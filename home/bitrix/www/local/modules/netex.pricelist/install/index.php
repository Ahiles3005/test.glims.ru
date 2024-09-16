<?php

use \Bitrix\Main\Localization\Loc;
use \Bitrix\Main\Application;
use \Bitrix\Main\Entity;
use \Bitrix\Main\EventManager;
use Bitrix\Main\IO\File;

Loc::loadMessages(__FILE__);

/**
 * Class netex_pricelist
 */
Class netex_pricelist extends CModule
{
	const MODULE_ID = 'netex.pricelist';
	var $MODULE_ID = "netex.pricelist";
	var $MODULE_VERSION;
	var $MODULE_VERSION_DATE;
	var $MODULE_NAME;
	var $MODULE_DESCRIPTION;
	var $MODULE_CSS;
	var $strError = '';
    var $arModuleVersion = array();

	function __construct()
	{
		include(dirname(__FILE__) . "/version.php");
        $this->arModuleVersion = $arModuleVersion;

		$this->MODULE_NAME = "Выгрузка прайс-листа";
		$this->MODULE_DESCRIPTION = "Модуль реализует генерацию прайс-листа на агенте.";

		$this->MODULE_VERSION = $this->arModuleVersion["VERSION"];
		$this->MODULE_VERSION_DATE = $this->arModuleVersion["VERSION_DATE"];


		$this->PARTNER_NAME = "Нетекс";
		$this->PARTNER_URI = "//netex.pro";

		$this->MODULE_SORT = 1;
		$this->SHOW_SUPER_ADMIN_GROUP_RIGHTS = "Y";
		$this->MODULE_GROUP_RIGHTS = "Y";
	}


	/**
	 * @throws \Bitrix\Main\LoaderException
	 */
	function DoInstall()
	{
		global $APPLICATION;
		if ($this->isVersionD7()) {
            \Bitrix\Main\ModuleManager::registerModule($this->MODULE_ID);
            \Bitrix\Main\Loader::includeModule($this->MODULE_ID);
            \CAgent::AddAgent(
				"\NetexPriceList::createExportFile();",
				"netex.pricelist"
			);
		} else {
			$APPLICATION->ThrowException("Нет ядра D7");
		}

		$APPLICATION->IncludeAdminFile("Выгрузка прайс-листа", $this->GetPath() . "/install/step.php");
	}

	/**
	 * @throws \Bitrix\Main\LoaderException
	 * @throws \Bitrix\Main\SystemException
	 */
	function DoUninstall()
	{
		global $APPLICATION;
		\Bitrix\Main\Loader::includeModule($this->MODULE_ID);

		$context = Application::getInstance()->getContext();
		$request = $context->getRequest();

		if ($request['step'] < 2) {
			$APPLICATION->IncludeAdminFile("Выгрузка прайс-листа", $this->GetPath() . '/install/unstep1.php');
		}

		if ($request['step'] == 2) {
			\CAgent::RemoveAgent(
				"\NetexPriceList::createExportFile();",
				"netex.pricelist"
			);

			\Bitrix\Main\ModuleManager::unRegisterModule($this->MODULE_ID);
			$APPLICATION->IncludeAdminFile("Выгрузка прайс-листа", $this->GetPath() . '/install/unstep2.php');
		}
	}

	function isVersionD7()
	{
		return CheckVersion($this->getVersion('main'), '14.00.00');
	}

	function GetPath($notDocumentRoot = false)
	{
		if ($notDocumentRoot)
			return str_ireplace(\Bitrix\Main\Application::getDocumentRoot(), '', dirname(__DIR__));
		else
			return dirname(__DIR__);
	}

	function getVersion($moduleName)
	{
		$moduleName = preg_replace("/[^a-zA-Z0-9_.]+/i", "", trim($moduleName));
		if ($moduleName == '')
			return false;

		$version = false;

		if ($moduleName == 'main')
		{
			if (!defined("SM_VERSION"))
			{
				include_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/classes/general/version.php");
			}
			$version = SM_VERSION;
		}
		else
		{
			include_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/tools.php");
			$modulePath = getLocalPath("modules/".$moduleName."/install/version.php");
			if ($modulePath === false)
				return false;

			include($_SERVER["DOCUMENT_ROOT"].$modulePath);
			$version = array_key_exists("VERSION", $this->arModuleVersion)? $this->arModuleVersion["VERSION"]: false;
		}

		return $version;
	}
}
?>
