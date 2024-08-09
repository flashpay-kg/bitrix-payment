<?php

namespace Sale\Handlers\PaySystem;

use Bitrix\Main\Error;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Request;
use Bitrix\Sale\PaySystem;
use Bitrix\Sale\Payment;
use Bitrix\Sale;
use Bitrix\Sale\Order;
use CSalePaySystemAction;
use Bitrix\Sale\Delivery\Services\Table;
use Bitrix\Main\Loader;
use FlashPay\FlashPayMerchantAPI;

include_once(__DIR__ . '/sdk/FlashPayMerchantAPI.php');

Loc::loadMessages(__FILE__);

class flashpayHandler extends PaySystem\ServiceHandler
{
    /** @var array */
    protected $params;

    /**
     * @param Payment $payment
     * @param Request|null $request
     * @return PaySystem\ServiceResult
     */
    public function initiatePay(Payment $payment, Request $request = null)
    {
        $params = $this->getParams($payment);
        $initParams = $this->calculatePaymentParams($params, $payment);
        $paramsExtra = array_merge($params, $initParams);
        $this->setExtraParams($paramsExtra);

        return $this->showTemplate($payment, 'template');
    }

    /**
     * @param Payment $payment
     * @return array
     */
    public function getParams(Payment $payment): array
    {
        if (empty($this->params)) {
            $params = $this->getParamsBusValue($payment);

            foreach ($params as $key => $value) {
                $this->params[$key] = trim($value);
            }
        }

        return $this->params;
    }

    /**
     * @param $error
     * @return array|false|string
     */
    public function convertError($error)
    {
        return mb_convert_encoding($error, LANG_CHARSET);
    }

    /**
     * @param array $params
     * @param Payment $payment
     * @return array
     */
    public function calculatePaymentParams(array $params, Payment $payment): array
    {
        $psHandler = new FlashPayMerchantAPI($params);
        $items = [];
        $errorMessage = '';
        $paymentValues = $payment->getFieldValues();
        $order = $payment->getCollection()->getOrder();
        $arUser = \CUser::GetByID($order->getUserId())->Fetch();

        $allPayments = Payment::loadForOrder($paymentValues['ORDER_ID']);
        if (count($allPayments) > 1) {
            $paymentMethod = 'prepayment';
        } else {
            $paymentMethod = trim($params['PAYMENT_METHOD']);
        }

        $paymentAccountNumber = $paymentValues['ACCOUNT_NUMBER'];
        $propertyCollection = $order->getPropertyCollection();
        $dataOrderProps = self::getPhoneEmail($propertyCollection);
        $customerEmail = $dataOrderProps['EMAIL'] ?: $arUser['EMAIL'] ?: null;
        $customerPhone = $dataOrderProps['PHONE'] ?: $arUser['PERSONAL_PHONE'] ?: $arUser['PERSONAL_MOBILE'] ?: '';
        $customerPhone = preg_replace('~\D~', '', $customerPhone);

        $amount = $paymentValues['SUM'] * 100;

        $paymentData = [
            'project_id' => $psHandler->getCurrentProjectId(),
            'order_id'   => $paymentAccountNumber,
            'payment'    => [
                'amount'   => $amount,
                'currency' => $paymentValues['CURRENCY'],
            ],
            'payment_data' => [
                'method_type' => 'card',
            ],
            'customer' => array_filter([
                'id'         => $arUser['ID'],
                'ip_address' => $_SERVER['REMOTE_ADDR'],
                'phone'      => $customerPhone,
                'email'      => $customerEmail,
                'first_name' => $arUser['NAME'] ?? null,
                'last_name'  => $arUser['LAST_NAME'] ?? null,
            ]),
            // $_SERVER['SERVER_NAME'] - is not always right if from admin section or events, SITE_SERVER_NAME is safer
            'return_url' => $_SERVER['REQUEST_SCHEME'] . '://' . SITE_SERVER_NAME
                . '/personal/order/flashpay/return.php?ORDER_ID=' . $order->getId()
                . '&PAYMENT_ACCOUNT_NUMBER=' . $paymentAccountNumber,
        ];

        if ((string)$params['ENABLE_FISCAL_DATA'] === '1') {
            $paymentData['fiscal_data'] = [];
            $basketItems = $order->getBasket()->getBasketItems();

            foreach ($basketItems as $basketItem) {
                $productData = $basketItem->getFieldValues();
                $vatData = $productData['VAT_RATE'];
                Loader::includeModule('catalog');
                $itemVatID = \CCatalogProduct::getByID($productData['PRODUCT_ID']);

                if (!$itemVatID['VAT_ID']) {
                    $vat = 'none';
                } else {
                    $vat = $this->getFlashPayDeliveryTax((int)round($vatData * 100));
                }

                $nameProduct = $this->convertEncodingFlashPay($productData['NAME']);
                $items[] = [
                    'Name'          => mb_substr($nameProduct, 0, 64, 'UTF-8'),
                    'Price'         => round($productData['PRICE'] * 100),
                    'Quantity'      => round($productData['QUANTITY'], 3, PHP_ROUND_HALF_UP),
                    'Amount'        => round($productData['PRICE'] * $productData['QUANTITY'] * 100),
                    'PaymentMethod' => trim($params['PAYMENT_METHOD']),
                    'PaymentObject' => trim($params['PAYMENT_OBJECT']),
                    'Tax'           => $vat,
                ];

                // FFD 1.2
                foreach ($items as $key => $item) {
                    $items[$key]['MeasurementUnit'] = 'pc';
                }
            }

            $isShipping = false;
            $deliveryPrice = $order->getDeliveryPrice();
            if ($deliveryPrice > 0) {
                $deliveryVat = $params['DELIVERY_TAXATION'];
                if (!$deliveryVat) {
                    $errorMessage .= GetMessage('SALE_FLASHPAY_PS_TAX_DELIVERY_ERROR');
                }
                $deliverySystemId = reset($order->getDeliverySystemId());
                $dataDelivery = Table::getRowById($deliverySystemId);
                $items[] = [
                    'Name'          => mb_substr($this->convertEncodingFlashPay($dataDelivery['NAME']), 0, 64,  'UTF-8'),
                    'Price'         => round($deliveryPrice * 100),
                    'Quantity'      => 1,
                    'Amount'        => round($deliveryPrice * 100),
                    'PaymentMethod' => $paymentMethod,
                    'PaymentObject' => 'service',
                    'Tax'           => trim($params['DELIVERY_TAXATION']),
                ];

                // FFD 1.2
                foreach ($items as $key => $item) {
                    $items[$key]['MeasurementUnit'] = 'pc';
                }

                $isShipping = true;
            }

            $taxation = $params['TAXATION'];

            if (!$taxation) {
                $errorMessage .= GetMessage('SALE_FLASHPAY_PS_TAXATION_ERROR');
            }

            $items = $this->balanceAmountForItems($isShipping, $items, $amount);

            $fiscalEmail = trim(mb_substr($params['FISCAL_EMAIL'], 0, 64, 'UTF-8'));
            if (!preg_match('~^[^@]+@[^@]+\.[^@]+?$~s', $fiscalEmail)) {
                $fiscalEmail = 'none';
            }

            $paymentData['fiscal_data']['Receipt'] = [
                'EmailCompany' => $fiscalEmail,
                'Email'        => $customerEmail,
                'Phone'        => $customerPhone,
                'Taxation'     => $taxation,
                'Items'        => $items,
            ];

            // FFD 1.2
            $paymentData['fiscal_data']['Receipt']['FfdVersion'] = '1.2';
        }

        return [
            'error'   => $errorMessage,
            'request' => $psHandler->createPaymentPageRequest($paymentData),
        ];
    }

