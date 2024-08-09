<?php
require($_SERVER['DOCUMENT_ROOT'].'/bitrix/header.php');
$APPLICATION->SetTitle('Return after payment');
require($_SERVER['DOCUMENT_ROOT'].'/bitrix/php_interface/include/sale_payment/flashpay/return.php');
?>

<?php
require($_SERVER['DOCUMENT_ROOT'].'/bitrix/footer.php');
?>
