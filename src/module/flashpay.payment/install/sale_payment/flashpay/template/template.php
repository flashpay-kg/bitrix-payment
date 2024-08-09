<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

use Bitrix\Main\SystemException;
use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

if (empty($params)) {
    echo 'Error: empty params data';
    return;
}

if (!$params['error']) { ?>
    <style>
        .btnFlashPay {
            border: none;
            border-radius: 10px;
            padding: 19px;
            width: 155px;
            background-color: #2dcfff;
        }
        .formWrapFlashPay {
            margin: 10px 0;
        }
    </style>

    <div>
        <?=GetMessage('PAYMENT_DESCRIPTION')?>
        <?=($params['IS_FLASHPAY_PROD_MODE'] === 'Y' ? '' : ' <span style="color:#999999">(Demo mode)</span>')?>
    </div>

    <div class="formWrapFlashPay">
        <form action="<?php echo $params['request']['action_url']; ?>" method="GET">
            <input type="hidden" name="language" value="<?=htmlspecialchars($params['request']['language'])?>" />
            <input type="hidden" name="body" value="<?=htmlspecialchars($params['request']['body'])?>" />
            <input type="hidden" name="signature" value="<?=htmlspecialchars($params['request']['signature'])?>" />
            <input class="btnFlashPay" type="submit" value="<?=GetMessage('SALE_FLASHPAY_PS_PAYBUTTON_NAME')?>" />
        </form>
    </div>
<?php } else { ?>
    <h3><?=GetMessage('SALE_FLASHPAY_PS_UNAVAILABLE')?></h3>
    <div><b>Error</b></div>
    <div>Message: <?=$params['error']?></div>
<?php } ?>
