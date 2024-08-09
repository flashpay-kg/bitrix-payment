<?php

IncludeModuleLangFile(__FILE__);

class flashpay_payment extends CModule
{
    public const MODULE_ID       = 'flashpay.payment';
    public const DIR_PERMISSIONS = 0755;

    public $MODULE_ID = self::MODULE_ID;
    public $MODULE_VERSION;
    public $MODULE_VERSION_DATE;
    public $MODULE_NAME;
    public $MODULE_DESCRIPTION;
    public $PARTNER_NAME;
    public $PARTNER_URI;

    public $strError = '';

    public function __construct()
    {
        $arModuleVersion = [];
        include(__DIR__ . '/version.php');

        $this->MODULE_VERSION      = $arModuleVersion['VERSION'];
        $this->MODULE_VERSION_DATE = $arModuleVersion['VERSION_DATE'];
        $this->MODULE_NAME         = GetMessage('FLASHPAY_PS_MODULE_NAME');
        $this->MODULE_DESCRIPTION  = GetMessage('FLASHPAY_PS_MODULE_DESC');
        $this->PARTNER_NAME        = GetMessage('FLASHPAY_PS_MODULE_NAME');
        $this->PARTNER_URI         = 'https://lk.flashpay.kg/';
    }

    /**
     * @return bool
     */
    public function InstallEvents(): bool
    {
        return true;
    }

    /**
     * @return bool
     */
    public function UnInstallEvents(): bool
    {
        return true;
    }

    /**
     * @param $dir
     * @return bool
     */
    public function rmFolder($dir): bool
    {
        foreach (glob($dir . '/*') as $file) {
            if (is_dir($file)) {
                $this->rmFolder($file);
            } else {
                unlink($file);
            }
        }
        rmdir($dir);

        return true;
    }

    /**
     * @param $source
     * @param $destination
     * @return void
     * @throws \RuntimeException
     */
    public function copyDir($source, $destination)
    {
        if (is_dir($source)) {
            $this->makeDir($destination);
            $directory = dir($source);

            while (false !== ($readDirectory = $directory->read())) {
                if ($readDirectory === '.' || $readDirectory === '..') {
                    continue;
                }

                $pathDir = $source . '/' . $readDirectory;

                if (is_dir($pathDir)) {
                    $this->copyDir($pathDir, $destination . '/' . $readDirectory);
                    continue;
                }

                copy($pathDir, $destination . '/' . $readDirectory);
            }
            $directory->close();
        } else {
            copy($source, $destination);
        }
    }

    /**
     * @param array $arParams
     * @return bool
     * @throws \RuntimeException
     */
    public function InstallFiles(array $arParams = []): bool
    {
        $ipnDir = $_SERVER['DOCUMENT_ROOT'] . '/bitrix/php_interface/include/sale_payment/';
        $this->makeDir($ipnDir);

        if (is_dir($source = $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/' . self::MODULE_ID . '/install')) {
            // bitrix/modules/sale/payment - system handler
            $this->copyDir($source . '/handler', $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/sale/payment');
            // php_interface/include/sale_payment - user handler
            $this->copyDir($source . '/handler', $ipnDir);

            $personalDir = $_SERVER['DOCUMENT_ROOT'] . '/personal/';
            $this->makeDir($personalDir);

            $orderDir = $_SERVER['DOCUMENT_ROOT'] . '/personal/order/';
            $this->makeDir($orderDir);

            $flashPayDir = $_SERVER['DOCUMENT_ROOT'] . '/personal/order/flashpay/';
            $this->makeDir($flashPayDir);

            copy($source . '/notifications/notification.php', $flashPayDir . 'notification.php');
            copy($source . '/notifications/return.php', $flashPayDir . 'return.php');
        }

        return true;
    }

    /**
     * @return bool
     */
    public function UnInstallFiles(): bool
    {
        $this->rmFolder($_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/sale/payment/flashpay');
        $this->rmFolder($_SERVER['DOCUMENT_ROOT'] . '/bitrix/php_interface/include/sale_payment/flashpay');

        unlink($_SERVER['DOCUMENT_ROOT'] . '/personal/order/flashpay/notification.php');
        unlink($_SERVER['DOCUMENT_ROOT'] . '/personal/order/flashpay/return.php');

        return true;
    }

    /**
     * @return void
     */
    public function DoInstall(): void
    {
        global $APPLICATION;
        global $DB;

        $strSql = "CREATE TABLE IF NOT EXISTS `flashpay_logs` (
            `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
            `payment_id` varchar(64) DEFAULT NULL,
            `type` varchar(20) DEFAULT NULL,
            `input` text,
            `response` text,
            `message` text,
            `date_created` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            KEY `payment_id` (`payment_id`),
            KEY `type` (`type`)
        ) ENGINE=InnoDB;";
        $DB->Query($strSql);

        $this->InstallFiles();

        RegisterModule(self::MODULE_ID);
    }

    /**
     * @return void
     */
    public function DoUninstall(): void
    {
        global $APPLICATION;

        UnRegisterModule(self::MODULE_ID);

        $this->UnInstallFiles();
    }

    /**
     * @param string $pathDir
     * @return void
     * @throws \RuntimeException
     */
    private function makeDir(string $pathDir): void
    {
        if (!is_dir($pathDir) && !mkdir($pathDir, self::DIR_PERMISSIONS) && !is_dir($pathDir)) {
            throw new \RuntimeException(sprintf('Directory "%s" was not created', $pathDir));
        }
    }
}
