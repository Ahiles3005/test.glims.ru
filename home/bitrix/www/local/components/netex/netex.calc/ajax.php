<?php

namespace Netex\Calc\Component;

use Bitrix\Main\Web\Json;

define('PUBLIC_AJAX_MODE', true);
define('NO_KEEP_STATISTIC', 'Y');
define('NO_AGENT_STATISTIC', 'Y');
define('NOT_CHECK_PERMISSIONS', true);
define('BX_NO_ACCELERATOR_RESET', true);

require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/prolog_before.php");

try {
    $data = json_decode(file_get_contents("php://input"), true);
    if (!empty($data)) {
        Ajax::getElements($data);
    }
}  catch (\Exception $e) {
    echo Json::encode([
        'status' => 'fail',
        'code' => $e->getCode(),
        'message' => $e->getMessage()
    ]);
}

class Ajax
{
    public static function getElements($params)
    {
        $query = \Bitrix\Iblock\SectionTable::getList([
            'filter' => [
                'IBLOCK_ID' => 19,
            ],
            'select' => [
                'ID', 'NAME'
            ]
        ]);

        $arSections = [];
        while($row = $query->fetch()) {
            $arSections[$row['ID']] = $row;
        }

        $filter['IBLOCK_ID'] = 19;
        foreach ($params as $key => $item) {
            $key = '=PROPERTY_' . $key . '_VALUE';
            $filter[$key] = $item;
        }

        $elements = \CIBlockElement::GetList(
            [],
            $filter,
            false,
            false,
            [
                'ID',
                'NAME',
                'PREVIEW_TEXT',
                'PREVIEW_PICTURE',
                'PROPERTY_RASHOD_KG',
                'PROPERTY_QUANTITY_IN_PACKAGE',
                'IBLOCK_ID',
                'IBLOCK_SECTION_ID',
                'DETAIL_PAGE_URL',
                'DETAIL_PICTURE',
                'PROPERTY_PROP_159',
                'PROPERTY_PROP_2026',
                'PROPERTY_maksimalnaya_tolshchina_sloya_nalivnogo_pola_mm',
                'PROPERTY_minimalnaya_tolshchina_sloya_nalivnogo_pola_mm'
            ]
        );
        $sku = \CCatalogSku::GetInfoByProductIBlock($filter['IBLOCK_ID']);

        $arItems = [];
        $arOffers = [];
        while ($elem = $elements->GetNext()) {
            if ($filter['=PROPERTY_oblast_prim_VALUE'] == 'Для наружных работ'
                && $filter['=PROPERTY_vid_poverkhnosti_VALUE'] == 'Цоколь'
                && $filter['=PROPERTY_format_plitki_VALUE'] == 'До 60x60 см'
                && $elem['CODE'] == 'greyfix') {
                continue;
            }

            if ($filter['=PROPERTY_oblast_prim_VALUE'] == 'Для наружных работ'
                && $filter['=PROPERTY_vid_poverkhnosti_VALUE'] == 'Фасад'
                && $filter['=PROPERTY_format_plitki_VALUE'] == 'До 60x60 см'
                && $elem['CODE'] == 'greyfix') {
                continue;
            }

            if ($filter['=PROPERTY_oblast_prim_VALUE'] == 'Для внутренних работ'
                && $filter['=PROPERTY_vid_poverkhnosti_VALUE'] == 'Стена'
                && $filter['=PROPERTY_format_plitki_VALUE'] == 'До 60x60 см'
                && $elem['CODE'] == 'greyfix') {
                continue;
            }

            if ($sku) {
                $offers = \CIBlockElement::GetList(
                    [],
                    [
                        'IBLOCK_ID' => $sku['IBLOCK_ID'],
                        'PROPERTY_' . $sku['SKU_PROPERTY_ID'] => $elem['ID'],
                        'ACTIVE' => 'Y'
                    ],
                    false,
                    false,
                    [
                        'PROPERTY_SIZES',
                        'PROPERTY_VOLUME',
                        'NAME',
                        'CODE',
                        'DETAIL_PAGE_URL',
                        'ID'
                    ]
                );

                while ($offer = $offers->GetNext()) {
                    $arOffer = [
                        'ID' => $offer['EXTERNAL_ID'],
                        'SIZE' => $offer,
                        'PRICE' => \CPrice::GetBasePrice($offer['EXTERNAL_ID'])['PRICE'],
                        'VALUE' => 0,
                        'COMPARE' => false,
                        'WISH' => false
                    ];

                    $elem['OFFERS'][] = $arOffer['ID'];
                    $arOffers[$arOffer['ID']] = $arOffer;
                }
            }

            $elem['PREVIEW_PICTURE_SRC'] = \CFile::GetFileArray($elem['PREVIEW_PICTURE'])['SRC'];
            $elem['PRICE'] = \CPrice::GetBasePrice($elem['ID'])['PRICE'];
            $elem['VALUE'] = 0;
            $elem['WISH'] = false;
            $elem['COMPARE'] = false;
            $elem['IN_BASKET'] = false;

            if ($arSections[$elem['IBLOCK_SECTION_ID']]) {
                $arSections[$elem['IBLOCK_SECTION_ID']]['ITEMS'][] = $elem['ID'];
            }

            $arItems[$elem['ID']] = $elem;
        }

        foreach ($arSections as $key => &$section) {
            if (!isset($section['ITEMS'])) {
                unset($arSections[$key]);
            }
        }

       echo Json::encode([
           'SECTIONS' => $arSections,
           'ITEMS' => $arItems,
           'OFFERS' => $arOffers
       ]);
    }
}