<?
	$arResult = CMax::getChilds($arResult);
global $arRegion;
if ($arRegion) {
    $defaultRegion = \CIBlockElement::getList([], ['IBLOCK_ID' => $arRegion['IBLOCK_ID'], 'PROPERTY_DEFAULT_VALUE' => 'Y'],
        false,false, ['PROPERTY_MAIN_DOMAIN'])
        ->fetch()['PROPERTY_MAIN_DOMAIN_VALUE'];
}

foreach($arResult as $key=>$arItem)
{
    if ($arRegion['PROPERTY_DEFAULT_VALUE'] !== 'Y' && isset($arItem['PARAMS']['TARGET'])) {
        $arResult[$key]['LINK'] = '//'.$defaultRegion . $arItem['LINK'];
    }
}
?>

