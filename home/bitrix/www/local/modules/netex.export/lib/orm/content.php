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

Loc::loadMessages(__FILE__);

/**
 * Класс описывает таблицу предложений для экспорта
 *
 * Class OffersTable
 * @package Netex\Export\Orm
 */
class ContentTable extends Entity\DataManager
{
    /**
     * Вовзращает название таблицы
     *
     * @return string
     */
    public static function getTableName()
    {
        return 'netex_export_content';
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
                'title' => Loc::getMessage('NETEX_EXPORT_ORM_CONTENT_FIELD_ID'),
                'autocomplete' => true,
                'primary' => true
            ]),
            new Entity\IntegerField('PROFILE_ID', [
                'title' => Loc::getMessage('NETEX_EXPORT_ORM_CONTENT_FIELD_PROFILE_ID'),
            ]),
            new Entity\IntegerField('OFFER_ID', [
                'title' => Loc::getMessage('NETEX_EXPORT_ORM_CONTENT_FIELD_OFFER_ID'),
            ]),
            new Entity\IntegerField('PRODUCT_ID', [
                'title' => Loc::getMessage('NETEX_EXPORT_ORM_CONTENT_FIELD_PRODUCT_ID'),
            ]),
            new Entity\TextField('CONTENT', [
                'title' => Loc::getMessage('NETEX_EXPORT_ORM_CONTENT_FIELD_CONTENT'),
            ]),
        ];
    }

    /**
     * @param array $data
     * @return Entity\AddResult|Entity\UpdateResult
     * @throws \Exception
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
            return parent::update($row['ID'], $data);
        }

        return parent::add($data);
    }
}