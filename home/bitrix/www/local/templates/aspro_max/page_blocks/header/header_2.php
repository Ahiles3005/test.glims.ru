<? if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die(); ?>
<?
global $arTheme, $arRegion, $bLongHeader, $bColoredHeader;
$arRegions = CMaxRegionality::getRegions();
if ($arRegion)
    $bPhone = ($arRegion['PHONES'] ? true : false);
else
    $bPhone = ((int)$arTheme['HEADER_PHONES'] ? true : false);
$logoClass = ($arTheme['COLORED_LOGO']['VALUE'] !== 'Y' ? '' : ' colored');
$bLongHeader = true;
$bColoredHeader = true;
?>
<div class="header-wrapper">
    <div class="logo_and_menu-row with-search">
        <div class="logo-row short paddings">
            <div class="maxwidth-theme">
                <div class="row">
                    <div class="col-md-12">
                        <div class="logo-block pull-left floated">
                            <div class="logo<?= $logoClass ?>">
                                <?= CMax::ShowLogo(); ?>
                            </div>
                        </div>

                        <? if ($arRegions): ?>
                            <div class="inline-block pull-left">
                                <div class="top-description no-title">
                                    <? \Aspro\Functions\CAsproMax::showRegionList(); ?>
                                </div>
                            </div>
                        <? endif; ?>
                        <div class="inline-block pull-left">
                            <div class="flex-language">
                                <div class="flex-language-link">RU</div>
                                <div class="flex-language-caret">/</div>
                                <div class="flex-language-link"><a href="https://<?= $_SERVER["SERVER_NAME"] ?>/en/">EN</a></div>
                            </div>
                        </div>
                        <div class="search_wrap pull-left">
                            <div class="search-block inner-table-block">
                                <? $APPLICATION->IncludeComponent(
                                    "bitrix:main.include",
                                    "",
                                    Array(
                                        "AREA_FILE_SHOW" => "file",
                                        "PATH" => SITE_DIR . "include/top_page/search.title.catalog.php",
                                        "EDIT_TEMPLATE" => "include_area.php",
                                        'SEARCH_ICON' => 'Y'
                                    )
                                ); ?>
                                <? $APPLICATION->IncludeComponent(
                                    "aspro:social.info.max.custom",
                                    ".default",
                                    array(
                                        "CACHE_TYPE" => "A",
                                        "CACHE_TIME" => "3600000",
                                        "CACHE_GROUPS" => "N",
                                        "TITLE_BLOCK" => "Мы в социальных сетях:",
                                        "COMPONENT_TEMPLATE" => ".default",
                                    ),
                                    false
                                ); ?>
                            </div>
                        </div>

                        <div class="right-icons pull-right wb">
                            <div class="pull-right">
                                <?= CMax::ShowBasketWithCompareLink('', 'big', '', 'wrap_icon wrap_basket baskets'); ?>
                            </div>

                            <div class="pull-right">
                                <div class="wrap_icon inner-table-block person">
                                    <?= CMax::showCabinetLink(true, true, 'big'); ?>
                                </div>
                            </div>
                        </div>

                        <div class="pull-right">
                            <div class="wrap_icon inner-table-block">
                                <div class="phone-block blocks">
                                    <? if ($bPhone):?>
                                        <? CMax::ShowHeaderPhones('no-icons'); ?>
                                    <? endif ?>
                                </div>
                                <div class="phone-block blocks header-email">
                                    <? $callbackExploded = explode(',', $arTheme['SHOW_CALLBACK']['VALUE']);
                                    if (in_array('HEADER', $callbackExploded)):?>
                                            <a href="mailto:zakaz@glims.ru" target="_blank" class="callback-block animate-load colored" >zakaz@glims.ru</a>
                                    <? endif; ?>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>
            </div>
        </div><? // class=logo-row?>
    </div>
    <div class="menu-row middle-block bg<?= strtolower($arTheme["MENU_COLOR"]["VALUE"]); ?>">
        <div class="maxwidth-theme">
            <div class="row">
                <div class="col-md-12">
                    <div class="menu-only">
                        <nav class="mega-menu sliced">
                            <? $APPLICATION->IncludeComponent("bitrix:main.include", ".default",
                                array(
                                    "COMPONENT_TEMPLATE" => ".default",
                                    "PATH" => SITE_DIR . "include/menu/menu." . ($arTheme["HEADER_TYPE"]["LIST"][$arTheme["HEADER_TYPE"]["VALUE"]]["ADDITIONAL_OPTIONS"]["MENU_HEADER_TYPE"]["VALUE"] == "Y" ? "top_catalog_wide" : "top") . ".php",
                                    "AREA_FILE_SHOW" => "file",
                                    "AREA_FILE_SUFFIX" => "",
                                    "AREA_FILE_RECURSIVE" => "Y",
                                    "EDIT_TEMPLATE" => "include_area.php"
                                ),
                                false, array("HIDE_ICONS" => "Y")
                            ); ?>
                        </nav>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="line-row visible-xs"></div>
</div>
