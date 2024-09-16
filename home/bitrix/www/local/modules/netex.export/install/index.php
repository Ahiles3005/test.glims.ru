<?php

use Bitrix\Main\Entity;
use Bitrix\Main\Application;
use Bitrix\Main\Config\Option;
use Bitrix\Main\Loader;
use Bitrix\Main\ModuleManager;
use Bitrix\Main\IO\File;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Type\DateTime;
use Netex\Sync\Orm\QueueTable;

Loc::loadMessages(__FILE__);

if (class_exists('netex_export')) return;

class netex_export extends \CModule
{
    var $MODULE_ID = 'netex.export';
    var $MODULE_VERSION;
    var $MODULE_VERSION_DATE;
    var $MODULE_NAME;
    var $MODULE_DESCRIPTION;
    var $PARTNER_NAME;
    var $PARTNER_URI;

    var $adminFiles = [];

    var $entities = [
        \Netex\Export\Orm\ProfileTable::class,
        \Netex\Export\Orm\OffersTable::class,
        \Netex\Export\Orm\ContentTable::class,
    ];

    function __construct()
    {
        $arModuleVersion = array();
        include(__DIR__ . '/version.php');

        $this->MODULE_NAME = Loc::getMessage('NETEX_EXPORT_INSTALL_MODULE_NAME');
        $this->MODULE_DESCRIPTION = Loc::getMessage('NETEX_EXPORT_INSTALL_MODULE_DESCRIPTION');

        $this->MODULE_VERSION = $arModuleVersion['VERSION'];
        $this->MODULE_VERSION_DATE = $arModuleVersion['VERSION_DATE'];

        $this->PARTNER_NAME = Loc::getMessage('NETEX_EXPORT_INSTALL_MODULE_PARTNER_NAME');
        $this->PARTNER_URI = Loc::getMessage('NETEX_EXPORT_INSTALL_MODULE_PARTNER_URI');
    }

    function DoInstall()
    {
        ModuleManager::registerModule($this->MODULE_ID);
        Loader::includeModule($this->MODULE_ID);

        $this->registerHandlers();
        $this->addDbTables();
        $this->copyFiles();
        $this->addAgents();
        $this->setOtherSettings();

        return true;
    }

    function DoUninstall()
    {
        global $APPLICATION, $USER, $step;

        if ($USER->IsAdmin()) {
            Loader::includeModule($this->MODULE_ID);
            $step = IntVal($step);

            if ($step < 2) {
                $APPLICATION->IncludeAdminFile(Loc::getMessage("NETEX_EXPORT_INSTALL_MODULE_UNINSTALL_TITLE"), Loader::getLocal('modules/' . $this->MODULE_ID) . "/install/unstep1.php");
            } elseif ($step == 2) {
                $this->unRegisterHandlers();
                $this->removeFiles();
                $this->removeAgents();
                $this->removeOtherSettings();
                if ($_REQUEST['savedata'] != 'Y') {
                    $this->dropDbTables();
                }
                ModuleManager::unRegisterModule($this->MODULE_ID);
                Option::delete($this->MODULE_ID);
                $APPLICATION->IncludeAdminFile(Loc::getMessage("NETEX_EXPORT_INSTALL_MODULE_UNINSTALL_TITLE"), Loader::getLocal('modules/' . $this->MODULE_ID) . "/install/unstep2.php");
            }
        }
    }

    private function addDbTables()
    {
        foreach ($this->entities as $entityName) {
            $entity = Entity\Base::getInstance($entityName);
            if (!Application::getConnection()->isTableExists($entity->getDBTableName())) {
                $entity->createDbTable();
            }
        }
        return true;
    }

    private function dropDbTables()
    {
        foreach ($this->entities as $entityName) {
            $entity = Entity\Base::getInstance($entityName);
            Application::getConnection($entityName::getConnectionName())
                ->queryExecute('DROP TABLE IF EXISTS ' . $entity->getDBTableName());
        }
        return true;
    }

