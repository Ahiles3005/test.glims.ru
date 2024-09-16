<?php

use Bitrix\Main;
use Bitrix\Sale;
use Netex\Tools\Helpers\Iblock;




require_once __DIR__ . '/Netex/Helper.php';
require_once __DIR__ . '/classes/RewriteLinkForRegion.php';

// require_once $_SERVER['DOCUMENT_ROOT'] . '/../composer/vendor/autoload.php';

Main\EventManager::getInstance()->addEventHandler(
	'sale',
	'OnSaleOrderSaved',
	'setSendRoistatIdBitrix24'
);

function setSendRoistatIdBitrix24(Main\Event $event)
{
	if ($GLOBALS['ORDER_EVENT']) return;

	try {
		$id = $event->getParameter('ENTITY')->getId();

		\CAgent::AddAgent(
			'CustomAgent::sendRoistatIdBitrix24(' . $id . ');',
			'main',
			'N',
			60
		);
	} catch (\Exception $exception) {
	}
}

function sendBitrix24Request($url, array $data)
{
	$queryData = http_build_query($data);

	$curl = curl_init();
	curl_setopt_array($curl, [
		CURLOPT_SSL_VERIFYPEER => 0,
		CURLOPT_POST => 1,
		CURLOPT_HEADER => 0,
		CURLOPT_RETURNTRANSFER => 1,
		CURLOPT_URL => $url,
		CURLOPT_POSTFIELDS => $queryData,
	]);

	$result = curl_exec($curl);
	curl_close($curl);

	return json_decode($result, 1);
}

class CustomAgent
{
	public static function sendRoistatIdBitrix24($id, $try = 0)
	{
		$option = new Main\Config\Option();
		$main = $option::get('main', 'update_devsrv');
		$developer = $option::get('netex.tools', 'developer_mode');

		if ($main == 'Y' || $developer == 'Y') return '';
		if ($try++ > 30) return '';

		$code = 'w2ew2bjvtc1utd40';
		$domain = 'glimsfs.bitrix24.ru';
		$ufCode = 'UF_CRM_1542095545';
		$userId = 14;

		$url = 'https://' . $domain . '/rest/' . $userId . '/' . $code . '/';

		/** @var Sale\Order $order */
		$order = Sale\Order::load($id);

		$value = '';
		$dealId = $propertyDealId = 0;
		/** @var Sale\PropertyValue $propertyItem */
		foreach ($order->getPropertyCollection() as $propertyItem) {
			if ($propertyItem->getField('CODE') == 'ROISTAT_VISIT') {
				$value = $propertyItem->getValue();
			}

			if ($propertyItem->getField('CODE') == 'B24_DEAL_ID') {
				if ($propertyItem->getValue()) {
					$dealId = $propertyItem->getValue();
				} else {
					$propertyDealId = $propertyItem->getPropertyId();
				}
			}
		}

		$arFilter = ['TITLE' => 'EShop0 #' . $id];
		if ($dealId) {
			$arFilter = ['ID' => $dealId];
		}

		// Получение и обновление поля сделки
		$query = sendBitrix24Request($url . 'crm.deal.list.json', [
			'filter' => $arFilter,
			'select' => ['ID', $ufCode]
		]);


		if ($query['total'] == 1 && $query['result'][0]) {
			$fields = [];
			if ($query['result'][0][$ufCode] != $value) {
				$fields = [$ufCode => $value];
			}

			if (empty($dealId)) {
				$propertyItem = $order->getPropertyCollection()->getItemByOrderPropertyId($propertyDealId);

				$GLOBALS['ORDER_EVENT'] = true;
				$propertyItem->setValue($query['result'][0]['ID']);
				if ($propertyItem->save()->isSuccess()) {
					$fields['TITLE'] = 'EShop0 #' . $id . ' | Синхронизировано';
				}
				$GLOBALS['ORDER_EVENT'] = false;
			}


			if ($fields) {
				sendBitrix24Request($url . 'crm.deal.update.json', [
					'id' => $query['result'][0]['ID'],
					'fields' => $fields
				]);
			}

			return '';
		} else {
			return __METHOD__ . "($id, $try);";
		}
	}
}

