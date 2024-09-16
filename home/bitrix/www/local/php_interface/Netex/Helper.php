<?php

namespace Netex\Glims;

use Netex\Tools\Helpers\Iblock;

/**
 * Class Helper
 */
class Helper
{
    /**
     * @param $iblockId
     * @param $elementCode
     * @return mixed
     * @throws \Bitrix\Main\ArgumentException
     * @throws \Bitrix\Main\ObjectPropertyException
     * @throws \Bitrix\Main\SystemException
     */
    public static function getSectionIdByElementCode($iblockId, $elementCode)
    {
        $id = \Bitrix\Iblock\ElementTable::getList([
            'filter' => ["=IBLOCK_ID" => $iblockId, '=CODE' => $elementCode],
            'select' => ["IBLOCK_SECTION_ID"]
        ])->fetch()['IBLOCK_SECTION_ID'];

        return $id;
    }

    /**
     * @param $array
     * @param $iblockId
     * @return bool|string
     */
    public static function addReviews($array, $iblockId)
    {
        if ($array) {

            $addElement = new \CIBlockElement();

            $PROP = [];
            $PROP['NAME'] = $array['name'];
            $PROP['RATING'] = $array['number'];
            $PROP['MESSAGE'] = [
                'VALUE' => [
                    'TEXT' => $array['comment'],
                    'TYPE' => 'text'
                ]
            ];

            $arFields = [
                "IBLOCK_ID" => $iblockId,
                'NAME' => date('d.m.Y - H:i:s'),
                "PROPERTY_VALUES" => $PROP,
                "ACTIVE" => "N",
            ];

            $elementId = $addElement->Add($arFields);

            if ($addElement->LAST_ERROR) {
                $result = $addElement->LAST_ERROR;
            } else {
                \CEvent::Send(
                    'SEND_RESULT_FORM_REVIEWS',
                    's1',
                    [
                        'NAME' => $array['name'],
                        'RATING' => $array['number'],
                        'MESSAGE' => $array['comment']
                    ],
                    'N',
                    'SEND_RESULT_FORM_REVIEWS'
                );
                $result = $elementId;
            }
        }
        return $result;
    }
}
