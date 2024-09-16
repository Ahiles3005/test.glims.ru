<?php

use Bitrix\Main\Application;

class RewriteLinkForRegion
{
    public static function handle() {
        global $arRegion;
        if ($arRegion['PROPERTY_DEFAULT_VALUE'] !== 'Y') {
            $defaultRegion = \CIBlockElement::getList([], ['IBLOCK_ID' => $arRegion['IBLOCK_ID'], 'PROPERTY_DEFAULT_VALUE' => 'Y'],
                false,false, ['PROPERTY_MAIN_DOMAIN'])
                ->fetch()['PROPERTY_MAIN_DOMAIN_VALUE'];
            $app = Application::getInstance();
            $app->getContext()->getServer()->getRequestUri();
            $request = $app->getContext()->getRequest();

            if (
                preg_match('#^/where-to-buy/stores/([^/]+?)/$#i', ($request->getRequestedPageDirectory() . '/'))
                || preg_match('#^/company/projects/([^/]+?)/$#i', ($request->getRequestedPageDirectory() . '/'))
                || $request->getRequestUri() === '/blog/'
                || $request->getRequestUri() === '/company/news/'
				//|| $request->getRequestUri() === '/en/'
            )
            {
                LocalRedirect(($_SERVER['HTTPS'] ? 'https:' : 'http:') . '//'. $defaultRegion . $request->getRequestedPageDirectory() . '/');

            }
        }
    }
}
