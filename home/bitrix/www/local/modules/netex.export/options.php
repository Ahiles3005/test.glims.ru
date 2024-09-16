<?php

if ($_POST['reindex'] == 'Y' || $_POST['yandex_export']) {
    die;
}

$moduleId = 'netex.export';

use Bitrix\Iblock\IblockTable;
use Bitrix\Main\Config\Option;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Application;
use Netex\Export\Orm\ProfileTable;

Loc::loadMessages(__FILE__);

Loader::includeModule($moduleId);
Loader::includeModule('iblock');

if($_POST["submit"])
{
    $request = Application::getInstance()->getContext()->getRequest();

    Option::set($moduleId, 'catalog_iblock_id', $request->getPost('catalog_iblock_id'));
    Option::set($moduleId, 'step_processing', $request->getPost('step_processing'));
    Option::set($moduleId, 'profile_id', $request->getPost('profile_id'));
}

$resIblocks = IblockTable::getList(['select' => ['ID', 'NAME']]);
$arIblocks = [];
while ($arIblock = $resIblocks->fetch()) {
    $arIblocks[$arIblock['ID']] = $arIblock['NAME'] . " [{$arIblock['ID']}]";
}
$profiles = [];
$resProfiles = ProfileTable::getList(['filter' => ['ACTIVE' => 'Y']]);
while ($arProfile = $resProfiles->fetch()) {
    $profiles[$arProfile['ID']] = $arProfile['NAME'];
}
$aTabs = [
    [
        'DIV' => 'set',
        'TAB' => Loc::getMessage('NETEX_EXPORT_OPTIONS_TAB_NAME'),
        'TITLE' => Loc::getMessage('NETEX_EXPORT_OPTIONS_TAB_TITLE')
    ]
];

$tabControl = new CAdminTabControl('tabControl', $aTabs);

$arOptionsBase = [
    [
        'catalog_iblock_id',
        Loc::getMessage('NETEX_EXPORT_OPTIONS_CATALOG_IBLOCK_ID'),
        Option::get($moduleId, 'catalog_iblock_id'),
        ['selectbox', $arIblocks]
    ],
    [
        'step_processing',
        Loc::getMessage('NETEX_EXPORT_OPTIONS_STEP_PROCESSING'),
        Option::get($moduleId, 'step_processing'),
        ['text']
    ],
];

$arOptionsProfile = [
    [
        'profile_id',
        Loc::getMessage('NETEX_EXPORT_OPTIONS_PROFILE_ID'),
        Option::get($moduleId, 'profile_id'),
        ['selectbox', $profiles]
    ],
];
?>

<form method="POST" action="<?=$APPLICATION->GetCurPage()?>?lang=<?=LANGUAGE_ID?>&mid=<?=$moduleId?>">
    <?$tabControl->Begin();?>
    <?$tabControl->BeginNextTab();?>
    <?=bitrix_sessid_post();?>
    <?__AdmSettingsDrawList($moduleId, $arOptionsBase);?>
    <tr>
        <td>Обновить выгрузку</td>
        <td><input type="button" value="Начать" onclick="yandex_export();"></td>
    </tr>
    <tr class="heading">
        <td colspan="2">Обновить настройки профиля</td>
        <?__AdmSettingsDrawList($moduleId, $arOptionsProfile);?>
        <td>Обновить профиль</td>
        <td><input type="button" value="Начать" onclick="yandex_export_reindex_profile();"></td>
    </tr>
    <?$tabControl->Buttons();?>
    <input type="submit" name="submit" value="<?=Loc::getMessage('NETEX_EXPORT_OPTIONS_SAVE_BUTTON_NAME')?>" class="adm-btn-save">
    <?$tabControl->End();?>

</form>

<?php CJSCore::Init(['jquery']);
$documentRoot = Application::getDocumentRoot();
$local = \Bitrix\Main\IO\Directory::isDirectoryExists(
    Application::getDocumentRoot() . '/local/modules/' . $moduleId) ? 'local' : 'bitrix';
$ajaxPath = '/' . $local . '/modules/' . $moduleId . '/ajax';?>
<script>
    function load(status) {
        if (status === 'N') {
            $('form input[type="button"], form input[type="submit"]').removeAttr('disabled');
            BX.closeWait('form');
        } else {
            $('form input[type="button"], form input[type="submit"]').attr('disabled', 'disabled');
            BX.showWait('form');
        }

        return true;
    }

    function yandex_export() {
        load();

        $.ajax({
            url: '<?=$ajaxPath . '/main.php'?>',
            type: 'POST',
            data: {
                yandex_export: 'Y',
            },
            success: function(data) {
                load('N');
                alert('Выгрузка товаров завершена!')
            },
            error: function() {
                alert('Произошла неизвестная ошибка!');
                load('N');
            }
        });
    }

    function yandex_export_reindex_profile() {
        $.ajax({
            url: '<?=$ajaxPath . '/main.php'?>',
            type: 'POST',
            data: {
                'yandex_export_reindex_profile': 'Y', 'profile_id':  $('form select[name="profile_id"]').val()
            },
            success: function(data) {
                load('N');
                alert('Профиль выгрузки обновлен')
            },
            error: function() {
                alert('Произошла неизвестная ошибка!');
                load('N');
            }
        });
    }
</script>
