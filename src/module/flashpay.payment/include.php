<?php

class CFlashPayPayment
{
    /**
     * @param $aGlobalMenu
     * @param $aModuleMenu
     * @return void
     */
    public function OnBuildGlobalMenu(&$aGlobalMenu, &$aModuleMenu): void
    {
        $MODULE_ID = basename(__DIR__);

        $aMenu = [
            'parent_menu' => 'global_menu_settings',
            'section'     => $MODULE_ID,
            'sort'        => 50,
            'text'        => $MODULE_ID,
            'title'       => '',
            'url'         => 'partner_modules.php?module=' . $MODULE_ID,
            'icon'        => '',
            'page_icon'   => '',
            'items_id'    => $MODULE_ID . '_items',
            'more_url'    => [],
            'items'       => [],
        ];

        $path = __DIR__ . '/admin';

        if (file_exists($path) && $dir = opendir($path)) {
            $arFiles = [];
            while (false !== $item = readdir($dir)) {
                if (in_array($item, ['.', '..', 'menu.php'])) {
                    continue;
                }
                if (!file_exists($file = $_SERVER['DOCUMENT_ROOT'] . '/bitrix/admin/' . $MODULE_ID . '_' . $item)) {
                    file_put_contents(
                        $file,
                        '<' . '? require($_SERVER[\'DOCUMENT_ROOT\'] . \'/bitrix/modules/'
                        . $MODULE_ID . '/admin/' . $item . '\');?' . '>'
                    );
                }
                $arFiles[] = $item;
            }

            sort($arFiles);

            foreach ($arFiles as $item) {
                $aMenu['items'][] = [
                    'text'      => $item,
                    'url'       => $MODULE_ID . '_' . $item,
                    'module_id' => $MODULE_ID,
                    'title'     => '',
                ];
            }
        }
    }
}
