<?php
/**
 * Created by PhpStorm.
 * User: Nikolay Starostenko
 * Date: 15.06.18
 * Time: 11:17
 */

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Application;

Loc::loadMessages(__FILE__);

return array(
    'parent_menu' => 'global_menu_store',
    'sort' => 100,
    'text' => "Выгрузка на ЯМаркет",
    'url' => 'settings.php?lang=' . Application::getInstance()->getContext()->getLanguage() . '&mid=netex.export',
    'icon' => 'sale_menu_icon_marketplace',
    'page_icon' => 'sale_menu_icon_marketplace',
);