AddEventHandler("iblock", "OnBeforeIBlockElementUpdate", Array("Reviews", "checkIblock"));
AddEventHandler("iblock", "OnBeforeIBlockElementUpdate", Array("Reviews", "onBeforeIBlockElementUpdateHandler"));
AddEventHandler("iblock", "OnAfterIBlockElementUpdate", Array("Reviews", "onAfterIBlockElementUpdateHandler"));


class Reviews
{
	public static $previewText;
	public static $iblock;

	function checkIblock(&$arParams)
	{
		$iblockId = Iblock::getIdByCode('reviews');
		if ($iblockId == $arParams['IBLOCK_ID']) {
			static::$iblock = true;
		}
	}

	function onBeforeIBlockElementUpdateHandler(&$arParams)
	{
		if (static::$iblock) {
			static::$previewText = CIBlockElement::GetList(
				[],
				[
					'ID' => $arParams['ID']
				],
				false,
				false,
				['PREVIEW_TEXT'])
				->Fetch();
		}
	}

	function onAfterIBlockElementUpdateHandler(&$arFields)
	{
		if (static::$previewText["PREVIEW_TEXT"] != $arFields["PREVIEW_TEXT"] && static::$iblock) {
			CIBlockElement::SetPropertyValuesEx($arFields['ID'], $arFields['IBLOCK_ID'], ['ANSWER_DATE' => date('d.m.Y - H:i:s')]);
		}
	}
}


/**
 * Обработчик события onAfterSetMetaTags модуля SEO Умного фильтра
 * На страницах пагинации тегированных страниц убирает каноникал url
 * В title закладки добавляет номер страницы
 *
 * @param $landing
 */
class CustomEvents
{
	public static function updatePageMetaTags($landing)
	{
		global $APPLICATION;
		$page = $_REQUEST['PAGEN_1'];

		if ($page > 1) {
			$title = $APPLICATION->GetTitle() . ' - страница №' . $page . ' | Интернет-магазин Glims.ru';
			$APPLICATION->SetPageProperty('canonical', '');
			$APPLICATION->SetPageProperty('title', $title);
			$APPLICATION->SetPageProperty('description', $title);
		}
	}
}

class ProductReviews
{
	public static function sendReview($ID, $arFields)
	{
		$fields = [
			'TOPIC_TITLE' => $arFields['TOPIC_INFO']['TITLE'],
			'AUTHOR' => $arFields['AUTHOR_NAME'],
			'MESSAGE_DATE' => $arFields['POST_DATE'],
			'MESSAGE_TEXT' => $arFields['POST_MESSAGE'],
		];
		$event = new \CEvent();
		$event->Send('NEW_FORUM_MESSAGE', 's1', $fields, 'N');
	}
}

function setParamIblock($type)
{
	if (SITE_ID == "s2") {
		$lang = "en";
	} else {
		$lang = "ru";
	}

	$iblocks = [
		"slider" => ["ru" => 90, "en" => 129],
		"slider_types" => ["ru" => 64, "en" => 103],
		"tabs" => ["ru" => 19, "en" => 25],
	];
	echo 19;
}

function getAbout($IBLOCK_ID){
global $arRegion;
	$res = CIBlockElement::GetList(Array(), array("=IBLOCK_ID"=>$IBLOCK_ID,"=PROPERTY_REGION"=>$arRegion["ID"]), false, Array(), array("ID"));
	while($ob = $res->GetNextElement()){
$arFields = $ob->GetFields();
return $arFields["ID"];
}
	return false;
}

Main\EventManager::getInstance()->addEventHandler('netex.seofilter', 'onAfterSetMetaTags', [CustomEvents::class, 'updatePageMetaTags']);
Main\EventManager::getInstance()->addEventHandler('forum', 'onAfterMessageAdd', [ProductReviews::class, "sendReview"]);

Main\EventManager::getInstance()->addEventHandler('main', 'OnBeforeEndBufferContent', [RewriteLinkForRegion::class, 'handle']);