    /**
     * @param $propertyCollection
     * @return array
     */
    public static function getPhoneEmail($propertyCollection): array
    {
        $propsResult = [
            'PHONE' => null,
            'EMAIL' => null,
        ];

        foreach ($propertyCollection as $orderProperty) {
            $props = $orderProperty->getProperty();

            if ($props['IS_PHONE'] === 'Y') {
                $propsResult['PHONE'] = $orderProperty->getValue();
            }

            if ($props['IS_EMAIL'] === 'Y') {
                $propsResult['EMAIL'] = $orderProperty->getValue();
            }
        }

        return $propsResult;
    }

    /**
     * @param int|null $tax
     * @return string
     */
    private function getFlashPayDeliveryTax(?int $tax = null): string
    {
        $arrayTax = [
            0  => 'vat0',
            10 => 'vat10',
            20 => 'vat20',
        ];

        return $arrayTax[$tax] ?? 'none';
    }

    /**
     * @param bool $isShipping
     * @param array $items
     * @param int $amount
     * @return array
     */
    private function balanceAmountForItems(bool $isShipping, array $items, int $amount): array
    {
        $itemsWithoutShipping = $items;

        if ($isShipping) {
            $shipping = array_pop($itemsWithoutShipping);
        }

        $sum = 0;

        foreach ($itemsWithoutShipping as $item) {
            $sum += $item['Amount'];
        }

        if (isset($shipping)) {
            $sum += $shipping['Amount'];
        }

        if ($sum !== $amount) {
            $sumAmountNew = 0;
            $difference = $amount - $sum;
            $amountNews = [];

            foreach ($itemsWithoutShipping as $key => $item) {
                $itemsAmountNew = $item['Amount'] + floor($difference * $item['Amount'] / $sum);
                $amountNews[$key] = $itemsAmountNew;
                $sumAmountNew += $itemsAmountNew;
            }

            if (isset($shipping)) {
                $sumAmountNew += $shipping['Amount'];
            }

            if ($sumAmountNew !== $amount) {
                $maxKey = array_keys($amountNews, max($amountNews))[0];    // key of max value
                $amountNews[$maxKey] = max($amountNews) + ($amount - $sumAmountNew);
            }

            foreach ($amountNews as $key => $item) {
                $items[$key]['Amount'] = $item;
            }
        }

        return $items;
    }

    /**
     * Used for encodings different other from UTF-8
     *
     * @param $productName
     * @return array|false|string
     */
    public function convertEncodingFlashPay($productName)
    {
        return mb_convert_encoding($productName, 'UTF-8', LANG_CHARSET);
    }

    /**
     * @param Request $request
     * @return mixed
     */
    public function getPaymentIdFromRequest(Request $request)
    {
        return $request->get('ORDER_ID');
    }

    /**
     * Process request from provider (webhook for example) to finalize operation payment
     * This method is used by some pre-installed payment systems, but most of the modules use their own solutions as us
     *
     * @param Payment $payment
     * @param Request $request
     * @return PaySystem\ServiceResult
     */
    public function processRequest(Payment $payment, Request $request)
    {
        die('Closed. Use different flow for processing request: via FlashPay sdk');
    }

    /**
     * @return string[]
     */
    public function getCurrencyList(): array
    {
        return [
            'KGS',
        ];
    }
}
