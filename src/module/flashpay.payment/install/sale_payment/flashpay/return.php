<?php

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Loader;
use Bitrix\Sale\Order;

Loc::loadMessages(__FILE__);
Loader::includeModule('sale');

require($_SERVER['DOCUMENT_ROOT'] . '/bitrix/header.php');

$APPLICATION->SetPageProperty('title', GetMessage('SALE_FLASHPAY_PS_ORDER_INFO'));
$APPLICATION->SetPageProperty('NOT_SHOW_NAV_CHAIN', 'Y');
$APPLICATION->SetTitle(GetMessage('SALE_FLASHPAY_PS_ORDER_PAYMENT'));

/**
 * @return Order|null
 * @throws \Bitrix\Main\ArgumentException
 * @throws \Bitrix\Main\ArgumentNullException
 * @throws \Bitrix\Main\NotImplementedException
 */
function find_order_result(): ?Order
{
    $order = null;

    if (!empty($_REQUEST['ORDER_ID'])) {
        $order = Order::load($_REQUEST['ORDER_ID']);
    }

    if (empty($order) && !empty($_REQUEST['PAYMENT_ACCOUNT_NUMBER'])) {
        $paymentIndex = strrpos($_REQUEST['PAYMENT_ACCOUNT_NUMBER'], '/');
        if ($paymentIndex) {
            $accountNumber = mb_substr($_REQUEST['PAYMENT_ACCOUNT_NUMBER'], 0, $paymentIndex);
        } else {
            $accountNumber = $_REQUEST['PAYMENT_ACCOUNT_NUMBER'];
        }

        $order = Order::loadByAccountNumber($accountNumber);
    }

    return !empty($order) ? $order : null;
}

$order = find_order_result();

if (!$order) {
    $orderToShow = $_REQUEST['ORDER_ID'] ?? $_REQUEST['PAYMENT_ACCOUNT_NUMBER'] ?? null;
    if (empty($orderToShow)) {
        $orderToShow = 'EMPTY';
    }

    echo sprintf(GetMessage('SALE_FLASHPAY_PS_FAIL_TEXT'), $orderToShow);
} else {
    $orderIdForLink = $order->getId(); // alternatively $order->getField('ACCOUNT_NUMBER')

    $statusPageURL = sprintf('%s/%s', GetPagePath('personal/orders'), $orderIdForLink);

    echo sprintf(GetMessage('SALE_FLASHPAY_PS_RETURN_TEXT'), $orderIdForLink, $statusPageURL);
}

echo '<div style="margin: 0 0 30px 0;"></div>';
