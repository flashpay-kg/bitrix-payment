<?php

use Bitrix\Main\Localization\Loc;

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die(); ?><?php

Loc::loadMessages(__FILE__);

$data = array(
    'NAME'        => GetMessage('SALE_FLASHPAY_PS_TITLE'),
    'DESCRIPTION' => GetMessage('SALE_FLASHPAY_PS_DESCRIPTION'),
    'CODES'       => array(
        'IS_FLASHPAY_PROD_MODE' => array(
            'NAME'        => GetMessage('SALE_FLASHPAY_PS_IS_FLASHPAY_PROD_MODE_NAME'),
            'DESCRIPTION' => GetMessage('SALE_FLASHPAY_PS_IS_FLASHPAY_PROD_MODE_DESCR'),
            'INPUT'       => array(
                'TYPE'    => 'Y/N',
            ),
            'GROUP'       => 'CREDENTIALS',
            'SORT'        => 100,
        ),

        'PROJECT_ID_PROD' => array(
            'NAME'        => GetMessage('SALE_FLASHPAY_PS_PROJECT_ID_PROD_NAME'),
            'DESCRIPTION' => GetMessage('SALE_FLASHPAY_PS_PROJECT_ID_PROD_DESCR'),
            'GROUP'       => 'CREDENTIALS',
            'SORT'        => 200,
        ),

        'SECRET_KEY_PROD' => array(
            'NAME'        => GetMessage('SALE_FLASHPAY_PS_SECRET_KEY_PROD_NAME'),
            'DESCRIPTION' => GetMessage('SALE_FLASHPAY_PS_SECRET_KEY_PROD_DESCR'),
            'GROUP'       => 'CREDENTIALS',
            'SORT'        => 300,
        ),

        'PROJECT_ID_STAGE' => array(
            'NAME'        => GetMessage('SALE_FLASHPAY_PS_PROJECT_ID_STAGE_NAME'),
            'DESCRIPTION' => GetMessage('SALE_FLASHPAY_PS_PROJECT_ID_STAGE_DESCR'),
            'GROUP'       => 'CREDENTIALS',
            'SORT'        => 400,
        ),

        'SECRET_KEY_STAGE' => array(
            'NAME'        => GetMessage('SALE_FLASHPAY_PS_SECRET_KEY_STAGE_NAME'),
            'DESCRIPTION' => GetMessage('SALE_FLASHPAY_PS_SECRET_KEY_STAGE_DESCR'),
            'GROUP'       => 'CREDENTIALS',
            'SORT'        => 500,
        ),

        'LANGUAGE_PAYMENT' => array(
            'NAME'        => GetMessage('SALE_FLASHPAY_PS_LANGUAGE_NAME'),
            'DESCRIPTION' => GetMessage('SALE_FLASHPAY_PS_LANGUAGE_DESCR'),
            'INPUT'       => array(
                'TYPE'      => 'ENUM',
                'OPTIONS'   => array(
                    'ru'      => GetMessage('SALE_FLASHPAY_PS_LANGUAGE_RU'),
                    'en'      => GetMessage('SALE_FLASHPAY_PS_LANGUAGE_EN'),
                    'kg'      => GetMessage('SALE_FLASHPAY_PS_LANGUAGE_KG'),
                )
            ),
            'GROUP'       => 'GENERAL',
            'SORT'        => 600,
        ),

        'ENABLE_FISCAL_DATA' => array(
            'NAME'        => GetMessage('SALE_FLASHPAY_PS_ENABLE_FISCAL_DATA_NAME'),
            'DESCRIPTION' => GetMessage('SALE_FLASHPAY_PS_ENABLE_FISCAL_DATA_DESCR'),
            'INPUT'       => array(
                'TYPE'    => 'ENUM',
                'OPTIONS' => array(
                    '0' => GetMessage('SALE_FLASHPAY_PS_NO'),
                    '1' => GetMessage('SALE_FLASHPAY_PS_YES'),
                )
            ),
            'GROUP'       => 'FISCAL_CONFIGS',
            'SORT'        => 700,
        ),

        'FISCAL_EMAIL' => array(
            'NAME'        => GetMessage('SALE_FLASHPAY_PS_FISCAL_EMAIL_NAME'),
            'DESCRIPTION' => GetMessage('SALE_FLASHPAY_PS_FISCAL_EMAIL_DESCR'),
            'GROUP'       => 'FISCAL_CONFIGS',
            'SORT'        => 800,
        ),

        'TAXATION' => array(
            'NAME'        => GetMessage('SALE_FLASHPAY_PS_TAXATION_NAME'),
            'DESCRIPTION' => GetMessage('SALE_FLASHPAY_PS_TAXATION_DESCR'),
            'INPUT'       => array(
                'TYPE'    => 'ENUM',
                'OPTIONS' => array(
                    'osn'                => GetMessage('SALE_FLASHPAY_PS_TAXATION_OSN'),
                    'usn_income'         => GetMessage('SALE_FLASHPAY_PS_TAXATION_USN_IMCOME'),
                    'usn_income_outcome' => GetMessage('SALE_FLASHPAY_PS_TAXATION_USN_IMCOME_OUTCOME'),
                    'envd'               => GetMessage('SALE_FLASHPAY_PS_TAXATION_ENVD'),
                    'esn'                => GetMessage('SALE_FLASHPAY_PS_TAXATION_ESN'),
                    'patent'             => GetMessage('SALE_FLASHPAY_PS_TAXATION_PATENT'),
                )
            ),
            'GROUP'       => 'FISCAL_CONFIGS',
            'SORT'        => 900,
        ),

        'PAYMENT_METHOD' => array(
            'NAME'        => GetMessage('SALE_FLASHPAY_PS_PAYMENT_METHOD_NAME'),
            'INPUT'       => array(
                'TYPE'      => 'ENUM',
                'OPTIONS'   => array(
                    'full_prepayment'  => GetMessage('SALE_FLASHPAY_PS_PAYMENT_METHOD_FULL_PREPAYMENT'),
                    'prepayment'       => GetMessage('SALE_FLASHPAY_PS_PAYMENT_METHOD_PREPAYMENT'),
                    'advance'          => GetMessage('SALE_FLASHPAY_PS_PAYMENT_METHOD_ADVANCE'),
                    'full_payment'     => GetMessage('SALE_FLASHPAY_PS_PAYMENT_METHOD_FULL_PAYMENT'),
                    'partial_payment ' => GetMessage('SALE_FLASHPAY_PS_PAYMENT_METHOD_PARTIAL_PAYMENT'),
                    'credit'           => GetMessage('SALE_FLASHPAY_PS_PAYMENT_METHOD_CREDIT'),
                    'credit_payment '  => GetMessage('SALE_FLASHPAY_PS_PAYMENT_METHOD_CREDIT_PAYMENT'),
                )
            ),
            'GROUP'       => 'FISCAL_CONFIGS',
            'SORT'        => 1000,
        ),

        'PAYMENT_OBJECT' => array(
            'NAME'        => GetMessage('SALE_FLASHPAY_PS_PAYMENT_OBJECT_NAME'),
            'INPUT'       => array(
                'TYPE'      => 'ENUM',
                'OPTIONS'   => array(
                    'commodity'             => GetMessage('SALE_FLASHPAY_PS_PAYMENT_METHOD_COMMODITY'),
                    'excise'                => GetMessage('SALE_FLASHPAY_PS_PAYMENT_METHOD_EXCISE'),
                    'job'                   => GetMessage('SALE_FLASHPAY_PS_PAYMENT_METHOD_JOB'),
                    'service'               => GetMessage('SALE_FLASHPAY_PS_PAYMENT_METHOD_SERVICE'),
                    'gambling_bet '         => GetMessage('SALE_FLASHPAY_PS_PAYMENT_METHOD_GAMBLING_BET'),
                    'gambling_prize'        => GetMessage('SALE_FLASHPAY_PS_PAYMENT_METHOD_GAMBLING_PRIZE'),
                    'lottery'               => GetMessage('SALE_FLASHPAY_PS_PAYMENT_METHOD_LOTTERY'),
                    'lottery_prize'         => GetMessage('SALE_FLASHPAY_PS_PAYMENT_METHOD_LOTTERY_PRIZE'),
                    'intellectual_activity' => GetMessage('SALE_FLASHPAY_PS_PAYMENT_METHOD_INTELLECTUAL_ACTIVITY'),
                    'payment'               => GetMessage('SALE_FLASHPAY_PS_PAYMENT_METHOD_PAYMENT'),
                    'agent_commission'      => GetMessage('SALE_FLASHPAY_PS_PAYMENT_METHOD_AGENT_COMMISSION'),
                    'composite'             => GetMessage('SALE_FLASHPAY_PS_PAYMENT_METHOD_PARTIAL_COMPOSITE'),
                    'another'               => GetMessage('SALE_FLASHPAY_PS_PAYMENT_METHOD_ANOTHER'),
                )
            ),
            'GROUP'       => 'FISCAL_CONFIGS',
            'SORT'        => 1100,
        ),

        'DELIVERY_TAXATION' => array(
            'NAME'        => GetMessage('SALE_FLASHPAY_PS_DELIVERY_TAXATION_NAME'),
            'DESCRIPTION' => GetMessage('SALE_FLASHPAY_PS_DELIVERY_TAXATION_DESCR'),
            'INPUT'       => array(
                'TYPE'     => 'ENUM',
                'OPTIONS'  => array(
                    'none'  => GetMessage('SALE_FLASHPAY_PS_VAT_NONE'),
                    'vat0'  => GetMessage('SALE_FLASHPAY_PS_VAT_ZERO'),
                    'vat10' => GetMessage('SALE_FLASHPAY_PS_VAT_REDUCED'),
                    'vat20' => GetMessage('SALE_FLASHPAY_PS_VAT_STANDARD'),
                )
            ),
            'GROUP'       => 'FISCAL_CONFIGS',
            'SORT'        => 1200,
        ),
    )
);
