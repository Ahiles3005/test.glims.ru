<?php
/**
 * Created by PhpStorm.
 * User: Nikolay Starostenko
 * Date: 01.06.18
 * Time: 13:05
 */

namespace Netex\Export\Orm;

use Bitrix\Main\Application;
use Bitrix\Main\Entity;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Type\DateTime;
use Bitrix\Main\Web\Json as BJson;

Loc::loadMessages(__FILE__);

/**
 * Класс описывает таблицу настроек экспорта
 *
 * Class SettingsTable
 * @package Netex\Export\Orm
 */
class ProfileTable extends Entity\DataManager
{
    /**
     * Вовзращает название таблицы
     *
     * @return string
     */
    public static function getTableName()
    {
        return 'netex_export_profile';
    }

    /**
     * Возвращает описание полей таблицы
     *
     * @return array
     */
    public static function getMap()
    {
        global $USER;
        return [
            new Entity\IntegerField('ID', [
                'title' => Loc::getMessage('NETEX_EXPORT_ORM_SETTINGS_FIELD_ID'),
                'autocomplete' => true,
                'primary' => true
            ]),
            new Entity\BooleanField('ACTIVE', [
                'title' => Loc::getMessage('NETEX_EXPORT_ORM_SETTINGS_FIELD_ACTIVE'),
                'values' => ['N', 'Y'],
                'default_value' => 'Y',
            ]),
            new Entity\StringField('NAME', [
                'title' => Loc::getMessage('NETEX_EXPORT_ORM_SETTINGS_FIELD_NAME'),
            ]),
            new Entity\StringField('DOMAIN', [
                'title' => Loc::getMessage('NETEX_EXPORT_ORM_SETTINGS_FIELD_DOMAIN'),
            ]),
            new Entity\BooleanField('HTTPS', [
                'title' => Loc::getMessage('NETEX_EXPORT_ORM_SETTINGS_FIELD_HTTPS'),
                'values' => [0, 1],
                'default_value' => 0,
            ]),
            new Entity\BooleanField('OFFERS', [
                'title' => Loc::getMessage('NETEX_EXPORT_ORM_SETTINGS_FIELD_OFFERS'),
                'values' => [0, 1],
                'default_value' => 0,
            ]),
            new Entity\IntegerField('IBLOCK_ID', [
                'title' => Loc::getMessage('NETEX_EXPORT_ORM_SETTINGS_FIELD_IBLOCK_ID'),
            ]),
            new Entity\TextField('HEADER_TEMPLATE', [
                'title' => Loc::getMessage('NETEX_EXPORT_ORM_SETTINGS_FIELD_HEADER_TEMPLATE'),
            ]),
            new Entity\TextField('OFFER_TEMPLATE', [
                'title' => Loc::getMessage('NETEX_EXPORT_ORM_SETTINGS_FIELD_OFFER_TEMPLATE'),
            ]),
            new Entity\TextField('FOOTER_TEMPLATE', [
                'title' => Loc::getMessage('NETEX_EXPORT_ORM_SETTINGS_FIELD_FOOTER_TEMPLATE'),
            ]),
            new Entity\TextField('SETTINGS', [
                'title' => Loc::getMessage('NETEX_EXPORT_ORM_SETTINGS_FIELD_SETTINGS'),
                'save_data_modification' => function() {
                    return [
                        function($value){
                            return serialize($value);
                        }
                    ];
                },
                'fetch_data_modification' => function() {
                    return [
                        function($value){
                            return unserialize($value);
                        }
                    ];
                }
            ]),
            new Entity\TextField('FILTER', [
                'title' => Loc::getMessage('NETEX_EXPORT_ORM_SETTINGS_FIELD_FILTER'),
                'save_data_modification' => function() {
                    return [
                        function($value){
                            return serialize($value);
                        }
                    ];
                },
                'fetch_data_modification' => function() {
                    return [
                        function($value){
                            return unserialize($value);
                        }
                    ];
                }
            ]),
            new Entity\TextField('SCHEDULER', [
                'title' => Loc::getMessage('NETEX_EXPORT_ORM_SETTINGS_FIELD_SCHEDULER'),
                'save_data_modification' => function() {
                    return [
                        function($value){
                            return serialize($value);
                        }
                    ];
                },
                'fetch_data_modification' => function() {
                    return [
                        function($value){
                            return unserialize($value);
                        }
                    ];
                }
            ]),
            new Entity\DatetimeField('DATE_CREATE', [
                'title' => Loc::getMessage('NETEX_EXPORT_ORM_SETTINGS_FIELD_DATE_CREATE'),
                'default_value' => new DateTime(),
            ]),
            new Entity\DatetimeField('DATE_UPDATE', [
                'title' => Loc::getMessage('NETEX_EXPORT_ORM_SETTINGS_FIELD_DATE_UPDATE'),
                'default_value' => new DateTime(),
            ]),
            new Entity\DatetimeField('DATE_GENERATE', [
                'title' => Loc::getMessage('NETEX_EXPORT_ORM_SETTINGS_FIELD_DATE_GENERATE'),
            ]),
            new Entity\IntegerField('USER_ID_CREATE', [
                'title' => Loc::getMessage('NETEX_EXPORT_ORM_SETTINGS_FIELD_USER_ID_CREATE'),
                'default_value' => $USER->GetID(),
            ]),
            new Entity\IntegerField('USER_ID_UPDATE', [
                'title' => Loc::getMessage('NETEX_EXPORT_ORM_SETTINGS_FIELD_USER_ID_UPDATE'),
                'default_value' => $USER->GetID(),
            ]),
        ];
    }

    /**
     * @param $primary
     * @param array $data
     * @return Entity\UpdateResult
     */
    public static function update($primary, $data)
    {
        global $USER;
        $data['USER_ID_UPDATE'] = $USER->GetID();

        return parent::update($primary, $data);
    }

    public static function onAfterAdd(Entity\Event $event)
    {
        $id = $event->getParameter('id');

        self::addIndexAgent($id);

        parent::onAfterAdd($event);
    }



    public static function onAfterUpdate(Entity\Event $event)
    {
        $id = $event->getParameter('id');

        self::addIndexAgent($id['ID']);

        parent::onAfterUpdate($event);
    }

    private static function addIndexAgent($id) {
        \CAgent::AddAgent(
            '\Netex\Export\Agent::indexProfile(' . $id . ');',
            'netex.export',
            'Y',
            86400,
            '',
            'Y',
            date('d.m.Y H:i:s', strtotime('today + 18 hours'))
        );
    }

    public static function onAfterDelete(Entity\Event $event)
    {
        $arFields = $event->getParameter('id');
        $agent = new \CAgent;

        Application::getConnection()->query('DELETE FROM ' . OffersTable::getTableName() . ' WHERE PROFILE_ID="' . $arFields['ID'] . '"');
        Application::getConnection()->query('DELETE FROM ' . ContentTable::getTableName() . ' WHERE PROFILE_ID="' . $arFields['ID'] . '"');

        $res = $agent::GetList([], ['NAME' => '%Profile(' . $arFields['ID'] . ');']);
        while ($arAgent = $res->Fetch()) {
            $agent::Delete($arAgent['ID']);
        }

        parent::onAfterDelete($event);
    }
}