    private function copyFiles()
    {
        $filesPath = str_replace(Application::getDocumentRoot(), '', Loader::getLocal('modules/' . $this->MODULE_ID));
        foreach ($this->adminFiles as $filename) {
            File::putFileContents(
                Application::getDocumentRoot() . '/bitrix/admin/netex_export_' . $filename,
                '<?php require $_SERVER["DOCUMENT_ROOT"] . "' . $filesPath . '/admin/' . $filename . '";'
            );
        }

        if (!file_exists($exportPath = Application::getDocumentRoot() . '/export')) {
            mkdir($exportPath, 0777, true);
        }
    }

    private function removeFiles()
    {
        foreach ($this->adminFiles as $filename) {
            File::deleteFile(Application::getDocumentRoot() . '/bitrix/admin/netex_export+' . $filename);
        }
    }

    private function registerHandlers()
    {
        $eventManager = \Bitrix\Main\EventManager::getInstance();

        $eventManager->registerEventHandler('iblock', 'OnAfterIBlockElementAdd', $this->MODULE_ID, '\Netex\Export\Event', 'addOffer2Export');
        $eventManager->registerEventHandler('iblock', 'OnAfterIBlockElementUpdate', $this->MODULE_ID, '\Netex\Export\Event', 'addChangedFlag');
        $eventManager->registerEventHandler('iblock', 'OnAfterIBlockElementDelete', $this->MODULE_ID, '\Netex\Export\Event', 'deleteOfferFromExport');
        $eventManager->registerEventHandler('catalog', 'OnProductUpdate', $this->MODULE_ID, '\Netex\Export\Event', 'addChangedFlagFromProduct');
    }

    private function unRegisterHandlers()
    {
        $eventManager = \Bitrix\Main\EventManager::getInstance();

        $eventManager->unRegisterEventHandler('iblock', 'OnAfterIBlockElementAdd', $this->MODULE_ID, '\Netex\Export\Event', 'addOffer2Export');
        $eventManager->unRegisterEventHandler('iblock', 'OnAfterIBlockElementUpdate', $this->MODULE_ID, '\Netex\Export\Event', 'addChangedFlag');
        $eventManager->unRegisterEventHandler('iblock', 'OnAfterIBlockElementDelete', $this->MODULE_ID, '\Netex\Export\Event', 'deleteOfferFromExport');
        $eventManager->unRegisterEventHandler('catalog', 'OnProductUpdate', $this->MODULE_ID, '\Netex\Export\Event', 'addChangedFlagFromProduct');
    }

    private function addAgents()
    {
        \CAgent::AddAgent(
            '\Netex\Export\Agent::indexChanged();',
            $this->MODULE_ID,
            'N',
            60
        );
    }

    private function removeAgents()
    {
        Application::getConnection()->query('DELETE FROM b_agent WHERE MODULE_ID="' . $this->MODULE_ID . '"');
    }

    private function setOtherSettings()
    {
        $ufEntity = new \CUserTypeEntity();

        $arUFFields = [
            'ENTITY_ID' => 'IBLOCK_19_SECTION',
            'FIELD_NAME' => 'UF_YM_CATEGORY',
            'USER_TYPE_ID' => 'string',
            'XML_ID' => 'XML_ID_YM_CATEGORY',
            'MULTIPLE' => 'N',
            'MANDATORY' => 'N',
            'SHOW_IN_LIST' => 'Y',
            'EDIT_IN_LIST' => 'Y',
            'EDIT_FORM_LABEL' => [
                'ru' => 'Категория на ЯМаркете',
                'en' => 'Category name from Yandex',
            ],
            'LIST_COLUMN_LABEL' => [
                'ru' => 'Категория на ЯМаркете',
                'en' => 'Category name from Yandex',
            ]
        ];
        $ufEntity->Add($arUFFields);
    }

    private function removeOtherSettings()
    {
        $ufEntity = new \CUserTypeEntity();

        $id = $ufEntity::GetList([], ['FIELD_NAME' => 'UF_YM_CATEGORY'])->Fetch()['ID'];
        if ($id) $ufEntity->Delete($id);
    }
}