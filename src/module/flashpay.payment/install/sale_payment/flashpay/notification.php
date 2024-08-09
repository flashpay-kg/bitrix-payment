<?php

use Bitrix\Main\Application;
use Bitrix\Main\HttpRequest;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Sale\Order;
use Bitrix\Main\Engine\Response\Json;
use Bitrix\Main\ArgumentException;
use Bitrix\Main\ArgumentNullException;
use Bitrix\Main\ObjectPropertyException;
use Bitrix\Main\SystemException;
use Bitrix\Sale\Payment;

require_once($_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/bx_root.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php');

Loader::includeModule('sale');
Loader::includeModule('HttpRequest');

require_once(__DIR__ . '/sdk/flashpay_autoload.php');

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

try {
    handleNotification();
} catch (Exception $exception) {
    sendResponse(
        [
            'status'  => 'error',
            'message' => $exception->getMessage(),
        ],
        400
    );
}

/**
 * @return void
 *
 * @throws FlashPayException
 * @throws ArgumentException
 * @throws ArgumentNullException
 * @throws ObjectPropertyException
 * @throws SystemException
 */
function handleNotification(): void
{
    $rawInput = (string)HttpRequest::getInput();
    $requestData = json_decode($rawInput, true);

    try {
        $orderValues = findOrderAndPayment($requestData);
    } catch (FlashPayException $e) {
        sendResponse(['status' => 'failure', 'message' => $e->getMessage()], 404);
    }

    if (empty($orderValues) || empty($orderValues[0]) || empty($orderValues[1])) {
        sendResponse(['status' => 'failure', 'message' => 'Order not found'], 404);
    }

    /**
     * @var Order $order
     * @var Payment $payment
     */
    [$order, $payment] = $orderValues ?? null;

    $paySystem = $payment->getPaySystem();
    $extra = $paySystem->getParamsBusValue($payment);

    try {
        $request = Application::getInstance()->getContext()->getRequest();

        $notificationModel = new FlashPayNotification(
            $extra,
            $requestData,
            $rawInput,
            $request->getHeader('x-signature') ?? ''
        );
    } catch (FlashPayException $e) {
        sendResponse(
            [
                'status'    => 'failure',
                'message'   => $e->getMessage(),
                'prod_mode' => $extra['IS_FLASHPAY_PROD_MODE'],
            ],
            400
        );
    }

    if (isset($notificationModel)) {
        if ($notificationModel->isPaymentSuccess()) {
            $paymentValues = $payment->getFieldValues();

            if (strtoupper($paymentValues['CURRENCY']) !== strtoupper($requestData['operation']['currency'])) {
                $responseMessage = 'Currency is not the same: ' . $paymentValues['CURRENCY'];
                sendResponse(
                    [
                        'status'    => 'failure',
                        'message'   => $responseMessage,
                        'prod_mode' => $extra['IS_FLASHPAY_PROD_MODE'],
                    ]
                );
            } else {
                $amountMerchantMinor = $paymentValues['SUM'] * 100;

                if ((string)$amountMerchantMinor === (string)$requestData['operation']['amount']) {
                    $responseMessage = 'Payment finished successful';
                } else {
                    $responseMessage = 'Payment finished successful but amount was changed from '
                        . $amountMerchantMinor . ' to ' . $requestData['operation']['amount'];

                    // SUM of payment could be change ony if payment is not set as paid yet
                    $payment->setPaid('N');
                    $payment->setField('SUM', $requestData['operation']['amount'] * 0.01);
                    // need to save order (and its payment) here because Paid status here still is N (not paid)
                    $order->save();

                    FlashPayNotification::logData(
                        isset($requestData['order_id']) ? (string)$requestData['order_id'] : null,
                        FlashPayNotification::LOG_TYPE_WEBHOOK_WRONG_AMOUNT,
                        $rawInput,
                        '',
                        json_encode([
                            'old_amount' => $amountMerchantMinor,
                            'new_amount' => $requestData['operation']['amount'],
                        ])
                    );
                }

                // Fields:
                // PS_STATUS
                // PS_STATUS_CODE
                // PS_INVOICE_ID
                // PS_STATUS_DESCRIPTION
                // PS_STATUS_MESSAGE
                // PS_SUM
                // PS_CURRENCY
                // PS_RESPONSE_DATE
                // PS_RECURRING_TOKEN
                // PS_CARD_NUMBER
                $payment->setField('PS_STATUS', 'Y');
                $payment->setField('PS_STATUS_CODE', $requestData['operation']['status']);
                $payment->setField('PS_INVOICE_ID', $requestData['operation']['id']);
                $payment->setField('PS_SUM', $requestData['operation']['amount'] * 0.01);
                $payment->setField('PS_CURRENCY', $requestData['operation']['currency']);
                $payment->setField('PS_STATUS_DESCRIPTION', json_encode($requestData));

                $payment->setPaid('Y');

                // no need to save payment $payment->save() because saving of order also saves its payments
                // it is important to save order not just payment because it updates info in inner data & list of orders
                $order->save();
            }
        } elseif ($notificationModel->isPaymentDecline()) {
            $payment->setField('PS_STATUS', 'N');
            $payment->setField('PS_STATUS_CODE', $requestData['operation']['status']);
            $payment->setField('PS_INVOICE_ID', $requestData['operation']['id']);
            $payment->setField('PS_STATUS_DESCRIPTION', json_encode($requestData));

            $payment->setPaid('N');
            $order->save();

            $responseMessage = 'Payment declined';
        } else {
            $responseMessage = 'Not final status. Info logged. Payment was not changed';
        }

        sendResponse(
            [
                'status'    => 'success',
                'message'   => $responseMessage,
                'prod_mode' => $extra['IS_FLASHPAY_PROD_MODE'],
            ]
        );
    } else {
        sendResponse(['status' => 'error', 'message' => 'Internal error in notification model'], 400);
    }
}

/**
 * @param array|null $requestData
 * @return array|null
 * @throws FlashPayException
 */
function findOrderAndPayment(?array $requestData): ?array
{
    if (empty($requestData) || !is_array($requestData)) {
        throw new FlashPayException('Input data is not correct json');
    }

    if (empty($requestData['order_id'])) {
        throw new FlashPayException('Empty order_id');
    }

    $orderInput = (string)$requestData['order_id'];
    $paymentIndex = strrpos($orderInput, '/');

    if (!$paymentIndex) {
        throw new FlashPayException('Wrong format of order_id');
    }

    $accountNumber = substr($orderInput, 0, $paymentIndex);

    try {
        /** @var Order|null $order */
        $order = Order::loadByAccountNumber($accountNumber);
    } catch (Exception $exception) {
        throw new FlashPayException('Error on order search');
    }

    if (empty($order)) {
        throw new FlashPayException('Order not found by payment account number');
    }

    $paymentCollection = $order->getPaymentCollection();
    /** @var Payment|null $payment */
    $payment = null;

    foreach ($paymentCollection as $paymentObject) {
        if ((string)$paymentObject->getField('ACCOUNT_NUMBER') === $orderInput) {
            $payment = $paymentObject;
            break;
        }
    }

    if (empty($payment)) {
        throw new FlashPayException('Order was found but payment was not found');
    }

    return [$order, $payment];
}

/**
 * @param array $data
 * @param int $status
 * @return void
 */
function sendResponse(array $data, int $status = 200): void
{
    $rawInput = (string)HttpRequest::getInput();
    $requestData = json_decode($rawInput, true);
    FlashPayNotification::logData(
        isset($requestData['order_id']) ? (string)$requestData['order_id'] : null,
        FlashPayNotification::LOG_TYPE_WEBHOOK,
        $rawInput,
        json_encode($data),
        json_encode(['header_status_response' => $status])
    );

    $response = new Json($data);
    $response->setStatus($status);
    $response->send();

    exit();
}

sendResponse(['status' => 'error', 'message' => 'Internal error. Unhandled case'], 400);
