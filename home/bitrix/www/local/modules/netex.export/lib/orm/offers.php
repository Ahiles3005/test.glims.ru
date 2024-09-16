<?php
/**
 * Created by PhpStorm.
 * User: Nikolay Starostenko
 * Date: 01.06.18
 * Time: 13:05
 */

namespace Netex\Export\Orm;

use Bitrix\Main\Entity;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Type\DateTime;

Loc::loadMessages(__FILE__);

/**
 * Класс описывает таблицу предложений для экспорта
 *
 * Class OffersTable
 * @package Netex\Export\Orm
 */
class OffersTable extends Entity\DataManager
{
    /**
     * Вовзращает название таблицы
     *
     * @return string
     */
    public static function getTableName()
    {
        return 'netex_export_offers';
    }

    /**
     * Возвращает описание полей таблицы
     *
     * @return array
     */
    public static function getMap()
    {
        return [
            new Entity\IntegerField('ID', [
                'title' => Loc::getMessage('NETEX_EXPORT_ORM_OFFERS_FIELD_ID'),
                'autocomplete' => true,
                'primary' => true
            ]),
            new Entity\StringField('PROFILE_ID', [
                'title' => Loc::getMessage('NETEX_EXPORT_ORM_OFFERS_FIELD_PROFILE_ID'),
            ]),
            new Entity\StringField('PRODUCT_ID', [
                'title' => Loc::getMessage('NETEX_EXPORT_ORM_OFFERS_FIELD_PRODUCT_ID'),
            ]),
            new Entity\StringField('OFFER_ID', [
                'title' => Loc::getMessage('NETEX_EXPORT_ORM_OFFERS_FIELD_OFFER_ID'),
            ]),
            new Entity\BooleanField('CHANGED', [
                'title' => Loc::getMessage('NETEX_EXPORT_ORM_OFFERS_FIELD_OFFERS'),
                'values' => ['N', 'Y'],
                'default_value' => 'Y',
            ]),
            new Entity\DatetimeField('CHANGED_DATE', [
                'title' => Loc::getMessage('NETEX_EXPORT_ORM_OFFERS_FIELD_CHANGED_DATE'),
            ]),
            new Entity\DatetimeField('GENERATE_DATE', [
                'title' => Loc::getMessage('NETEX_EXPORT_ORM_OFFERS_FIELD_GENERATE_DATE'),
            ]),
        ];
    }

    /**
     * Добавляет или обновляет существующую запись
     *
     * @param array $data
     * @return Entity\AddResult|Entity\UpdateResult
     */
    public static function add($data)
    {
        $row = parent::getRow([
            'filter' => [
                'PROFILE_ID' => $data['PROFILE_ID'],
                'PRODUCT_ID' => $data['PRODUCT_ID'],
                'OFFER_ID' => $data['OFFER_ID'],
            ],
        ]);

        if ($row) {
            return parent::update($row['ID'], $row);
        }

        return parent::add($data);
    }

    /**
     * Указывает предложению, что оно было изменено
     *
     * @param array $data
     * @return Entity\UpdateResult|false
     */
    public static function setChanged($data)
    {
        $row = parent::getRow(['filter' => [$data]]);

        if ($row) {
            return parent::update($row['ID'], [
                'CHANGED' => 'Y',
                'CHANGED_DATE' => new DateTime(),
            ]);
        }

        return false;
    }
}