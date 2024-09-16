<?
if(!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

if(!isset($arParams['CACHE_TIME']))
	$arParams['CACHE_TIME'] = 36000000;
if(\Bitrix\Main\Loader::includeModule('aspro.max'))
{
	if($this->getTemplateName() == 'mail')
	{
		// include CMainPage
		require_once($_SERVER['DOCUMENT_ROOT']."/bitrix/modules/main/include/mainpage.php");
		// get site_id by host
		$obMainPage = new CMainPage();
		$site_id = $obMainPage->GetSiteByHost();
		if(!$site_id || $site_id == "ru")
		    $site_id = "s1";
		$arFrontParametrs = CMax::GetFrontParametrsValues($site_id);
	}
	else
		$arFrontParametrs = CMax::GetFrontParametrsValues(SITE_ID);
	$arResult['SOCIAL_TELEGRAM'] = $arFrontParametrs['SOCIAL_TELEGRAM'];
	$arResult['SOCIAL_WHATS'] = $arFrontParametrs['SOCIAL_WHATS'];
	$arResult['SOCIAL_WHATS_TEXT'] = $arFrontParametrs['SOCIAL_WHATS_TEXT'];
	$arResult['SOCIAL_WHATS_CUSTOM'] = $arFrontParametrs['SOCIAL_WHATS_CUSTOM'];

	if($this->StartResultCache(false, array(($arParams['CACHE_GROUPS'] === 'N'? false : $USER->GetGroups()), $arResult, $bUSER_HAVE_ACCESS, $arNavigation))){
		$this->SetResultCacheKeys(array(
			'SOCIAL_WHATS',
			'SOCIAL_WHATS_TEXT'
		));

		$this->IncludeComponentTemplate();
	}
}
?>
