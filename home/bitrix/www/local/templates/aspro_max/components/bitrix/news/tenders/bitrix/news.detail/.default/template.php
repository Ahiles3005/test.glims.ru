<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();
/** @var array $arParams */
/** @var array $arResult */
/** @global CMain $APPLICATION */
/** @global CUser $USER */
/** @global CDatabase $DB */
/** @var CBitrixComponentTemplate $this */
/** @var string $templateName */
/** @var string $templateFile */
/** @var string $templateFolder */
/** @var string $componentPath */
/** @var CBitrixComponent $component */
$this->setFrameMode(true);
?>
<div class="news-detail">
	<?if($arParams["DISPLAY_PICTURE"]!="N" && is_array($arResult["DETAIL_PICTURE"])):?>
		<img
			class="detail_picture"
			border="0"
			src="<?=$arResult["DETAIL_PICTURE"]["SRC"]?>"
			width="<?=$arResult["DETAIL_PICTURE"]["WIDTH"]?>"
			height="<?=$arResult["DETAIL_PICTURE"]["HEIGHT"]?>"
			alt="<?=$arResult["DETAIL_PICTURE"]["ALT"]?>"
			title="<?=$arResult["DETAIL_PICTURE"]["TITLE"]?>"
			/>
	<?endif?>
	<?if($arParams["DISPLAY_DATE"]!="N" && $arResult["DISPLAY_ACTIVE_FROM"]):?>
		<span class="news-date-time"><?=$arResult["DISPLAY_ACTIVE_FROM"]?></span>
	<?endif;?>
	<?if($arParams["DISPLAY_NAME"]!="N" && $arResult["NAME"]):?>
		<h3><?=$arResult["NAME"]?></h3>
	<?endif;?>
	<div class="tender_status"><?=$arResult['PROPERTIES']['STATUS']['VALUE']?></div>
    <div class="tender_date"><?=$arResult['PROPERTIES']['DATE']['VALUE']?></div>
    <div class="tender_contact_name">Контактное лицо:</div>
    <div class="tender_contact_wrapper">
        <?if ($arResult['PROPERTIES']['CONTACT_PHOTO']['VALUE']) {?>
            <img src="<?=CFile::GetPath($arResult['PROPERTIES']['CONTACT_PHOTO']['VALUE'])?>" class="">
        <?} else {?>
            <img src="/images/nothing.png" class="">
        <?}?>
        <div class="tender_contact_data">
            <div class=""><?=$arResult['PROPERTIES']['CONTACT_NAME']['VALUE']?></div>
            <div class=""><?=html_entity_decode($arResult['PROPERTIES']['CONTACT_CONTACTS']['VALUE'])?></div>
        </div>
    </div>
    <br/><br/>
	<?if($arParams["DISPLAY_PREVIEW_TEXT"]!="N" && ($arResult["FIELDS"]["PREVIEW_TEXT"] ?? '')):?>
		<p><?=$arResult["FIELDS"]["PREVIEW_TEXT"];unset($arResult["FIELDS"]["PREVIEW_TEXT"]);?></p>
	<?endif;?>
	<?if($arResult["NAV_RESULT"]):?>
		<?if($arParams["DISPLAY_TOP_PAGER"]):?><?=$arResult["NAV_STRING"]?><br /><?endif;?>
		<?echo $arResult["NAV_TEXT"];?>
		<?if($arParams["DISPLAY_BOTTOM_PAGER"]):?><br /><?=$arResult["NAV_STRING"]?><?endif;?>
	<?elseif($arResult["DETAIL_TEXT"] <> ''):?>
		<?echo $arResult["DETAIL_TEXT"];?>
	<?else:?>
		<?echo $arResult["PREVIEW_TEXT"];?>
	<?endif?>
	<br/>
	<div class="tender_contact_name">Документы:</div>
	<div class="tender_docs">
	    <?$index = 0;?>
	    <?foreach ($arResult['PROPERTIES']['DOCS']['VALUE'] as $doc) {?>
	       <a href="<?=CFile::GetPath($doc)?>">
	           <?if ($arResult['PROPERTIES']['DOCS_NAMES']['VALUE'][$index]) {?>
	               <?=$arResult['PROPERTIES']['DOCS_NAMES']['VALUE'][$index]?>
	           <?} else {?>
	               Скачать
	           <?}?>
	       </a>
	       <br/>
	       <?$index++?>
	    <?}?>
	</div>
	<div style="clear:both"></div>
	<br />
</div>