<?php
/**
 * Created by PhpStorm.
 * User: Nikolay Starostenko
 * Date: 07.06.18
 * Time: 20:16
 */

namespace Netex\Export;

/**
 * Класс агентов
 *
 * Class Agent
 * @package Netex\Export
 */
class Agent
{
    /**
     * Обновляет контент измененных индексов
     *
     * @return string
     */
    public static function indexChanged()
    {
        $agent = '\Netex\Export\Agent::indexChanged();';

        $step = \COption::GetOptionString('netex.export', 'step_processing', '200');
        Export::getInstance()->generateContent($step);

        return $agent;
    }

    /**
     * Генерирует файл для профиля
     *
     * @param $id
     * @return string
     */
    public static function exportProfile($id) {
        Export::getInstance()->generateFile($id);

        return '\Netex\Export\Agent::exportProfile(' . $id . ');';
    }

    /**
     * Реиндексирует торговые предложения профиля
     *
     * @param $id
     * @return string
     */
    public static function indexProfile($id)
    {
        Export::getInstance()->reindex($id);

        \CAgent::AddAgent(
            '\Netex\Export\Agent::exportProfile(' . $id . ');',
            'netex.export',
            'Y',
            86400,
            date('d.m.Y H:i:s', strtotime('tomorrow midnight')),
            'Y',
            date('d.m.Y H:i:s', strtotime('tomorrow midnight'))
        );

        return '';
    }


}