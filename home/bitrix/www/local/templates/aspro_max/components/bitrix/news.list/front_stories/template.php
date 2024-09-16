<? if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die(); ?>
<? $this->setFrameMode(true); ?>
<? use \Bitrix\Main\Localization\Loc; ?>
<? if ($arResult['ITEMS']): ?>
    <?php $sectionIndex = 0; ?>
    <div class="content_wrapper_block front_stories ROUND"  data-sort='SORT' data-sort-order='ASC' data-sort2='ID' data-sort2-order='ASC'>
        <div class="maxwidth-theme only-on-front">
            <div class="top_block">
                <h3 class="title_block">Наши проекты</h3>
                <a href="/company/projects/" class="pull-right font_upper muted">Смотреть все проекты</a>
            </div>
            <div class="tab_slider_wrapp stories">
                <div
                    class="owl-carousel owl-theme owl-bg-nav loading_state short-nav hidden-dots visible-nav swipeignore"
                    data-plugin-options='{"nav": true, "margin":32,"autoplay": false, "dots": false, "marginMove": true, "loop": false, "responsive": {"0":{"items": 2, "autoWidth": true, "lightDrag": true, "margin":16},"601":{"items": 4, "autoWidth": false, "lightDrag": false, "margin":32},"768":{"items": 5},"992":{"items": 6}, "1200":{"items": 7}}}'>
                    <? foreach ($arResult['ITEMS'] as $i => $arItem): ?>
                        <?
                        // preview image
                        $arItemImage = (strlen($arItem['PREVIEW_PICTURE']['SRC']) ? $arItem['PREVIEW_PICTURE'] : $arItem['DETAIL_PICTURE']);
                        $arImage = ($arItemImage ? CFile::ResizeImageGet($arItemImage, array('width' => 200, 'height' => 200), BX_RESIZE_IMAGE_PROPORTIONAL_ALT, true) : array());
                        $imageSrc = ($arItemImage ? $arImage['src'] : '');

                        if (!$imageSrc) {
                            $imageSrc = SITE_TEMPLATE_PATH . '/images/svg/noimage_content.svg';
                        }
                        ?>
                        <a href="<?=$arItem["DETAIL_PAGE_URL"] ?>" class="item color-theme-hover"
                             data-iblock-id=<?= $arParams['IBLOCK_ID'] ?> data-section-id=<?= $arItem['ID'] ?>
                             data-index=<?= $sectionIndex ?>>
                            <div class="img">
                                <? if ($imageSrc): ?>
                                    <span class="lazy" data-src="<?= $imageSrc ?>"
                                          style="background-image:url(<?= \Aspro\Functions\CAsproMax::showBlankImg($imageSrc); ?>)"></span>
                                <? endif; ?>
                            </div>
                            <div class="name font_xs">
                                <?=$arItem["NAME"] ?>
                            </div>
                        </a>
                        <? $sectionIndex++; ?>
                    <? endforeach; ?>
                </div>
            </div>
        </div>
    </div>
<? endif; ?>
