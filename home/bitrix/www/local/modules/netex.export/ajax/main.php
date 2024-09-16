<?php
/**
 * Created by PhpStorm.
 * User: Nikolay Starostenko
 * Date: 26.04.18
 * Time: 15:01
 */

ini_set('max_execution_time', 3600);

define("STATISTIC_SKIP_ACTIVITY_CHECK", "true");
define('STOP_STATISTICS', true);
define('PUBLIC_AJAX_MODE', true);
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php");

use Bitrix\Main\Loader;
use Netex\Export\Export;

Loader::includeModule('netex.export');

if ($_POST['yandex_export'] == 'Y') {
    $export = Export::getInstance();
    foreach ($export->arProfiles as $arProfile) {
        $export->generateFile($arProfile['ID']);
    }
}
if ($_POST['yandex_export_reindex_profile'] === 'Y') {
    $export = Export::getInstance();
    $export->reindex((int)$_POST['profile_id']);
}
