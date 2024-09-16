<?php
/**
 * Created by PhpStorm.
 * User: Nikolay Starostenko
 * Date: 01.06.18
 * Time: 15:59
 */

namespace Netex\Export;

use Bitrix\Main\Loader;
use Netex\Export\Orm\OffersTable;
use Netex\Export\Orm\ContentTable;

Loader::includeModule('catalog');
Loader::includeModule('iblock');

/**
 * Класс обработчиков событий
 *
 * Class Event
 * @package Netex\Export
 */
class Event
{
    /**
     * Обработчик события OnAfterIBlockElementUpdate
     * Добавляет флаг "изменен" измененному торговому предложению
     *
     * @param $arFields
     */
    public static function addChangedFlag($arFields)
    {
        $export = Export::getInstance();
        $arInfo = $export->iblockInfo;
        $iblock = new \CIBlockElement();

        if ($arFields['IBLOCK_ID'] == $arInfo['IBLOCK_ID']) { //Если иблок SCU
            foreach ($export->arProfiles as $arProfile) {
                OffersTable::setChanged([
                    'PROFILE_ID' => $arProfile['ID'],
                    'OFFER_ID' => $arFields['ID'],
                ]);
            }
        } elseif ($arFields['IBLOCK_ID'] == $arInfo['PRODUCT_IBLOCK_ID']) { //Если иблок товаров
            $res = $iblock::GetList([], [
                'IBLOCK_ID' => $arInfo['IBLOCK_ID'],
                'PROPERTY_CML2_LINK' => $arFields['ID'],
            ]);

            while ($arOffer = $res->Fetch()) {
                foreach ($export->arProfiles as $arProfile) {
                    if (!$arProfile['OFFERS']) {
                        OffersTable::setChanged([
                            'PROFILE_ID' => $arProfile['ID'],
                            'OFFER_ID' => 0,
                            'PRODUCT_ID' => $arFields['ID']
                        ]);
                    } else {
                        OffersTable::setChanged([
                            'PROFILE_ID' => $arProfile['ID'],
                            'OFFER_ID' => $arOffer['ID'],
                            'PRODUCT_ID' => $arFields['ID']
                        ]);
                    }
                }
            }
        }
    }

    /**
     * Обработчик события OnAfterIBlockElementAdd
     * Добавляет торговое предложение в индекс
     *
     * @param $arFields
     */
    public static function addOffer2Export($arFields)
    {
        $arInfo = Export::getInstance()->iblockInfo;
        $iblock = new \CIBlockElement();
        if ($arFields['IBLOCK_ID'] == $arInfo['IBLOCK_ID']) { //Если иблок SCU
            $arFilter = [
                'IBLOCK_ID' => $arInfo['PRODUCT_IBLOCK_ID'],
                'ID' => $arFields['PROPERTY_VALUES'][$arInfo['SKU_PROPERTY_ID']]['n0']['VALUE']
            ];
            $resIblockCatalog = $iblock::GetList([], $arFilter);

            if ($obj = $resIblockCatalog->GetNextElement()) {
                $arIblockCatalog = $obj->GetFields();
                $arIblockCatalog['PROPERTY_VALUES'] = $obj->GetProperties();

                $arProfileIds = [];
                foreach (Export::getInstance()->arProfiles as $arProfile) {
                    $arFilter = $arProfile['FILTER'];
                    $arFilter['ID'] = $arIblockCatalog['ID'];
                    if ($iblock::GetList([], $arFilter, false, false, ['ID'])->Fetch()) {
                        $arProfileIds[] = $arProfile['ID'];
                    }
                }

                foreach ($arProfileIds as $profileId) {

                }
            }
        } elseif ($arFields['IBLOCK_ID'] == $arInfo['PRODUCT_IBLOCK_ID']) { //Если иблок товаров
            $arFilter = [
                'IBLOCK_ID' => $arInfo['PRODUCT_IBLOCK_ID'],
                'ID' => $arFields['ID'],
            ];

            foreach (Export::getInstance()->arProfiles as $arProfile) {
                $arFilter = array_merge($arProfile['FILTER'], $arFilter);

                if ($iblock::GetList([], $arFilter, false, false, ['ID'])->Fetch()) {
                    OffersTable::add([
                        'PROFILE_ID' => $arProfile['ID'],
                        'PRODUCT_ID' => $arFields['ID'],
                        'OFFER_ID' => 0,
                    ]);
                }
            }
        }
    }

    /**
     * Обработчик события ProfileTable::onAfterAdd
     * Удаляет торговое предложение из индекса
     *
     * @param $arFields
     */
    public static function deleteOfferFromExport($arFields)
    {
        $arInfo = Export::getInstance()->iblockInfo;

        if ($arFields['IBLOCK_ID'] == $arInfo['IBLOCK_ID']) {
            $objOfferExport = OffersTable::getList(['filter' => ['OFFER_ID' => $arFields['ID']]]);
            while ($arOfferExport = $objOfferExport->fetch()) {
                OffersTable::delete($arOfferExport['ID']);
            }

            $objOfferContent = ContentTable::getList(['filter' => ['OFFER_ID' => $arFields['ID']]]);
            while ($arOfferContent = $objOfferContent->fetch()) {
                ContentTable::delete($arOfferContent['ID']);
            }
        } elseif ($arFields['IBLOCK_ID'] == $arInfo['PRODUCT_IBLOCK_ID']) { //Если иблок товаров
            $objOfferExport = OffersTable::getList(['filter' => ['PRODUCT_ID' => $arFields['ID'], 'OFFER_ID' => 0]]);
            while ($arOfferExport = $objOfferExport->fetch()) {
                OffersTable::delete($arOfferExport['ID']);
            }

            $objOfferContent = ContentTable::getList(['filter' => ['PRODUCT_ID' => $arFields['ID'], 'OFFER_ID' => 0]]);
            while ($arOfferContent = $objOfferContent->fetch()) {
                ContentTable::delete($arOfferContent['ID']);
            }
        }
    }

    public static function addChangedFlagFromProduct($id)
    {
        if (\CIBlockElement::GetList([], ['ID' => $id])->Fetch()['IBLOCK_ID'] == Export::getInstance()->iblockInfo['IBLOCK_ID']) {
            $arFilter = ['OFFER_ID' => $id];
        } else {
            $arFilter = ['PRODUCT_ID' => $id];
        }

        $res = OffersTable::getList(['filter' => $arFilter]);
        while ($row = $res->fetch()) {
            OffersTable::setChanged($row);
        }
    }
}