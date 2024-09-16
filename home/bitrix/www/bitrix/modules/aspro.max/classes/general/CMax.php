<?
/**
 * Aspro:Max module
 */

if(!defined('ASPRO_MAX_MODULE_ID')){
	define('ASPRO_MAX_MODULE_ID', 'aspro.max');
}

use \Bitrix\Main\Application,
	\Bitrix\Main\Type\Collection,
	\Bitrix\Main\Loader,
	\Bitrix\Main\IO\File,
	\Bitrix\Main\Localization\Loc,
	\Bitrix\Main\Config\Option;

Loc::loadMessages(__FILE__);

// initialize module parametrs list and default values
include_once __DIR__.'/../../parametrs.php';
include_once __DIR__.'/../../presets.php';
include_once __DIR__.'/../../thematics.php';

class CMax{
    const partnerName	= 'aspro';
    const solutionName	= 'max';
    const templateName	= 'aspro_max';
	const moduleID		= ASPRO_MAX_MODULE_ID;
    const wizardID		= 'aspro:max';
	const devMode 		= false;

	public static $arParametrsList = array();
	public static $arPresetsList = array();
	public static $arThematicsList = array();
	private static $arMetaParams = array();

	function Check(){}

	public static function isSmartSeoInstalled() {
		static $result;

		return $result ?? ($result = (Loader::includeModule('aspro.smartseo') && Option::get(self::moduleID, 'IS_SMARTSEO_INSTALLED', 'Y', SITE_ID) === 'Y'));
	}

	public static function isPageSpeedTest(){
		return isset($_SERVER['HTTP_ACCEPT_LANGUAGE']) && strpos($_SERVER['HTTP_USER_AGENT'], 'Lighthouse') !== false;
	}

	public static function checkIndexBot(){
		static $result;

		if(!isset($result)){
			$result = self::isPageSpeedTest() && Option::get(self::moduleID, 'USE_PAGE_SPEED_OPTIMIZATION', 'Y', SITE_ID) === 'Y';
		}

		return $result;
	}

	public static function getVendorsPath(){
		return realpath(__DIR__.'/../../vendors');
	}

	public static function ShowPageType($type = 'indexblocks', $subtype = '', $template = '', $bRestart = '', $bLoadAjax = false){
		global $APPLICATION, $arTheme, $bShowSimple, $isIndex;
		$path = $_SERVER['DOCUMENT_ROOT'].SITE_TEMPLATE_PATH.'/page_blocks/'.$type.'_';
		$file = null;
		if(is_array($arTheme) && $arTheme)
		{
			switch($type):
				case 'page_contacts':
					$path = str_replace('//', '/', $_SERVER['DOCUMENT_ROOT'].'/'.SITE_DIR.'contacts/'.$type);
					$file = $path.'_'.$arTheme['PAGE_CONTACTS']['VALUE'].'.php';
					break;
				case 'search_title_component':
					$path = str_replace('//', '/', $_SERVER['DOCUMENT_ROOT'].'/'.SITE_DIR.'include/footer/');
					$file = $path.'site-search.php';
					break;
				case 'mainpage':
					if($bLoadAjax && !self::checkAjaxRequest()):?>
						<div class="content_wrapper_block"><div class="maxwidth-theme wide"></div></div>
					<?endif;?>
					<?if($bRestart && $subtype)
					{
						static::checkRestartBuffer(true, $subtype);
						$GLOBALS["NavNum"]=0;
					}

					if($template && $subtype)
					{
						$path = str_replace('//', '/', $_SERVER['DOCUMENT_ROOT'].'/'.SITE_DIR.'include/'.$type.'/components/'.$subtype.'/');
						$file = $path.$template.'.php';
					}
					else
					{
						$path = str_replace('//', '/', $_SERVER['DOCUMENT_ROOT'].'/'.SITE_DIR.'include/'.$type.'/comp_');
						$file = $path.$subtype.'.php';
					}
					break;
				case 'basket_component':
					$path = str_replace('//', '/', $_SERVER['DOCUMENT_ROOT'].'/'.SITE_DIR.'include/footer/');
					$file = $path.'site-basket.php';
					break;
				case 'auth_component':
					$path = str_replace('//', '/', $_SERVER['DOCUMENT_ROOT'].'/'.SITE_DIR.'include/footer/');
					$file = $path.'site-auth.php';
					break;
				case 'bottom_counter':
					$bIndexBot = self::checkIndexBot(); // is indexed yandex/google bot
					if(!$bIndexBot)
					{
						$path = str_replace('//', '/', $_SERVER['DOCUMENT_ROOT'].'/'.SITE_DIR.'include/');
						$file = $path.'invis-counter.php';
					}
					break;
				case 'page_width':
					$APPLICATION->SetAdditionalCSS(SITE_TEMPLATE_PATH.'/css/width-'.$arTheme['PAGE_WIDTH']['VALUE'].'.css');
					break;
				case 'h1_style':
					$APPLICATION->SetAdditionalCSS(SITE_TEMPLATE_PATH.'/css/h1-'.strToLower($arTheme['H1_STYLE']['VALUE']).'.css');
					break;
				case 'footer':
					if($bShowSimple) {
						$file = $_SERVER['DOCUMENT_ROOT'].SITE_TEMPLATE_PATH.'/page_blocks/'.$type.'_simple_'.(isset($arTheme['SIMPLE_BASKET']['DEPENDENT_PARAMS']) ? $arTheme['SIMPLE_BASKET']['DEPENDENT_PARAMS']['FOOTER_SIMPLE']['VALUE'] : $arTheme['FOOTER_SIMPLE']).'.php';
					} else {
						$file = $_SERVER['DOCUMENT_ROOT'].SITE_TEMPLATE_PATH.'/page_blocks/footer/'.$type.'_'.(isset($arTheme['FOOTER_TYPE']['VALUE']) && $arTheme['FOOTER_TYPE']['VALUE'] ? $arTheme['FOOTER_TYPE']['VALUE'] : $arTheme['FOOTER_TYPE']).'.php';
					}
					$APPLICATION->SetAdditionalCSS(SITE_TEMPLATE_PATH.'/css/footer.css');
					if($isIndex){
						\Aspro\Functions\CAsproMax::shemaOrganization();
					}
					break;
				case 'header':
					\Aspro\Max\Functions\Extensions::init('line_block');
					if($bShowSimple) {
						$file = $_SERVER['DOCUMENT_ROOT'].SITE_TEMPLATE_PATH.'/page_blocks/'.$type.'_simple_'.(isset($arTheme['SIMPLE_BASKET']['DEPENDENT_PARAMS']) ? $arTheme['SIMPLE_BASKET']['DEPENDENT_PARAMS']['HEADER_SIMPLE']['VALUE'] : $arTheme['HEADER_SIMPLE']).'.php';
					} else {
						$file = $_SERVER['DOCUMENT_ROOT'].SITE_TEMPLATE_PATH.'/page_blocks/header/'.$type.'_'.$arTheme['HEADER_TYPE']['VALUE'].'.php';
					}
					break;
				case 'header_fixed':
					if(!$bShowSimple) {
						$file = $path.$arTheme['TOP_MENU_FIXED']['DEPENDENT_PARAMS']['HEADER_FIXED']['VALUE'].'.php';
					}
					break;
				case 'header_mobile':
					if(!$bShowSimple) {
						$file = $path.$arTheme['HEADER_MOBILE']['VALUE'].'.php';
					}
					break;
				case 'header_mobile_menu':
					if(!$bShowSimple) {
						$file = $path.$arTheme['HEADER_MOBILE_MENU']['VALUE'].'.php';
					}
					break;
				case 'mega_menu':
					$template = isset($arTheme['MEGA_MENU']['VALUE']) && $arTheme['MEGA_MENU']['VALUE'] ? $arTheme['MEGA_MENU']['VALUE'] : '1';
					$file = $path.$template.'.php';
					break;
				case 'page_title':
					$file = $path.$arTheme['PAGE_TITLE']['VALUE'].'.php';
					break;
				case 'left_block':
					global $isShowIndexLeftBlock;
					$file = $path.$arTheme['LEFT_BLOCK']['VALUE'].'.php';
					if($isIndex && !$isShowIndexLeftBlock){
						$file = '';
					}
					if ($file) {
						$APPLICATION->SetAdditionalCSS(SITE_TEMPLATE_PATH.'/css/left_block_main_page.css');
					}
					break;
				default:
					global $arMainPageOrder;
					if(isset($arTheme['INDEX_TYPE']['SUB_PARAMS'][$arTheme['INDEX_TYPE']['VALUE']]))
					{
						$order = $arTheme["SORT_ORDER_INDEX_TYPE_".$arTheme["INDEX_TYPE"]["VALUE"]];
						if($order)
						{
							$arMainPageOrder = explode(",", $order);
							$arIndexList = array_keys($arTheme['INDEX_TYPE']['SUB_PARAMS'][$arTheme['INDEX_TYPE']['VALUE']]);
							$arNewBlocks = array_diff($arIndexList, $arMainPageOrder);
							if($arNewBlocks) {
								$arMainPageOrder = array_merge($arMainPageOrder, $arNewBlocks);
							}
							if(!in_array("BIG_BANNER_INDEX", $arMainPageOrder))
								array_unshift($arMainPageOrder, "BIG_BANNER_INDEX");
						}
						else
							$arMainPageOrder = array_keys($arTheme['INDEX_TYPE']['SUB_PARAMS'][$arTheme['INDEX_TYPE']['VALUE']]);
					}
					foreach(GetModuleEvents(self::moduleID, 'OnAsproShowPageType', true) as $arEvent) // event for manipulation arMainPageOrder
						ExecuteModuleEventEx($arEvent, array($arTheme, &$arMainPageOrder));

					if($arTheme['INDEX_TYPE']['VALUE'] == 'custom')
					{
						global $arSite, $isMenu, $isIndex, $isCabinet, $is404, $bBigBannersIndex, $bServicesIndex, $bPortfolioIndex, $bPartnersIndex, $bTeasersIndex, $bInstagrammIndex, $bReviewsIndex, $bConsultIndex, $bCompanyIndex, $bTeamIndex, $bNewsIndex, $bMapIndex, $bFloatBannersIndex, $bCatalogIndex, $bBlogIndex, $bActiveTheme, $bCatalogSectionsIndex;
						global $bBigBannersIndexClass, $bServicesIndexClass, $bPartnersIndexClass, $bTeasersIndexClass, $bFloatBannersIndexClass, $bPortfolioIndexClass, $bCatalogIndexClass,  $bBlogIndexClass, $bInstagrammIndexClass, $bReviewsIndexClass, $bConsultIndexClass, $bCompanyIndexClass, $bTeamIndexClass, $bNewsIndexClass, $bMapIndexClass, $bCatalogSectionsIndexClass;

						$bBigBannersIndex = $bServicesIndex = $bPortfolioIndex = $bPartnersIndex = $bTeasersIndex = $bInstagrammIndex = $bReviewsIndex = $bConsultIndex = $bCompanyIndex = $bTeamIndex = $bNewsIndex = true;
						$bMapIndex = $bFloatBannersIndex = $bCatalogIndex = $bBlogIndex = $bCatalogSectionsIndex = $bPortfolioIndex = true;

						$bBigBannersIndexClass = $bServicesIndexClass = $bPartnersIndexClass = $bTeasersIndexClass = $bFloatBannersIndexClass = $bPortfolioIndexClass = $bCatalogIndexClass = $bBlogIndexClass = $bInstagrammIndexClass = $bReviewsIndexClass = $bConsultIndexClass = $bCompanyIndexClass = $bTeamIndexClass = $bNewsIndexClass = $bMapIndexClass = $bCatalogSectionsIndexClass = '';
					}

					$path = str_replace('//', '/', $_SERVER['DOCUMENT_ROOT'].'/'.SITE_DIR.$type);
					$file = $path.'_'.$arTheme['INDEX_TYPE']['VALUE'].'.php';

					break;
			endswitch;

			if(!$subtype || ($subtype && (!$bLoadAjax || ($bLoadAjax && self::checkAjaxRequest()))))
			{
				if($file)
					@include_once $file;
			}

			if($bRestart && $subtype)
			{
				static::checkRestartBuffer(true, $subtype);
			}
		}
	}

	public static function formatJsName($name = ''){
		$name = str_replace('\\', '%99', $name); // replace symbol \
		return htmlspecialcharsbx($name);
	}

	public static function PrepareItemProps($arProps){
		if(is_array($arProps) && $arProps)
		{
			foreach($arProps as $PCODE => $arProperty)
			{
				if(in_array($PCODE, array('PERIOD', 'TITLE_BUTTON', 'LINK_BUTTON', 'REDIRECT', 'LINK_PROJECTS', 'LINK_REVIEWS', 'DOCUMENTS', 'FORM_ORDER', 'FORM_QUESTION', 'PHOTOPOS', 'TASK_PROJECT', 'PHOTOS', 'LINK_COMPANY', 'LINK_VACANCY', 'LINK_BLOG', 'LINK_LANDING', 'GALLEY_BIG', 'LINK_SERVICES', 'LINK_GOODS', 'LINK_STAFF', 'LINK_SALE', 'SERVICES', 'HIT', 'RECOMMEND', 'NEW', 'STOCK', 'VIDEO', 'VIDEO_YOUTUBE', 'CML2_ARTICLE', 'LINK_TIZERS', 'LINK_BRANDS', 'BRAND', 'POPUP_VIDEO','LINK_NEWS', 'SALE_NUMBER', 'SIDE_IMAGE_TYPE', 'SIDE_IMAGE', 'LINK_LANDINGS', 'EXPANDABLES', 'EXPANDABLES_FILTER', 'ASSOCIATED_FILTER', 'ASSOCIATED', 'LINK_PARTNERS', 'BLOG_POST_ID', 'BLOG_COMMENTS_CNT', 'HELP_TEXT', 'FORUM_TOPIC_ID', 'FORUM_MESSAGE_CNT', 'EXTENDED_REVIEWS_COUNT', 'EXTENDED_REVIEWS_RAITING')))
					unset($arProps[$PCODE]);
				elseif(!$arProperty['VALUE'])
					unset($arProps[$PCODE]);
			}
		}
		else
			$arProps = array();

		return $arProps;
	}

	static function ShowTopDetailBanner($arResult, $arParams){?>
		<?ob_start();?>
			<?$bg = ((isset($arResult['PROPERTIES']['BNR_TOP_BG']) && $arResult['PROPERTIES']['BNR_TOP_BG']['VALUE']) ? CFile::GetPath($arResult['PROPERTIES']['BNR_TOP_BG']['VALUE']) : '');

			$onHead = isset($arResult['PROPERTIES']['BNR_ON_HEADER']) && $arResult['PROPERTIES']['BNR_ON_HEADER']['VALUE_XML_ID'] == 'YES' ? true : false;
			$halfBlock = isset($arResult['PROPERTIES']['BNR_HALF_BLOCK']) && $arResult['PROPERTIES']['BNR_HALF_BLOCK']['VALUE_XML_ID'] == 'YES' ? true : false;
			$bShowBG = (isset($arResult['PROPERTIES']['BNR_TOP_IMG']) && $arResult['PROPERTIES']['BNR_TOP_IMG']['VALUE']);
			$title = ($arResult['IPROPERTY_VALUES'] && strlen($arResult['IPROPERTY_VALUES']['ELEMENT_PAGE_TITLE']) ? $arResult['IPROPERTY_VALUES']['ELEMENT_PAGE_TITLE'] : $arResult['NAME']);
			$text_color_style = ((isset($arResult['PROPERTIES']['CODE_TEXT']) && $arResult['PROPERTIES']['CODE_TEXT']['VALUE']) ? 'style="color:'.$arResult['PROPERTIES']['CODE_TEXT']['VALUE'].'"' : '');
			$bLanding = (isset($arResult['IS_LANDING']) && $arResult['IS_LANDING'] == 'Y');
			$dopText = isset($arResult['PROPERTIES']['BNR_DOP_TEXT']['VALUE']) && $arResult['PROPERTIES']['BNR_DOP_TEXT']['VALUE'];
			$bgHalfImage = $bShowBG && !$bg && $halfBlock;
			$bActiveImage = ($bShowBG ? CFile::GetPath($arResult['PROPERTIES']['BNR_TOP_IMG']['VALUE']) : '');

			$bShowOrderButton = ($arResult['DISPLAY_PROPERTIES']['FORM_ORDER']['VALUE_XML_ID'] == 'YES');

			?>
			<div class="banners-content">
				<div class="maxwidth-banner <?=($halfBlock ? 'half_block' : '');?> <?=($bShowBG && !$bg ? 'only-active-img' : '')?> <?=(!$bShowBG && $bg ? 'only-bg' : '')?> <?=($bg ? 'lazy' : '')?>" <?if($bg):?>data-src="<?=$bg;?>" style="background-image:url('<?=\Aspro\Functions\CAsproMax::showBlankImg($bg);?>');"<?endif;?> data-text_color="<?=($arResult["PROPERTIES"]["BNR_DARK_MENU_COLOR"]["VALUE"] != "Y" ? "light" : "");?>">
					<div class="row banner-wrapper">
						<div class="maxwidth-theme">
							<div class="col-md-<?=($bShowBG || $halfBlock ? 6 : 12);?> text item_block fadeInUp">
								<?if($dopText):?>
									<div class="dop-text font_upper_md darken" <?=$text_color_style;?>><?=$arResult['PROPERTIES']['BNR_DOP_TEXT']['VALUE'];?></div>
								<?endif;?>
								<h1 <?=$text_color_style;?>><?=((isset($arResult['PROPERTIES']['BANNER_TITLE']['VALUE']) && $arResult['PROPERTIES']['BANNER_TITLE']['VALUE']) ? $arResult['PROPERTIES']['BANNER_TITLE']['VALUE'] : $title);?></h1>
								<div class="intro-text muted777" <?=$text_color_style;?>>
									<?if($bLanding):?>
										<p><?=$arResult['PROPERTIES']['ANONS']['VALUE'];?></p>
									<?else:?>
										<?if(
											isset($arResult['PROPERTIES']['BANNER_DESCRIPTION']['VALUE']['TEXT']) && 
											$arResult['PROPERTIES']['BANNER_DESCRIPTION']['VALUE']['TEXT']
										):?>
											<p><?=$arResult['PROPERTIES']['BANNER_DESCRIPTION']['~VALUE']['TEXT'];?></p>
										<?else:?>
											<?if($arResult['PREVIEW_TEXT_TYPE'] == 'text'):?>
												<p><?=$arResult['FIELDS']['PREVIEW_TEXT'];?></p>
											<?else:?>
												<?=$arResult['FIELDS']['PREVIEW_TEXT'];?>
											<?endif;?>
										<?endif;?>
									<?endif;?>
								</div>
								<p class="buttons_block">
									<?if($bLanding):?>
										<?if($arResult['PROPERTIES']['BUTTON_TEXT']['VALUE']):?>
											<span>
												<span class="btn btn-default btn-lg scroll_btn"><?=$arResult['PROPERTIES']['BUTTON_TEXT']['VALUE'];?></span>
											</span>
										<?endif;?>
									<?else:?>
										<?if($bShowOrderButton):?>
											<span>
												<span class="btn btn-default animate-load" data-event="jqm" data-param-form_id="<?=($arParams["FORM_ID_ORDER_SERVISE"] ? $arParams["FORM_ID_ORDER_SERVISE"] : 'SERVICES');?>" data-name="order_services" data-autoload-service="<?=self::formatJsName($arResult['NAME']);?>" data-autoload-project="<?=self::formatJsName($arResult['NAME']);?>" data-autoload-product="<?=self::formatJsName($arResult['NAME']);?>"><span><?=(strlen($arParams['S_ORDER_SERVISE']) ? $arParams['S_ORDER_SERVISE'] : Loc::getMessage('S_ORDER_SERVISE'))?></span></span>
											</span>
										<?endif;?>

										<?if($arResult['DISPLAY_PROPERTIES']['FORM_QUESTION']['VALUE_XML_ID'] == 'YES'):?>
											<span>
												<span class="btn <?=($halfBlock && $bShowOrderButton ? 'btn-transparent-border-color' : 'btn-default')?> <?=($bShowOrderButton ? 'white' : '')?>  animate-load" data-event="jqm" data-param-form_id="ASK" data-autoload-need_product="<?=self::formatJsName($arResult['NAME']);?>" data-name="question"><span><?=(strlen($arParams['S_ASK_QUESTION']) ? $arParams['S_ASK_QUESTION'] : Loc::getMessage('S_ASK_QUESTION'))?></span></span>
											</span>
										<?endif;?>
									<?endif;?>
								</p>
							</div>
							<?if($bShowBG && !$bgHalfImage):?>
								<div class="col-md-6 hidden-xs hidden-sm img animated delay09 duration08 item_block fadeInUp">
									<div class="inner">
										<img class="lazy" src="<?=\Aspro\Functions\CAsproMax::showBlankImg($bActiveImage);?>" data-src="<?=$bActiveImage;?>" alt="<?=$title;?>" title="<?=$title;?>" draggable="false">
									</div>
								</div>
							<?elseif($bgHalfImage):?>
								<div class="col-md-6 img item_block half_bg_img lazy" data-src="<?=$bActiveImage;?>" style="background-image:url('<?=\Aspro\Functions\CAsproMax::showBlankImg($bActiveImage);?>');">
								</div>
							<?endif;?>
						</div>
					</div>
				</div>
			</div>
		<?$html = ob_get_contents();
			ob_end_clean();

			foreach(GetModuleEvents(self::moduleID, 'OnAsproShowTopDetailBanner', true) as $arEvent) // event for manipulation item delay and compare buttons
				ExecuteModuleEventEx($arEvent, array(&$html, $arResult, $arParams));

			echo $html;?>
	<?}

	static function utf8_substr_replace($original, $replacement, $position, $length){
		$startString = mb_substr($original, 0, $position, 'UTF-8');
		$endString = mb_substr($original, $position + $length, mb_strlen($original), 'UTF-8');

		$out = $startString.$replacement.$endString;

		return $out;
	}

	public static function GetBackParametrsValues($SITE_ID, $bFromStatic = true, $SITE_DIR = ''){
		static $arCacheValues;

		$SITE_DIR = strlen($SITE_DIR) ? $SITE_DIR : (defined('SITE_DIR') ? SITE_DIR : '');

		if(!isset($arCacheValues)){
			$arCacheValues = array();
		}

		if(!isset($arCacheValues[$SITE_ID])){
			$arCacheValues[$SITE_ID] = array();
		}

		$arValues =& $arCacheValues[$SITE_ID];

		if(!$bFromStatic){
			$arValues = array();
		}

		if(!$arValues){
			$arDefaultValues = $arNestedValues = array();
			$bNestedParams = false;

			// get site template
			$arTemplate = self::GetSiteTemplate($SITE_ID);

			// add custom values for PAGE_CONTACTS
			if(isset(self::$arParametrsList['SECTION']['OPTIONS']['PAGE_CONTACTS']['LIST']))
			{
				// get site dir
				$arSite = CSite::GetByID($SITE_ID)->Fetch();
				$siteDir = str_replace('//', '/', $arSite['DIR']).'/';
				if($arPageBlocks = self::GetIndexPageBlocks($_SERVER['DOCUMENT_ROOT'].$siteDir.'contacts', 'page_contacts_', ''))
				{
					foreach($arPageBlocks as $page => $value)
					{
						$value_ = str_replace('page_contacts_', '', $value);
						if(!isset(self::$arParametrsList['SECTION']['OPTIONS']['PAGE_CONTACTS']['LIST'][$value_]))
						{
							self::$arParametrsList['SECTION']['OPTIONS']['PAGE_CONTACTS']['LIST'][$value_] = array(
								'TITLE' => $value,
								'HIDE' => 'Y',
								'IS_CUSTOM' => 'Y',
							);
						}
					}
					if(!self::$arParametrsList['SECTION']['OPTIONS']['PAGE_CONTACTS']['DEFAULT'])
					{
						self::$arParametrsList['SECTION']['OPTIONS']['PAGE_CONTACTS']['DEFAULT'] = key(self::$arParametrsList['SECTION']['OPTIONS']['PAGE_CONTACTS']['LIST']);
					}
				}
			}

			// add custom values for INDEX_PAGE
			if(isset(self::$arParametrsList['INDEX_PAGE']['OPTIONS']['INDEX_TYPE']['LIST']))
			{
				// get site dir
				$arSite = CSite::GetByID($SITE_ID)->Fetch();
				$siteDir = str_replace('//', '/', $arSite['DIR']).'/';
				if($arPageBlocks = self::GetIndexPageBlocks($_SERVER['DOCUMENT_ROOT'].$siteDir, 'indexblocks_', ''))
				{
					foreach($arPageBlocks as $page => $value)
					{
						$value_ = str_replace('indexblocks_', '', $value);
						if(!isset(self::$arParametrsList['INDEX_PAGE']['OPTIONS']['INDEX_TYPE']['LIST'][$value_]))
						{
							self::$arParametrsList['INDEX_PAGE']['OPTIONS']['INDEX_TYPE']['LIST'][$value_] = array(
								'TITLE' => $value,
								'HIDE' => 'Y',
								'IS_CUSTOM' => 'Y',
							);
						}
					}
					if(!self::$arParametrsList['INDEX_PAGE']['OPTIONS']['INDEX_TYPE']['DEFAULT'])
					{
						self::$arParametrsList['INDEX_PAGE']['OPTIONS']['INDEX_TYPE']['DEFAULT'] = key(self::$arParametrsList['INDEX_PAGE']['OPTIONS']['INDEX_TYPE']['LIST']);
					}
				}
			}

			// add form values for web_forms section
			if(isset(self::$arParametrsList['WEB_FORMS']['OPTIONS']))
			{
				if (\Bitrix\Main\Loader::includeModule('form')) {
					$arFilter = array('ACTIVE' => 'Y');
					$resForms = CForm::GetList($by='s_sort', $order='ask', $arFilter, $is_filtered = true);

					while ($arForm = $resForms->Fetch()) {
						$arForms[$arForm['SID'].'_FORM'] = array(
							'TITLE' => $arForm['NAME'],
							'TYPE' => 'selectbox',
							'LIST' => array(
								'BITRIX' => GetMessage('USE_BITRIX_FORM'),
								'CRM' => GetMessage('USE_CRM_FORM'),
							),
							'DEFAULT' => 'BITRIX',
							'DEPENDENT_PARAMS' => array(
								'CRM_SCRIPT' => array(
									'TITLE' => GetMessage('CRM_SCRIPT_TITLE'),
									'TO_TOP' => 'Y',
									'TYPE' => 'includefile',
									'NO_EDITOR' => 'Y',
									'INCLUDEFILE' => '#SITE_DIR#include/forms/'.$arForm['SID'].'_FORM'.'.php',
									'CONDITIONAL_VALUE' => 'CRM',
									'THEME' => 'N',
								),
							),
						);
					}
				} else {
					$arSite = CSite::GetByID($SITE_ID)->Fetch();

					$fpServices = $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/' . self::partnerName . '.' . self::solutionName . '/install/wizards/' . self::partnerName . '/' . self::solutionName . '/site/services';
					$fpServiceUniversal = $fpServices . '/service_universal.php';
					$fpLang = $fpServices . '/form/lang/' . $arSite['LANGUAGE_ID'] . '/forms.php';

					if (file_exists($fpServiceUniversal) && file_exists($fpLang)) {
						@include $fpServiceUniversal;
						@include $fpLang;

						if (isset($arServices) && isset($arServices['form']) && isset($arServices['form']['STAGES'])) {
							$arFormList = $arServices['form']['STAGES'];
							
							if (count($arFormList)) {
								$formSIDRegex = "/[$]FORM_SID\s*=\s*[\"'](\S*)[\"'];/s";
								$arFormListData = [];

								foreach ($arFormList as $file) {
									$fpForm = $fpServices . '/form/' . $file;
									
									if (file_exists($fpForm)) {
										$formContent = @file_get_contents($fpForm);
										preg_match($formSIDRegex, $formContent, $matches);
	
										if (isset($matches[1])) {
											$formSID = $matches[1];
											$optionName =  $formSID . '_FORM';

											$arForms[$optionName] = array(
												'TITLE' => $MESS[$optionName . '_NAME'],
												'TYPE' => 'selectbox',
												'LIST' => array(
													'CRM' => GetMessage('USE_CRM_FORM'),
												),
												'DEFAULT' => 'CRM',
												'SET_DEFAULT' => 'Y',
												'DEPENDENT_PARAMS' => array(
													'CRM_SCRIPT' => array(
														'TITLE' => GetMessage('CRM_SCRIPT_TITLE'),
														'TO_TOP' => 'Y',
														'TYPE' => 'includefile',
														'NO_EDITOR' => 'Y',
														'INCLUDEFILE' => '#SITE_DIR#include/forms/' . $formSID . '_FORM.php',
														'THEME' => 'N',
													),
												),
											);
										}
										unset($matches);
									}
								}
							}
						}
					}
				}

				self::$arParametrsList['WEB_FORMS']['OPTIONS'] = $arForms;
			}

			// add basket file download templates
			if(isset(self::$arParametrsList['BASKET']['OPTIONS']['SHOW_DOWNLOAD_BASKET']['DEPENDENT_PARAMS']['BASKET_FILE_DOWNLOAD_TEMPLATE'])){
				ob_start();
				\CBitrixComponent::includeComponentClass('aspro:basket.file.max');
				ob_get_clean();
				if(class_exists('CAsproBasketFileMax')){
					self::$arParametrsList['BASKET']['OPTIONS']['SHOW_DOWNLOAD_BASKET']['DEPENDENT_PARAMS']['BASKET_FILE_DOWNLOAD_TEMPLATE']['LIST'] = CAsproBasketFileMax::getTemplatesList('/bitrix/templates/aspro_max');
				}
			}

			if($arTemplate && $arTemplate['PATH'])
			{
				// add custom values for PAGE_TILE
				if(isset(self::$arParametrsList['MAIN']['OPTIONS']['PAGE_TITLE']))
					self::Add2OptionCustomPageBlocks(self::$arParametrsList['MAIN']['OPTIONS']['PAGE_TITLE'], $arTemplate['PATH'].'/page_blocks/', 'page_title_');

				// add custom values for LEFT_BLOCK
				if(isset(self::$arParametrsList['MAIN']['OPTIONS']['LEFT_BLOCK']))
					self::Add2OptionCustomPageBlocks(self::$arParametrsList['MAIN']['OPTIONS']['LEFT_BLOCK'], $arTemplate['PATH'].'/page_blocks/', 'left_block_');

				// add custom values for TOP_MENU_FIXED
				if(isset(self::$arParametrsList['HEADER']['OPTIONS']['TOP_MENU_FIXED']['DEPENDENT_PARAMS']['HEADER_FIXED']))
					self::Add2OptionCustomPageBlocks(self::$arParametrsList['HEADER']['OPTIONS']['TOP_MENU_FIXED']['DEPENDENT_PARAMS']['HEADER_FIXED'], $arTemplate['PATH'].'/page_blocks/', 'header_fixed_');

				// add custom values for HEADER_TYPE
				if(isset(self::$arParametrsList['HEADER']['OPTIONS']['HEADER_TYPE']))
					self::Add2OptionCustomPageBlocks(self::$arParametrsList['HEADER']['OPTIONS']['HEADER_TYPE'], $arTemplate['PATH'].'/page_blocks/header/', 'header_');

				// add custom values for HEADER_SIMPLE_TYPE
				if(isset(self::$arParametrsList['BASKET']['OPTIONS']['SIMPLE_BASKET']['DEPENDENT_PARAMS']['HEADER_SIMPLE']))
					self::Add2OptionCustomPageBlocks(self::$arParametrsList['BASKET']['OPTIONS']['SIMPLE_BASKET']['DEPENDENT_PARAMS']['HEADER_SIMPLE'], $arTemplate['PATH'].'/page_blocks/', 'header_simple_');

				// add custom values for FOOTER_TYPE
				if(isset(self::$arParametrsList['FOOTER']['OPTIONS']['FOOTER_TYPE']))
					self::Add2OptionCustomPageBlocks(self::$arParametrsList['FOOTER']['OPTIONS']['FOOTER_TYPE'], $arTemplate['PATH'].'/page_blocks/footer/', 'footer_');

				// add custom values for HEADER_SIMPLE_TYPE
				if(isset(self::$arParametrsList['BASKET']['OPTIONS']['SIMPLE_BASKET']['DEPENDENT_PARAMS']['FOOTER_SIMPLE']))
					self::Add2OptionCustomPageBlocks(self::$arParametrsList['BASKET']['OPTIONS']['SIMPLE_BASKET']['DEPENDENT_PARAMS']['FOOTER_SIMPLE'], $arTemplate['PATH'].'/page_blocks/', 'footer_simple_');

				// add custom values for HEADER_MOBILE
				if(isset(self::$arParametrsList['MOBILE']['OPTIONS']['HEADER_MOBILE']))
					self::Add2OptionCustomPageBlocks(self::$arParametrsList['MOBILE']['OPTIONS']['HEADER_MOBILE'], $arTemplate['PATH'].'/page_blocks/', 'header_mobile_custom', 'custom');

				// add custom values for HEADER_MOBILE_MENU
				if(isset(self::$arParametrsList['MOBILE']['OPTIONS']['HEADER_MOBILE_MENU']))
					self::Add2OptionCustomPageBlocks(self::$arParametrsList['MOBILE']['OPTIONS']['HEADER_MOBILE_MENU'], $arTemplate['PATH'].'/page_blocks/', 'header_mobile_menu_');

				// add custom values for BLOG_PAGE
				if(isset(self::$arParametrsList['SECTION']['OPTIONS']['BLOG_PAGE'])){
					self::Add2OptionCustomComponentTemplatePageBlocks(self::$arParametrsList['SECTION']['OPTIONS']['BLOG_PAGE'], $arTemplate['PATH'].'/components/bitrix/news/blog');
				}

				// add custom values for PROJECTS_PAGE
				if(isset(self::$arParametrsList['SECTION']['OPTIONS']['PROJECTS_PAGE'])){
					self::Add2OptionCustomComponentTemplatePageBlocks(self::$arParametrsList['SECTION']['OPTIONS']['PROJECTS_PAGE'], $arTemplate['PATH'].'/components/bitrix/news/projects');
				}

				// add custom values for NEWS_PAGE
				if(isset(self::$arParametrsList['SECTION']['OPTIONS']['NEWS_PAGE'])){
					self::Add2OptionCustomComponentTemplatePageBlocks(self::$arParametrsList['SECTION']['OPTIONS']['NEWS_PAGE'], $arTemplate['PATH'].'/components/bitrix/news/news');
				}

				// add custom values for STAFF_PAGE
				if(isset(self::$arParametrsList['SECTION']['OPTIONS']['STAFF_PAGE'])){
					self::Add2OptionCustomComponentTemplatePageBlocks(self::$arParametrsList['SECTION']['OPTIONS']['STAFF_PAGE'], $arTemplate['PATH'].'/components/bitrix/news/staff');
				}

				// add custom values for PARTNERS_PAGE
				if(isset(self::$arParametrsList['SECTION']['OPTIONS']['PARTNERS_PAGE'])){
					self::Add2OptionCustomComponentTemplatePageBlocks(self::$arParametrsList['SECTION']['OPTIONS']['PARTNERS_PAGE'], $arTemplate['PATH'].'/components/bitrix/news/partners');
				}

				// add custom values for PARTNERS_PAGE_DETAIL
				if(isset(self::$arParametrsList['SECTION']['OPTIONS']['PARTNERS_PAGE_DETAIL'])){
					self::Add2OptionCustomComponentTemplatePageBlocksElement(self::$arParametrsList['SECTION']['OPTIONS']['PARTNERS_PAGE_DETAIL'], $arTemplate['PATH'].'/components/bitrix/news/partners');
				}

				// add custom values for CATALOG_PAGE_SECTIONS
				if(isset(self::$arParametrsList['CATALOG_PAGE']['OPTIONS']['CATALOG_PAGE_SECTIONS'])){
					self::Add2OptionCustomComponentTemplatePageBlocksSections(self::$arParametrsList['CATALOG_PAGE']['OPTIONS']['CATALOG_PAGE_SECTIONS'], $arTemplate['PATH'].'/components/bitrix/catalog/main');
				}

				// add custom values for CATALOG_PAGE_DETAIL
				if(isset(self::$arParametrsList['CATALOG_PAGE']['OPTIONS']['CATALOG_PAGE_DETAIL'])){
					self::Add2OptionCustomComponentTemplatePageBlocksElement(self::$arParametrsList['CATALOG_PAGE']['OPTIONS']['CATALOG_PAGE_DETAIL'], $arTemplate['PATH'].'/components/bitrix/catalog/main');
				}

				// add custom values for CATALOG_PAGE_LANDINGS
				if(isset(self::$arParametrsList['CATALOG_PAGE']['OPTIONS']['CATALOG_PAGE_LANDINGS'])){
					self::Add2OptionCustomComponentTemplatePageBlocksLandings(self::$arParametrsList['CATALOG_PAGE']['OPTIONS']['CATALOG_PAGE_LANDINGS'], $arTemplate['PATH'].'/components/bitrix/catalog/main');
				}

				// add custom values for USE_FAST_VIEW_PAGE_DETAIL
				if(isset(self::$arParametrsList['CATALOG_PAGE']['OPTIONS']['USE_FAST_VIEW_PAGE_DETAIL'])){
					self::Add2OptionCustomComponentTemplatePageBlocksElement(self::$arParametrsList['CATALOG_PAGE']['OPTIONS']['USE_FAST_VIEW_PAGE_DETAIL'], $arTemplate['PATH'].'/components/bitrix/catalog/main', 'FAST_VIEW_ELEMENT');
				}

				// add custom values for USE_FAST_VIEW_SERVICES
				if(isset(self::$arParametrsList['CATALOG_PAGE']['OPTIONS']['USE_FAST_VIEW_SERVICES'])){
					self::Add2OptionCustomComponentTemplatePageBlocksElement(self::$arParametrsList['CATALOG_PAGE']['OPTIONS']['USE_FAST_VIEW_SERVICES'], $arTemplate['PATH'].'/components/bitrix/news/services', 'FAST_VIEW_ELEMENT');
				}

				// add custom values for VACANCY_PAGE
				if(isset(self::$arParametrsList['SECTION']['OPTIONS']['VACANCY_PAGE'])){
					self::Add2OptionCustomComponentTemplatePageBlocks(self::$arParametrsList['SECTION']['OPTIONS']['VACANCY_PAGE'], $arTemplate['PATH'].'/components/bitrix/news/vacancy');
				}

				// add custom values for LICENSES_PAGE
				if(isset(self::$arParametrsList['SECTION']['OPTIONS']['LICENSES_PAGE'])){
					self::Add2OptionCustomComponentTemplatePageBlocks(self::$arParametrsList['SECTION']['OPTIONS']['LICENSES_PAGE'], $arTemplate['PATH'].'/components/bitrix/news/licenses');
				}
			}

			// remove search.title properties
			if (!Loader::includeModule('search')) {
				unset(self::$arParametrsList['MAIN']['OPTIONS']['TYPE_SEARCH']);
				unset(self::$arParametrsList['MAIN']['OPTIONS']['SEARCHTITLE_TOP_COUNT']);
				unset(self::$arParametrsList['MAIN']['OPTIONS']['SEARCHTITLE_ORDER']);
				unset(self::$arParametrsList['MAIN']['OPTIONS']['SEARCHTITLE_USE_LANGUAGE_GUESS']);
				unset(self::$arParametrsList['MAIN']['OPTIONS']['SEARCHTITLE_CHECK_DATES']);
				unset(self::$arParametrsList['MAIN']['OPTIONS']['SEARCHTITLE_SHOW_WHERE']);
				unset(self::$arParametrsList['MAIN']['OPTIONS']['SEARCHTITLE_CATALOG_CATS']);
				unset(self::$arParametrsList['MAIN']['OPTIONS']['SEARCHTITLE_SITE_CATS']);
			}

			if(self::$arParametrsList && is_array(self::$arParametrsList))
			{
				foreach(self::$arParametrsList as $blockCode => $arBlock)
				{
					if($arBlock['OPTIONS'] && is_array($arBlock['OPTIONS']))
					{
						foreach($arBlock['OPTIONS'] as $optionCode => $arOption)
						{
							if($arOption['TYPE'] !== 'note' && $arOption['TYPE'] !== 'includefile')
							{
								if($arOption['TYPE'] === 'array'){
									$itemsKeysCount = Option::get(self::moduleID, $optionCode, '0', $SITE_ID);
									if($arOption['OPTIONS'] && is_array($arOption['OPTIONS']))
									{
										for($itemKey = 0, $cnt = $itemsKeysCount + 1; $itemKey < $cnt; ++$itemKey)
										{
											$_arParameters = array();
											$arOptionsKeys = array_keys($arOption['OPTIONS']);
											foreach($arOptionsKeys as $_optionKey)
											{
												$arrayOptionItemCode = $optionCode.'_array_'.$_optionKey.'_'.$itemKey;
												$arValues[$arrayOptionItemCode] = Option::get(self::moduleID, $arrayOptionItemCode, '', $SITE_ID);
												$arDefaultValues[$arrayOptionItemCode] = $arOption['OPTIONS'][$_optionKey]['DEFAULT'];
											}
										}
									}
									$arValues[$optionCode] = $itemsKeysCount;
									$arDefaultValues[$optionCode] = 0;
								}
								else
								{
									if($arOption['TYPE'] == 'multiselectbox') {
										$arValues[$optionCode] = Option::getRealValue(self::moduleID, $optionCode, $SITE_ID);
									} else {
										$arValues[$optionCode] = Option::get(self::moduleID, $optionCode, $arOption['DEFAULT'], $SITE_ID);
									}
									$arDefaultValues[$optionCode] = $arOption['DEFAULT'];

									if(isset($arOption['ADDITIONAL_OPTIONS']) && $arOption['ADDITIONAL_OPTIONS']) //get additional params default value
									{
										if($arOption['LIST'])
										{
											foreach($arOption['LIST'] as $key => $arListOption)
											{
												if(isset($arListOption['ADDITIONAL_OPTIONS']) && $arListOption['ADDITIONAL_OPTIONS'])
												{
													foreach($arListOption['ADDITIONAL_OPTIONS'] as $key2 => $arListOption2)
													{
														if(isset($arListOption2['LIST']) && $arListOption2['LIST'])
														{
															foreach($arListOption2['LIST'] as $key3 => $arListOption3)
															{
																$arDefaultValues[$key2.'_'.$key] = $arListOption2['DEFAULT'];
																$arValues[$key2.'_'.$key] = Option::get(self::moduleID, $key2.'_'.$key, $arListOption2['DEFAULT'], $SITE_ID);
															}
														}
														elseif($arListOption2['TYPE'] == 'checkbox')
														{
															$arDefaultValues[$key2.'_'.$key] = $arListOption2['DEFAULT'];
															$arValues[$key2.'_'.$key] = Option::get(self::moduleID, $key2.'_'.$key, $arListOption2['DEFAULT'], $SITE_ID);
														}
													}
												}
											}
										}
									}
									if(isset($arOption['SUB_PARAMS']) && $arOption['SUB_PARAMS']) //get nested params default value
									{
										if($arOption['TYPE'] == 'selectbox' && (isset($arOption['LIST'])) && $arOption['LIST'])
										{
											$bNestedParams = true;
											$arNestedValues[$optionCode] = $arOption['LIST'];
											foreach($arOption['LIST'] as $key => $value)
											{
												if($arOption['SUB_PARAMS'][$key])
												{
													foreach($arOption['SUB_PARAMS'][$key] as $key2 => $arSubOptions)
													{
														$arDefaultValues[$key.'_'.$key2] = $arSubOptions['DEFAULT'];

														//set fon index components
														if(isset($arSubOptions['FON']) && $arSubOptions['FON'])
														{
															$code_tmp = 'fon'.$key.$key2;

															$arDefaultValues[$code_tmp] = $arSubOptions['FON'];
															$arValues[$code_tmp] = Option::get(self::moduleID, $code_tmp, $arSubOptions['FON'], $SITE_ID);
														}

														//set default template index components
														if(isset($arSubOptions['TEMPLATE']) && $arSubOptions['TEMPLATE'])
														{
															$code_tmp = $key.'_'.$key2.'_TEMPLATE';
															$arDefaultValues[$code_tmp] = $arSubOptions['TEMPLATE']['DEFAULT'];
															$arValues[$code_tmp] = Option::get(self::moduleID, $code_tmp, $arSubOptions['TEMPLATE']['DEFAULT'], $SITE_ID);

															if($arSubOptions['TEMPLATE']['LIST'])
															{
																foreach($arSubOptions['TEMPLATE']['LIST'] as $keyS => $arListOption)
																{
																	if(isset($arListOption['ADDITIONAL_OPTIONS']) && $arListOption['ADDITIONAL_OPTIONS'])
																	{
																		foreach($arListOption['ADDITIONAL_OPTIONS'] as $keyS2 => $arListOption2)
																		{
																			if(isset($arListOption2['LIST']) && $arListOption2['LIST'])
																			{
																				// foreach($arListOption2['LIST'] as $keyS3 => $arListOption3)
																				// {
																					$arDefaultValues[$key.'_'.$key2.'_'.$keyS2.'_'.$keyS] = $arListOption2['DEFAULT'];
																					$arTmpSubOption = unserialize(Option::get(self::moduleID, 'N_O_'.$optionCode.'_'.$key.'_'.$key2.'_', serialize(array()), $SITE_ID));
																					if($arTmpSubOption)
																						$arValues[$key.'_'.$key2.'_'.$keyS2.'_'.$keyS] = $arTmpSubOption[$key2.'_'.$keyS2.'_'.$keyS];
																					else
																						$arValues[$key.'_'.$key2.'_'.$keyS2.'_'.$keyS] = $arListOption2['DEFAULT'];
																				// }
																			}
																			elseif($arListOption2['TYPE'] == 'checkbox')
																			{
																				$arDefaultValues[$key.'_'.$key2.'_'.$keyS2.'_'.$keyS] = $arListOption2['DEFAULT'];

																				$arTmpSubOption = unserialize(Option::get(self::moduleID, 'N_O_'.$optionCode.'_'.$key.'_'.$key2.'_', serialize(array()), $SITE_ID));
																				if($arTmpSubOption)
																					$arValues[$key.'_'.$key2.'_'.$keyS2.'_'.$keyS] = $arTmpSubOption[$key2.'_'.$keyS2.'_'.$keyS];
																				else
																					$arValues[$key.'_'.$key2.'_'.$keyS2.'_'.$keyS] = Option::get(self::moduleID, $key.'_'.$key2.'_'.$keyS2.'_'.$keyS, $arListOption2['DEFAULT'], $SITE_ID);
																			}
																		}
																	}
																}
															}
														}
													}

													//sort order prop for main page
													$param = 'SORT_ORDER_'.$optionCode.'_'.$key;
													$arValues[$param] = Option::get(self::moduleID, $param, '', $SITE_ID);
													$arDefaultValues[$param] = '';
												}
											}
										}

									}

									if(isset($arOption['DEPENDENT_PARAMS']) && $arOption['DEPENDENT_PARAMS']) //get dependent params default value
									{
										foreach($arOption['DEPENDENT_PARAMS'] as $key => $arSubOption)
										{
											$arDefaultValues[$key] = $defaultValue = $arSubOption['DEFAULT'] ?? null;
											$arValues[$key] = Option::get(self::moduleID, $key, $defaultValue, $SITE_ID);
										}
									}

									elseif($optionCode === 'USE_PHONE_AUTH')
									{
										list($bPhoneAuthSupported, $bPhoneAuthShow, $bPhoneAuthRequired, $bPhoneAuthUse) = Aspro\Max\PhoneAuth::getOptions();
										if(!$bPhoneAuthSupported || !$bPhoneAuthShow){
											self::$arParametrsList[$blockCode]['OPTIONS'][$optionCode]['DISABLED'] = 'Y';
										}
									}
								}
							}
						}
					}
				}
			}

			if($arNestedValues && $bNestedParams) //get nested params bd value
			{
				foreach($arNestedValues as $key => $arAllValues)
				{
					$arTmpValues = array();
					foreach($arAllValues as $key2 => $arOptionValue)
					{
						$arTmpValues = unserialize(Option::get(self::moduleID, 'NESTED_OPTIONS_'.$key.'_'.$key2, serialize(array()), $SITE_ID));
						if($arTmpValues)
						{
							foreach($arTmpValues as $key3 => $value)
							{
								$arValues[$key2.'_'.$key3] = $value;
							}
						}
					}

				}
			}

			if($arValues && is_array($arValues))
			{
				foreach($arValues as $optionCode => $arOption)
				{
					if(!isset($arDefaultValues[$optionCode]))
						unset($arValues[$optionCode]);
				}
			}
			if($arDefaultValues && is_array($arDefaultValues))
			{
				foreach($arDefaultValues as $optionCode => $arOption)
				{
					if(!isset($arValues[$optionCode]))
						$arValues[$optionCode] = $arOption;
				}
			}


			foreach($arValues as $key => $value)
			{
				if($key == 'LOGO_IMAGE' || $key == 'LOGO_IMAGE_WHITE' || $key == 'FAVICON_IMAGE' || $key == 'APPLE_TOUCH_ICON_IMAGE'){
					$arValue = unserialize(Option::get(self::moduleID, $key, serialize(array()), $SITE_ID));
					$arValue = (array)$arValue;
					$fileID = $arValue ? current($arValue) : false;

					if($key === 'FAVICON_IMAGE'){
						if($fileID){
							$faviconFile = CFIle::GetPath($fileID);
							$file_ext = pathinfo($faviconFile, PATHINFO_EXTENSION);
							$fav_ext = $file_ext ? $file_ext : 'ico';						
							$arValues[$key] = str_replace('//', '/', $SITE_DIR.'/favicon.'.$file_ext);
						} else {
							$arValues[$key] = str_replace('//', '/', $SITE_DIR.'/favicon.ico');
						}
					}
						

					if($fileID)
					{
						if($key !== 'FAVICON_IMAGE')
							$arValues[$key] = CFIle::GetPath($fileID);
					}
					else
					{
						if($key === 'APPLE_TOUCH_ICON_IMAGE')
							$arValues[$key] = str_replace('//', '/', $SITE_DIR.'/include/apple-touch-icon.png');
						elseif($key === 'LOGO_IMAGE')
						{
							if(file_exists(str_replace('//', '/', $_SERVER['DOCUMENT_ROOT'].$SITE_DIR.'logo.svg')))
								$arValues[$key] = str_replace('//', '/', $_SERVER['DOCUMENT_ROOT'].$SITE_DIR.'logo.svg');
							else
								$arValues[$key] = str_replace('//', '/', $SITE_DIR.'/logo.png');
						}
					}

					if(!file_exists(str_replace('//', '/', $_SERVER['DOCUMENT_ROOT'].$arValues[$key])))
					{
						$arValues[$key] = '';
					}

				}
			}

			// replace #SITE_DIR#
			if(!defined('ADMIN_SECTION'))
			{
				if($arValues && is_array($arValues))
				{
					foreach($arValues as $optionCode => $arOption)
					{
						if(!is_array($arOption))
							$arValues[$optionCode] = str_replace('#SITE_DIR#', $SITE_DIR, $arOption);
					}
				}
			}
		}

		return $arValues;
	}

	public static function GetFrontParametrsValues($SITE_ID = SITE_ID, $SITE_DIR = SITE_DIR){
		if(!strlen($SITE_ID))
			$SITE_ID = SITE_ID;

		if(!strlen($SITE_DIR))
			$SITE_DIR = SITE_DIR;

		$arBackParametrs = self::GetBackParametrsValues($SITE_ID, true, $SITE_DIR);
		if($arBackParametrs['THEME_SWITCHER'] === 'Y' && isset($_SESSION['THEME'][$SITE_ID]))
			$arValues = array_merge((array)$arBackParametrs, (array)$_SESSION['THEME'][$SITE_ID]);
		else
			$arValues = (array)$arBackParametrs;

		// global flag for OnEndBufferContentHandler
		$GLOBALS['_USE_LAZY_LOAD_MAX_'] = $arValues['USE_LAZY_LOAD'] === 'Y';

		return $arValues;
	}

	public static function GetFrontParametrValue($optionCode, $SITE_ID=SITE_ID, $bStatic = true){
		static $arFrontParametrs;

		if(!isset($arFrontParametrs) || !$bStatic)
			$arFrontParametrs = self::GetFrontParametrsValues($SITE_ID);

		return $arFrontParametrs[$optionCode];
	}

	public static function CheckColor($strColor){
		$strColor = substr(str_replace('#', '', $strColor), 0, 6);
		$strColor = base_convert(base_convert($strColor, 16, 2), 2, 16);
		for($i = 0, $l = 6 - (function_exists('mb_strlen') ? mb_strlen($strColor) : strlen($strColor)); $i < $l; ++$i)
			$strColor = '0'.$strColor;
		return $strColor;
	}

	public static function GetUserID(){
		static $userID;
		if($userID === NULL)
		{
			global $USER;
			$userID = $USER->GetID();
			$userID = ($userID > 0 ? $userID : 0);
		}
		return $userID;
	}

	public static function getCurrentThematic($SITE_ID = false){
		if(!strlen($SITE_ID)){
			$SITE_ID = SITE_ID;
		}

		return Option::get(self::moduleID, 'THEMATIC', 'UNIVERSAL', $SITE_ID);
	}

	private static function options_replace($arA, $arB){
		if(is_array($arA) && is_array($arB)){
			foreach($arA as $key => $value){
				if(array_key_exists($key, $arB)){
					if(is_array($value)){
						$arA[$key] = self::options_replace($arA[$key], $arB[$key]);
					}
					else{
						$arA[$key] = $arB[$key];
					}
				}
			}
		}
		else{
			$arA = $arB;
		}

		return $arA;
	}

	public static function getCurrentPreset($SITE_ID = false){
		static $arCurPresets;

		if(!isset($arCurPresets)){
			$arCurPresets = array();
		}

		if(!strlen($SITE_ID)){
			$SITE_ID = SITE_ID;
		}

		if(!isset($arCurPresets[$SITE_ID])){
			$arCurPresets[$SITE_ID] = false;

			$arPresets = array();
			if(strlen($curThematic = self::getCurrentThematic($SITE_ID))){
				if(self::$arThematicsList && self::$arThematicsList[$curThematic]){
					$arPresets = self::$arPresetsList;
					foreach($arPresets as $id => &$arPreset){
						if(in_array($id, self::$arThematicsList[$curThematic]['PRESETS']['LIST'])){
							if(self::$arThematicsList[$curThematic]['OPTIONS'] && is_array(self::$arThematicsList[$curThematic]['OPTIONS'])){
								$arPreset['OPTIONS'] = self::options_replace($arPreset['OPTIONS'], self::$arThematicsList[$curThematic]['OPTIONS']);
							}
						}
						else{
							unset($arPresets[$id]);
						}
					}
					unset($arPreset);
				}
			}

			if($arPresets){
				$arFrontParametrs = self::GetFrontParametrsValues($SITE_ID);

				foreach(self::$arParametrsList as $blockCode => $arBlock){
					foreach($arBlock['OPTIONS'] as $optionCode => $arOption){
						if(isset($arOption['THEME']) && $arOption['THEME'] === 'Y'){
							foreach($arPresets as $id => &$arPreset){
								if($arPreset['OPTIONS']){
									if(array_key_exists($optionCode, $arPreset['OPTIONS'])){
										$presetValue = $arPreset['OPTIONS'][$optionCode];

										if(array_key_exists($optionCode, $arFrontParametrs)){
											if(is_array($presetValue)){
												if(array_key_exists('VALUE', $presetValue)){
													if($arFrontParametrs[$optionCode] != $presetValue['VALUE']){
														unset($arPresets[$id]);
														continue;
													}
												}

												if(is_array($presetValue['ADDITIONAL_OPTIONS'])){
													// check only additional values of current option value
													if(is_array($presetValue['ADDITIONAL_OPTIONS'][$presetValue['VALUE']])){
														foreach($presetValue['ADDITIONAL_OPTIONS'][$presetValue['VALUE']] as $subAddOptionCode => $subAddOptionValue){
															if(isset($arFrontParametrs[$subAddOptionCode.'_'.$presetValue['VALUE']])){
																if($arFrontParametrs[$subAddOptionCode.'_'.$presetValue['VALUE']] != $subAddOptionValue){
																	unset($arPresets[$id]);
																	continue 2;
																}
															}
														}
													}
												}

												if(is_array($presetValue['SUB_PARAMS'])){
													foreach($presetValue['SUB_PARAMS'] as $subOptionCode => $subValue){
														if(isset($arFrontParametrs[$presetValue['VALUE'].'_'.$subOptionCode])){
															if(is_array($subValue)){
																if(array_key_exists('VALUE', $subValue)){
																	if($arFrontParametrs[$presetValue['VALUE'].'_'.$subOptionCode] != $subValue['VALUE']){
																		unset($arPresets[$id]);
																		continue 2;
																	}

																	if(array_key_exists('TEMPLATE', $subValue) && array_key_exists($presetValue['VALUE'].'_'.$subOptionCode.'_TEMPLATE', $arFrontParametrs)){
																		if($arFrontParametrs[$presetValue['VALUE'].'_'.$subOptionCode.'_TEMPLATE'] != $subValue['TEMPLATE']){
																			unset($arPresets[$id]);
																			continue 2;
																		}

																		if(array_key_exists('ADDITIONAL_OPTIONS', $subValue)){
																			foreach($subValue['ADDITIONAL_OPTIONS'] as $addSubOptionTemplateCode => $addSubOptionTemplateValue){
																				if(array_key_exists($presetValue['VALUE'].'_'.$subOptionCode.'_'.$addSubOptionTemplateCode.'_'.$subValue['TEMPLATE'], $arFrontParametrs)){
																					if($arFrontParametrs[$presetValue['VALUE'].'_'.$subOptionCode.'_'.$addSubOptionTemplateCode.'_'.$subValue['TEMPLATE']] != $addSubOptionTemplateValue){
																						unset($arPresets[$id]);
																						continue 3;
																					}
																				}
																			}
																		}
																	}

																	if(array_key_exists('FON', $subValue) && array_key_exists('fon'.$presetValue['VALUE'].$subOptionCode, $arFrontParametrs)){
																		if($arFrontParametrs['fon'.$presetValue['VALUE'].$subOptionCode] != $subValue['FON']){
																			unset($arPresets[$id]);
																			continue 2;
																		}
																	}
																}
															}
															else{
																if($arFrontParametrs[$presetValue['VALUE'].'_'.$subOptionCode] != $subValue){
																	unset($arPresets[$id]);
																	continue 2;
																}
															}
														}
													}
												}

												if(is_array($presetValue['DEPENDENT_PARAMS'])){
													foreach($presetValue['DEPENDENT_PARAMS'] as $depOptionCode => $depValue){
														if(isset($arFrontParametrs[$depOptionCode])){
															if($arFrontParametrs[$depOptionCode] != $depValue){
																unset($arPresets[$id]);
																continue 2;
															}
														}
													}
												}

												if(array_key_exists('ORDER', $presetValue)){
													if(isset($arFrontParametrs['SORT_ORDER_'.$optionCode.'_'.$presetValue['VALUE']])){
														if($arFrontParametrs['SORT_ORDER_'.$optionCode.'_'.$presetValue['VALUE']] != $presetValue['ORDER']){
															unset($arPresets[$id]);
															continue;
														}
													}
												}
											}
											else{
												if($arFrontParametrs[$optionCode] != $presetValue){
													unset($arPresets[$id]);
													continue;
												}
											}
										}
									}
								}
								else{
									unset($arPresets[$id]);
									continue;
								}
							}
							unset($arPreset);
						}
					}
				}
			}

			if($arPresets){
				return $arCurPresets[$SITE_ID] = key($arPresets);
			}
		}

		return $arCurPresets[$SITE_ID];
	}

	public static function setFrontParametrsOfPreset($presetId, $SITE_ID = false){
		if(($presetId = intval($presetId) > 0 ? intval($presetId) : false) > 0){
			if(!strlen($SITE_ID)){
				$SITE_ID = SITE_ID;
			}

			if(strlen($curThematic = self::getCurrentThematic($SITE_ID))){
				if(self::$arThematicsList && self::$arThematicsList[$curThematic]){

					if(in_array($presetId, self::$arThematicsList[$curThematic]['PRESETS']['LIST'])){
						if($arPreset = self::$arPresetsList[$presetId]){
							$arPreset['OPTIONS'] = self::options_replace($arPreset['OPTIONS'], self::$arThematicsList[$curThematic]['OPTIONS']);

							if($arPreset['OPTIONS']){
								foreach($arPreset['OPTIONS'] as $optionCode => $optionVal){
									if(!is_array($optionVal)){
										$_SESSION['THEME'][$SITE_ID][$optionCode] = $optionVal;

										if($optionCode === 'BASE_COLOR' && $optionVal === 'CUSTOM'){
											Option::set(self::moduleID, 'NeedGenerateCustomTheme', 'Y', $SITE_ID);
										}

										if($optionCode === 'CUSTOM_BGCOLOR_THEME' && $optionVal === 'CUSTOM'){
											Option::set(self::moduleID, 'NeedGenerateCustomThemeBG', 'Y', $SITE_ID);
										}
									}
									else{
										if(array_key_exists('VALUE', $optionVal)){
											$_SESSION['THEME'][$SITE_ID][$optionCode] = $optionVal['VALUE'];
										}

										if(array_key_exists('ADDITIONAL_OPTIONS', $optionVal) && $optionVal['ADDITIONAL_OPTIONS']){
											foreach($optionVal['ADDITIONAL_OPTIONS'] as $addOptionValue => $arAddOption){
												foreach($arAddOption as $subAddOptionCode => $subAddOptionVal){
													$_SESSION['THEME'][$SITE_ID][$subAddOptionCode.'_'.$addOptionValue] = $subAddOptionVal;
												}
											}
										}

										if(array_key_exists('SUB_PARAMS', $optionVal) && $optionVal['SUB_PARAMS']){
											$propValue = $optionVal['VALUE'];
											foreach($optionVal['SUB_PARAMS'] as $subOptionCode => $arSubOption){
												if(is_array($arSubOption)){
													if(array_key_exists('VALUE', $arSubOption)){
														$_SESSION['THEME'][$SITE_ID][$propValue.'_'.$subOptionCode] = $arSubOption['VALUE'];
													}

													if(array_key_exists('FON', $arSubOption)){
														$_SESSION['THEME'][$SITE_ID]['fon'.$propValue.$subOptionCode] = $arSubOption['FON'];
													}

													if(array_key_exists('TEMPLATE', $arSubOption)){
														$_SESSION['THEME'][$SITE_ID][$propValue.'_'.$subOptionCode.'_TEMPLATE'] = $arSubOption['TEMPLATE'];

														if(array_key_exists('ADDITIONAL_OPTIONS', $arSubOption)){
															foreach($arSubOption['ADDITIONAL_OPTIONS'] as $addSubOptionTemplateCode => $addSubOptionTemplateValue){
																$_SESSION['THEME'][$SITE_ID][$propValue.'_'.$subOptionCode.'_'.$addSubOptionTemplateCode.'_'.$arSubOption['TEMPLATE']] = $addSubOptionTemplateValue;
															}
														}
													}
												}
												else{
													$_SESSION['THEME'][$SITE_ID][$propValue.'_'.$subOptionCode] = $arSubOption;
												}
											}

											if(array_key_exists('ORDER', $optionVal)){
												$_SESSION['THEME'][$SITE_ID]['SORT_ORDER_'.$optionCode.'_'.$propValue] = $optionVal['ORDER'];
											}
										}

										if(array_key_exists('DEPENDENT_PARAMS', $optionVal) && $optionVal['DEPENDENT_PARAMS']){
											foreach($optionVal['DEPENDENT_PARAMS'] as $depOptionCode => $depOptionVal){
												$_SESSION['THEME'][$SITE_ID][$depOptionCode] = $depOptionVal;
											}
										}
									}
								}
							}

							return true;
						}
					}
				}
			}
		}

		return false;
	}

	public static function setBackParametrsOfPreset($presetId, $SITE_ID = false){
		if(($presetId = intval($presetId) > 0 ? intval($presetId) : false) > 0){
			if(!strlen($SITE_ID)){
				$SITE_ID = SITE_ID;
			}

			unset($_SESSION['THEME'][$SITE_ID]);
			$arSeted = array();

			if(strlen($curThematic = self::getCurrentThematic($SITE_ID))){
				if(self::$arThematicsList && self::$arThematicsList[$curThematic]){

					if(in_array($presetId, self::$arThematicsList[$curThematic]['PRESETS']['LIST'])){
						if($arPreset = self::$arPresetsList[$presetId]){
							$arPreset['OPTIONS'] = self::options_replace($arPreset['OPTIONS'], self::$arThematicsList[$curThematic]['OPTIONS']);

							if($arPreset['OPTIONS']){
								foreach($arPreset['OPTIONS'] as $optionCode => $optionVal){
									if(!is_array($optionVal)){
										Option::set(self::moduleID, $optionCode, $optionVal, $SITE_ID);

										if($optionCode === 'BASE_COLOR' && $optionVal === 'CUSTOM'){
											Option::set(self::moduleID, 'NeedGenerateCustomTheme', 'Y', $SITE_ID);
										}

										if($optionCode === 'CUSTOM_BGCOLOR_THEME' && $optionVal === 'CUSTOM'){
											Option::set(self::moduleID, 'NeedGenerateCustomThemeBG', 'Y', $SITE_ID);
										}
									}
									else{
										if(array_key_exists('VALUE', $optionVal)){
											Option::set(self::moduleID, $optionCode, $optionVal['VALUE'], $SITE_ID);
										}

										if(array_key_exists('ADDITIONAL_OPTIONS', $optionVal) && $optionVal['ADDITIONAL_OPTIONS']){
											foreach($optionVal['ADDITIONAL_OPTIONS'] as $addOptionValue => $arAddOption){
												foreach($arAddOption as $subAddOptionCode => $subAddOptionVal){
													Option::set(self::moduleID, $subAddOptionCode.'_'.$addOptionValue, $subAddOptionVal, $SITE_ID);
												}
											}
										}

										if(array_key_exists('SUB_PARAMS', $optionVal) && $optionVal['SUB_PARAMS']){
											$propValue = $optionVal['VALUE'];
											$arSubValues = array();
											foreach($optionVal['SUB_PARAMS'] as $subOptionCode => $arSubOption){
												if(is_array($arSubOption)){
													if(array_key_exists('VALUE', $arSubOption)){
														$arSubValues[$propValue.'_'.$subOptionCode] = $arSubOption['VALUE'];
													}

													if(array_key_exists('FON', $arSubOption)){
														Option::set(self::moduleID, 'fon'.$propValue.$subOptionCode, $arSubOption['FON'], $SITE_ID);
													}

													if(array_key_exists('TEMPLATE', $arSubOption)){
														Option::set(self::moduleID, $propValue.'_'.$subOptionCode.'_TEMPLATE', $arSubOption['TEMPLATE'], $SITE_ID);

														if(array_key_exists('ADDITIONAL_OPTIONS', $arSubOption)){
															$arTmpDopConditions = array();
															foreach($arSubOption['ADDITIONAL_OPTIONS'] as $addSubOptionTemplateCode => $addSubOptionTemplateValue){
																$arTmpDopConditions[$subOptionCode.'_'.$addSubOptionTemplateCode.'_'.$arSubOption['TEMPLATE']] = $addSubOptionTemplateValue;
															}

															if($arTmpDopConditions){
																Option::set(self::moduleID, 'N_O_'.$optionCode.'_'.$propValue.'_'.$subOptionCode.'_', serialize($arTmpDopConditions), $SITE_ID);
															}
														}
													}
												}
												else{
													$arSubValues[$propValue.'_'.$subOptionCode] = $arSubOption;
												}
											}

											if($arSubValues){
												Option::set(self::moduleID, 'NESTED_OPTIONS_'.$optionCode.'_'.$propValue, serialize($arSubValues), $SITE_ID);
											}

											if(array_key_exists('ORDER', $optionVal)){
												Option::set(self::moduleID, 'SORT_ORDER_'.$optionCode.'_'.$propValue, $optionVal['ORDER'], $SITE_ID);
											}
										}

										if(array_key_exists('DEPENDENT_PARAMS', $optionVal) && $optionVal['DEPENDENT_PARAMS']){
											foreach($optionVal['DEPENDENT_PARAMS'] as $depOptionCode => $depOptionVal){
												Option::set(self::moduleID, $depOptionCode, $depOptionVal, $SITE_ID);
											}
										}
									}
								}
							}

							return true;
						}
					}
				}
			}
		}

		return false;
	}

	public static function ShowCabinetLink($icon=true, $text=true, $class_icon='', $show_mess=false, $message=''){
		global $APPLICATION, $arTheme;
		static $hauth_call;

		if (!isset($hauth_call)) {
			$hauth_call = 0;
		}
		$iCalledID = ++$hauth_call;

		$type_svg = '';
		if($class_icon)
		{
			$tmp = explode(' ', $class_icon);
			$type_svg = '_'.$tmp[0];
		}
		$userID = self::GetUserID();
		$html = '<!-- noindex --><div class="auth_wr_inner '.($userID && $text ? 'with_dropdown' : '').'">';
		if(!$message)
			$message = Loc::getMessage('CABINET_LINK');

		if($userID)
		{
			global $USER;
			$name = ($USER->GetFullName() ? $USER->GetFullName() : $USER->GetLogin());

			$html .= '<a rel="nofollow" title="'.$name.'" class="personal-link dark-color logined'.($text ? /*' with_dropdown'*/ '' : '').'" href="'.$arTheme['PERSONAL_PAGE_URL']['VALUE'].'">';
			if($icon)
				$html .= self::showSpriteIconSvg(SITE_TEMPLATE_PATH."/images/svg/header_icons_srite.svg#user_login", "svg-inline-cabinet ".$class_icon, ['WIDTH' => 16,'HEIGHT' => 18]);
			if($text)
				$html .= '<span class="wrap">';

				if ($text)
					$html .= '<span class="name">'.Loc::getMessage('CABINET_LINK').'</span>';
				if($show_mess)
					$html .= '<span class="title">'.$message.'</span>';

			if($text)
				$html .= '</span>';

			$html .= '</a>';
			if($text)
				$html .= self::showSpriteIconSvg(SITE_TEMPLATE_PATH."/images/svg/header_icons_srite.svg#Triangle_down", "downs ".$class_icon, ['WIDTH' => 5,'HEIGHT' => 3]);?>
			<?ob_start();?>
			<?$APPLICATION->IncludeComponent(
				"bitrix:menu",
				"cabinet_dropdown",
				Array(
					"COMPONENT_TEMPLATE" => "cabinet_dropdown",
					"MENU_CACHE_TIME" => "3600000",
					"MENU_CACHE_TYPE" => "A",
					"MENU_CACHE_USE_GROUPS" => "Y",
					"MENU_CACHE_GET_VARS" => array(
					),
					"DELAY" => "N",
					"MAX_LEVEL" => "4",
					"ALLOW_MULTI_SELECT" => "Y",
					"ROOT_MENU_TYPE" => "cabinet",
					"CHILD_MENU_TYPE" => "left",
					"USE_EXT" => "Y"
				),
				array("HIDE_ICONS" => "Y")
			);?>
			<?$html .= ob_get_contents();
			ob_end_clean();?>
			<?
		}
		else
		{
			$url = ((isset($_GET['backurl']) && $_GET['backurl']) ? $_GET['backurl'] : $APPLICATION->GetCurUri());
			$html .= '<a rel="nofollow" title="'.Loc::getMessage('CABINET_LINK').'" class="personal-link dark-color animate-load" data-event="jqm" data-param-backurl="'.htmlspecialcharsbx($url).'" data-param-type="auth" data-name="auth" href="'.$arTheme['PERSONAL_PAGE_URL']['VALUE'].'">';
			if($icon)
				$html .= self::showSpriteIconSvg(SITE_TEMPLATE_PATH."/images/svg/header_icons_srite.svg#user", "svg-inline-cabinet $class_icon", ['WIDTH' => 18,'HEIGHT' => 18]);
			if($text)
				$html .= '<span class="wrap">';

				if($text)
					$html .= '<span class="name">'.Loc::getMessage('LOGIN').'</span>';
				if($show_mess)
					$html .= '<span class="title">'.$message.'</span>';
			if($text)
				$html .= '</span>';

			$html .= '</a>';
		}
		$html .= '</div><!-- /noindex -->';?>

		<?Bitrix\Main\Page\Frame::getInstance()->startDynamicWithID('header-auth-block'.$iCalledID);?>
			<?=$html;?>
		<?Bitrix\Main\Page\Frame::getInstance()->finishDynamicWithID('header-auth-block'.$iCalledID);?>

	<?}

	public static function showContactPhones($txt = '', $wrapTable = true, $class = '', $icon = 'Phone_black2.svg', $subclass = ''){
		static $cphones_call;
		global $arRegion, $APPLICATION;

		if (!isset($cphones_call)) {
			$cphones_call = 0;
		}
		$iCalledID = ++$cphones_call;

		$iCountPhones = ($arRegion ? count($arRegion['PHONES']) : self::checkContentFile(SITE_DIR.'include/contacts-site-phone-one.php'));
		$bRegionContact = (\Bitrix\Main\Config\Option::get(self::moduleID, 'SHOW_REGION_CONTACT', 'N') == 'Y');

		if($arRegion){
			$frame = new \Bitrix\Main\Page\FrameHelper('header-allcphones-block'.$iCalledID);
			$frame->begin();
		}
		?>
		<?if($iCountPhones):?>
			<div class="property phone">
				<div class="title font_upper muted"><?=($txt ? $txt : Loc::getMessage('SPRAVKA'));?></div>
				<?if($arRegion && $bRegionContact):?>
					<div class="<?=($class ? ' '.$class : '')?>">
						<?for($i = 0; $i < $iCountPhones; ++$i):?>
							<?
							$phone = ($arRegion ? $arRegion['PHONES'][$i]['PHONE'] : $arBackParametrs['HEADER_PHONES_array_PHONE_VALUE_'.$i]);
							$href = ($arRegion ? $arRegion['PHONES'][$i]['HREF'] : $arBackParametrs['HEADER_PHONES_array_PHONE_HREF_'.$i]);
							if(!strlen($href)){
								$href = 'javascript:;';
							}

							$description = ($arRegion ? $arRegion['PROPERTY_PHONES_DESCRIPTION'][$i] : $arBackParametrs['HEADER_PHONES_array_PHONE_DESCRIPTION_'.$i]);
							$description = (!empty($description)) ? 'title="' . $description . '"' : '';
							?>
							<div class="value darken" itemprop="telephone"><a <?=$description?> href="<?=$href?>"><?=$phone?></a></div>
						<?endfor;?>
					</div>
				<?else:?>
					<div class="value darken" itemprop="telephone"><?$APPLICATION->IncludeFile(SITE_DIR."include/contacts-site-phone-one.php", Array(), Array("MODE" => "html", "NAME" => "Phone"));?></div>
				<?endif;?>
			</div>
		<?endif;?>
		<?
		if($arRegion){
			$frame->end();
		}
	}

	public static function showContactEmail($txt = '', $wrapTable = true, $class = '', $icon = 'Email.svg', $subclass = ''){
		static $cemail_call;
		global $arRegion, $APPLICATION;

		if (!isset($cemail_call)) {
			$cemail_call = 0;
		}
		$iCalledID = ++$cemail_call;

		$bEmail = ($arRegion ? $arRegion['PROPERTY_EMAIL_VALUE'] : self::checkContentFile(SITE_DIR.'include/contacts-site-email.php'));
		$bRegionContact = (\Bitrix\Main\Config\Option::get(self::moduleID, 'SHOW_REGION_CONTACT', 'N') == 'Y');
		?>
		<?if($arRegion):?>
			<?$frame = new \Bitrix\Main\Page\FrameHelper('header-allcemail-block'.$iCalledID);?>
			<?$frame->begin();?>
		<?endif;?>
		<?if($bEmail): // count of phones?>

			<div class="property email">
				<div class="title font_upper muted"><?=($txt ? $txt : Loc::getMessage('SPRAVKA'));?></div>
				<?if($arRegion && $bRegionContact):?>
					<div class="<?=($class ? ' '.$class : '')?>">
						<?foreach($arRegion['PROPERTY_EMAIL_VALUE'] as $value):?>
							<div class="value darken" itemprop="email">
								<a href="mailto:<?=$value;?>"><?=$value;?></a>
							</div>
						<?endforeach;?>
					</div>
				<?else:?>
					<div class="value darken" itemprop="email"><?$APPLICATION->IncludeFile(SITE_DIR."include/contacts-site-email.php", Array(), Array("MODE" => "html", "NAME" => "email"));?></div>
				<?endif;?>
			</div>

		<?endif;?>
		<?if($arRegion):?>
			<?$frame->end();?>
		<?endif;?>
		<?
	}

	public static function showContactAddr($txt = '', $wrapTable = true, $class = '', $icon = 'Addres_black.svg', $subclass = ''){
		static $caddr_call;
		global $arRegion, $APPLICATION;
		
		if (!isset($caddr_call)) {
			$caddr_call = 0;
		}
		$iCalledID = ++$caddr_call;
		
		$bAddr = ($arRegion ? ($arRegion['PROPERTY_ADDRESS_VALUE']['TEXT'] ?? '') : self::checkContentFile(SITE_DIR.'include/contacts-site-address.php'));
		$bRegionContact = (\Bitrix\Main\Config\Option::get(self::moduleID, 'SHOW_REGION_CONTACT', 'N') == 'Y');
		?>
		<?if($arRegion):?>
			<?$frame = new \Bitrix\Main\Page\FrameHelper('header-allcaddr-block'.$iCalledID);?>
			<?$frame->begin();?>
		<?endif;?>
		<?if($bAddr): // count of phones?>

					<div class="property address">
						<div class="title font_upper muted"><?=$txt;?></div>
						<?if($arRegion && $bRegionContact):?>
							<div itemprop="address" class="<?=($class ? ' value darken '.$class : '')?>">
								<?=($arRegion['PROPERTY_ADDRESS_VALUE']['TEXT'] ?? '');?>
							</div>
						<?else:?>
							<div itemprop="address" class="value darken"><?$APPLICATION->IncludeFile(SITE_DIR."include/contacts-site-address.php", Array(), Array("MODE" => "html", "NAME" => "address"));?></div>
						<?endif;?>
					</div>

		<?endif;?>
		<?if($arRegion):?>
			<?$frame->end();?>
		<?endif;?>
		<?
	}


	public static function showContactImg(){
		static $cimg_call;
		global $arRegion, $APPLICATION;

		if (!isset($cimg_call)) {
			$cimg_call = 0;
		}
		$iCalledID = ++$cimg_call;

		//$bAddr = ($arRegion ? $arRegion['PROPERTY_ADDRESS_VALUE']['TEXT'] : self::checkContentFile(SITE_DIR.'include/contacts-site-address.php'));
		$bImg = ($arRegion && $arRegion['PROPERTY_REGION_TAG_CONTACT_IMG_VALUE'] ? $arRegion['PROPERTY_REGION_TAG_CONTACT_IMG_VALUE'] : self::checkContentFile(SITE_DIR.'include/contacts-site-image.php'));
		$bRegionContact = (\Bitrix\Main\Config\Option::get(self::moduleID, 'SHOW_REGION_CONTACT', 'N') == 'Y');
		?>
		<?if($arRegion):?>
			<?$frame = new \Bitrix\Main\Page\FrameHelper('header-allcimg-block'.$iCalledID);?>
			<?$frame->begin();?>
		<?endif;?>
		<?if($bImg):?>

					<div class="contacts_img ">
						<?if($arRegion && $bRegionContact && $arRegion['PROPERTY_REGION_TAG_CONTACT_IMG_VALUE']):?>
						<?
							$arImgRegion = CFile::GetFileArray($arRegion['PROPERTY_REGION_TAG_CONTACT_IMG_VALUE']);
						?>
								<img src="<?=$arImgRegion['SRC'];?>">
						<?else:?>
							<?$APPLICATION->IncludeFile(SITE_DIR."include/contacts-site-image.php", Array(), Array("MODE" => "html", "NAME" => "Image"));?>
						<?endif;?>
					</div>

		<?endif;?>
		<?if($arRegion):?>
			<?$frame->end();?>
		<?endif;?>
		<?
	}


	public static function showContactDesc(){
		static $cdesc_call;
		global $arRegion, $APPLICATION;

		if (!isset($cdesc_call)) {
			$cdesc_call = 0;
		}
		$iCalledID = ++$cdesc_call;

		//$bAddr = ($arRegion ? $arRegion['PROPERTY_ADDRESS_VALUE']['TEXT'] : self::checkContentFile(SITE_DIR.'include/contacts-site-address.php'));
		$bDesc = ($arRegion && ($arRegion['PROPERTY_REGION_TAG_CONTACT_TEXT_VALUE']['TEXT'] ?? '') ? $arRegion['PROPERTY_REGION_TAG_CONTACT_TEXT_VALUE']['TEXT'] : self::checkContentFile(SITE_DIR.'include/contacts-about.php'));
		$bRegionContact = (\Bitrix\Main\Config\Option::get(self::moduleID, 'SHOW_REGION_CONTACT', 'N') == 'Y');
		?>
		<?if($arRegion):?>
			<?$frame = new \Bitrix\Main\Page\FrameHelper('header-allcdesc-block'.$iCalledID);?>
			<?$frame->begin();?>
		<?endif;?>
		<?if($bDesc):?>
					<div itemprop="description" class="previewtext muted777">
						<?if($arRegion && $bRegionContact && ($arRegion['PROPERTY_REGION_TAG_CONTACT_TEXT_VALUE']['TEXT'] ?? '')):?>
							<?=$arRegion['PROPERTY_REGION_TAG_CONTACT_TEXT_VALUE']['TEXT'];?>
						<?else:?>
							<?$APPLICATION->IncludeFile(SITE_DIR."include/contacts-about.php", Array(), Array("MODE" => "html", "NAME" => "Contacts about"));?>
						<?endif;?>
					</div>
		<?endif;?>
		<?if($arRegion):?>
			<?$frame->end();?>
		<?endif;?>
		<?
	}


	public static function showContactSchedule($txt = '', $wrapTable = true, $class = '', $icon = 'WorkingHours_lg.svg', $subclass = ''){
		static $cshc_call;
		global $arRegion, $APPLICATION;

		if (!isset($cshc_call)) {
			$cshc_call = 0;
		}
		$iCalledID = ++$cshc_call;

		$bRegionContact = (\Bitrix\Main\Config\Option::get(self::moduleID, 'SHOW_REGION_CONTACT', 'N') == 'Y');
		$bAddr = ($arRegion && $bRegionContact && ($arRegion['PROPERTY_REGION_TAG_SHEDULLE_VALUE']['TEXT'] ?? '') ? $arRegion['PROPERTY_REGION_TAG_SHEDULLE_VALUE']['TEXT'] : self::checkContentFile(SITE_DIR.'include/contacts-site-schedule.php'));
		?>
		<?if($arRegion):?>
			<?$frame = new \Bitrix\Main\Page\FrameHelper('header-allcaddr-block'.$iCalledID);?>
			<?$frame->begin();?>
		<?endif;?>
		<?if($bAddr):?>
					<div class="property schedule">
						<div class="title font_upper muted"><?=$txt;?></div>
						<?if($arRegion && ($arRegion['PROPERTY_REGION_TAG_SHEDULLE_VALUE']['TEXT'] ?? '') && $bRegionContact):?>
							<div class="<?=($class ? ' value darken '.$class : '')?>">
								<?=$arRegion['PROPERTY_REGION_TAG_SHEDULLE_VALUE']['TEXT'];?>
							</div>
						<?else:?>
							<div class="value darken"><?$APPLICATION->IncludeFile(SITE_DIR."include/contacts-site-schedule.php", Array(), Array("MODE" => "html", "NAME" => "schedule"));?></div>
						<?endif;?>
					</div>
		<?endif;?>
		<?if($arRegion):?>
			<?$frame->end();?>
		<?endif;?>
		<?
	}

	public static function ShowPrintLink($txt=''){
		$html = '';

		$arTheme = self::GetFrontParametrsValues(SITE_ID);
		if($arTheme['PRINT_BUTTON'] == 'Y')
		{
			if(!$txt)
				$txt = $arTheme['EXPRESSION_FOR_PRINT_PAGE'];

			/*$html = '<div class="print-link"><i class="icon"><svg id="Print.svg" xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 16 16"><path class="cls-1" d="M1553,287h-2v3h-8v-3h-2a2,2,0,0,1-2-2v-5a2,2,0,0,1,2-2h2v-4h8v4h2a2,2,0,0,1,2,2v5A2,2,0,0,1,1553,287Zm-8,1h4v-4h-4v4Zm4-12h-4v2h4v-2Zm4,4h-12v5h2v-3h8v3h2v-5Z" transform="translate(-1539 -274)"/></svg></i>';*/
			$html = '<div class="print-link">'.static::showIconSvg("print", SITE_TEMPLATE_PATH."/images/svg/print.svg");
			if($txt)
				$html .= '<span class="text">'.$txt.'</span>';
			$html .= '</div>';
		}
		return $html;
	}

	public static function ShowBasketWithCompareLink($class_link='top-btn hover', $class_icon='', $show_price = false, $class_block='', $force_show = false, $bottom = false, $div_class=''){?>
		<?global $APPLICATION, $arTheme, $arBasketPrices;
		static $basket_call;
		$type_svg = '';
		if($class_icon)
		{
			$tmp = explode(' ', $class_icon);
			$type_svg = '_'.$tmp[0];
		}


		$iCalledID = ++$basket_call;?>
		<?if(($arTheme['ORDER_BASKET_VIEW']['VALUE'] == 'NORMAL' || ($arTheme['ORDER_BASKET_VIEW']['VALUE'] == 'BOTTOM' && $bottom)) || $force_show):?>
			<?if($div_class):?>
				<div class="<?=$div_class?>">
			<?endif;?>
			<?Bitrix\Main\Page\Frame::getInstance()->startDynamicWithID('header-basket-with-compare-block'.$iCalledID);?>
				<?if($arTheme['CATALOG_COMPARE']['VALUE'] != 'N'):?>
					<?if($class_block):?>
						<div class="<?=$class_block;?>">
					<?endif;?>
					<?$APPLICATION->IncludeComponent("bitrix:main.include", ".default",
						array(
							"COMPONENT_TEMPLATE" => ".default",
							"PATH" => SITE_DIR."ajax/show_compare_preview_top.php",
							"AREA_FILE_SHOW" => "file",
							"AREA_FILE_SUFFIX" => "",
							"AREA_FILE_RECURSIVE" => "Y",
							"CLASS_LINK" => $class_link,
							"CLASS_ICON" => $class_icon,
							"FROM_MODULE" => "Y",
							"EDIT_TEMPLATE" => "standard.php"
						),
						false, array('HIDE_ICONS' => 'Y')
					);?>
					<?if($class_block):?>
						</div>
					<?endif;?>
				<?endif;?>
				<?if(self::getShowBasket()):?>
					<!-- noindex -->
					<?if($class_block):?>
						<div class="<?=$class_block;?>">
					<?endif;?>
						<a 
							rel="nofollow" 
							class="basket-link delay <?=$class_link;?> <?=$class_icon;?> <?=($arBasketPrices['DELAY_COUNT'] ? 'basket-count' : '');?>" 
							href="<?= $arBasketPrices['DELAY_COUNT'] ? $arTheme['BASKET_PAGE_URL']['VALUE'] . '#delayed' : 'javascript:void(0)'; ?>"
							data-href="<?=$arTheme['BASKET_PAGE_URL']['VALUE'];?>#delayed" 
							title="<?=$arBasketPrices['DELAY_SUMM_TITLE'];?>"
						>
							<span class="js-basket-block">
								<?=self::showSpriteIconSvg(SITE_TEMPLATE_PATH."/images/svg/header_icons_srite.svg#chosen", "wish ".$class_icon, ['WIDTH' => 20,'HEIGHT' => 16]);?>
								<span class="title dark_link"><?=Loc::getMessage('JS_BASKET_DELAY_TITLE');?></span>
								<span class="count"><?=$arBasketPrices['DELAY_COUNT'];?></span>
							</span>
						</a>
					<?if($class_block):?>
						</div>
					<?endif;?>
					<?if($class_block):?>
						<div class="<?=$class_block;?> <?=$arTheme['ORDER_BASKET_VIEW']['VALUE'] ? 'top_basket' : ''?>">
					<?endif;?>
						<a rel="nofollow" class="basket-link basket <?=($show_price ? 'has_prices' : '');?> <?=$class_link;?> <?=$class_icon;?> <?=($arBasketPrices['BASKET_COUNT'] ? 'basket-count' : '');?>" href="<?=$arTheme['BASKET_PAGE_URL']['VALUE'];?>" title="<?=$arBasketPrices['BASKET_SUMM_TITLE'];?>">
							<span class="js-basket-block">
								<?=self::showSpriteIconSvg(SITE_TEMPLATE_PATH."/images/svg/header_icons_srite.svg#basket", "basket ".$class_icon, ['WIDTH' => 19,'HEIGHT' => 16]);?>
								<?if($show_price):?>
									<span class="wrap">
								<?endif;?>
								<span class="title dark_link"><?=Loc::getMessage('JS_BASKET_TITLE');?></span>
								<span class="count"><?=$arBasketPrices['BASKET_COUNT'];?></span>
								<?if($show_price):?>
									<span class="prices"><?=($arBasketPrices['BASKET_COUNT'] ? $arBasketPrices['BASKET_SUMM'] : $arBasketPrices['BASKET_SUMM_TITLE_SMALL'] )?></span>
									</span>
								<?endif;?>
							</span>
						</a>
						<span class="basket_hover_block loading_block loading_block_content"></span>

					<?if($class_block):?>
						</div>
					<?endif;?>
					<!-- /noindex -->
				<?endif;?>
			<?Bitrix\Main\Page\Frame::getInstance()->finishDynamicWithID('header-basket-with-compare-block'.$iCalledID, '');?>
			<?if($div_class):?>
				</div>
			<?endif;?>
		<?endif;?>
	<?}

	public static function ShowMobileMenuCabinet(){
		global $APPLICATION, $arTheme;
		static $mauth_call;

		$iCalledID = ++$mauth_call;?>
		<?Bitrix\Main\Page\Frame::getInstance()->startDynamicWithID('mobile-auth-block'.$iCalledID);?>
		<?$APPLICATION->IncludeComponent(
			"bitrix:menu",
			"cabinet_mobile",
			Array(
				"COMPONENT_TEMPLATE" => "cabinet_mobile",
				"MENU_CACHE_TIME" => "3600000",
				"MENU_CACHE_TYPE" => "A",
				"MENU_CACHE_USE_GROUPS" => "Y",
				"MENU_CACHE_GET_VARS" => array(
				),
				"DELAY" => "N",
				"MAX_LEVEL" => \Bitrix\Main\Config\Option::get(self::moduleID, "MAX_DEPTH_MENU", 2),
				"ALLOW_MULTI_SELECT" => "Y",
				"CACHE_SELECTED_ITEMS" => "N",
				"ROOT_MENU_TYPE" => "cabinet",
				"CHILD_MENU_TYPE" => "left",
				"USE_EXT" => "Y"
			)
		);?>
		<?Bitrix\Main\Page\Frame::getInstance()->finishDynamicWithID('mobile-auth-block'.$iCalledID);?>
	<?}

	public static function ShowMobileMenuBasket(){
		static $mbasket_call;
		global $APPLICATION, $arTheme, $arBasketPrices, $compare_items;

		$basketUrl = trim($arTheme['BASKET_PAGE_URL']['VALUE']);
		$compareUrl = trim($arTheme['COMPARE_PAGE_URL']['VALUE']);

		$bShowBasket = (strlen($basketUrl) && self::getShowBasket());

		$iCalledID = ++$mbasket_call;
		$count_compare = 0;
		if($compare_items)
		{
			$count_compare = count($compare_items);
		}
		else
		{
			if(isset($_SESSION["CATALOG_COMPARE_LIST"][$arTheme['CATALOG_IBLOCK_ID']['VALUE']]['ITEMS']))
			{
				$compare_items = array_keys($_SESSION["CATALOG_COMPARE_LIST"][$arTheme['CATALOG_IBLOCK_ID']['VALUE']]['ITEMS']);
				$count_compare = count(array_keys($compare_items));
			}
		}?>
		<?Bitrix\Main\Page\Frame::getInstance()->startDynamicWithID('mobile-basket-with-compare-block'.$iCalledID);?>
		<!-- noindex -->
		<?if($bShowBasket):?>
			<div class="menu middle">
				<ul>
					<li class="counters">
						<a rel="nofollow" class="dark-color basket-link basket ready <?=($arBasketPrices['BASKET_COUNT'] ? 'basket-count' : '');?>" href="<?=$basketUrl?>">
							<?=static::showIconSvg("basket", SITE_TEMPLATE_PATH.'/images/svg/basket.svg', '', '', true, false);?>
							<span><?=Loc::getMessage('JS_BASKET_TITLE')?><span class="count<?=(!$arBasketPrices['BASKET_COUNT'] ? ' empted' : '')?>"><?=$arBasketPrices['BASKET_COUNT'];?></span></span>
						</a>
					</li>
					<li class="counters">
						<a rel="nofollow" 
							class="dark-color basket-link delay ready <?=($arBasketPrices['DELAY_COUNT'] ? 'basket-count' : '');?>" 
							href="<?= $arBasketPrices['DELAY_COUNT'] ? $basketUrl . '#delayed' : 'javascript:void(0)'; ?>"
							data-href="<?=$basketUrl;?>#delayed"
						>
							<?=static::showIconSvg("basket", SITE_TEMPLATE_PATH.'/images/svg/chosen_small.svg', '', '', true, false);?>
							<span><?=Loc::getMessage('JS_BASKET_DELAY_TITLE')?><span class="count<?=(!$arBasketPrices['DELAY_COUNT'] ? ' empted' : '')?>"><?=$arBasketPrices['DELAY_COUNT'];?></span></span>
						</a>
					</li>
				</ul>
			</div>
		<?endif;?>
		<?if($arTheme['CATALOG_COMPARE']['VALUE'] != 'N'):?>
			<div class="menu middle">
				<ul>
					<li class="counters">
						<a rel="nofollow" class="dark-color basket-link compare ready <?=($count_compare ? 'basket-count' : '');?>" href="<?=$compareUrl?>">
							<?=static::showIconSvg("compare ", SITE_TEMPLATE_PATH."/images/svg/compare.svg");?>
							<span><?=Loc::getMessage('JS_COMPARE_TITLE')?><span class="count<?=(!$count_compare ? ' empted' : '')?>"><?=$count_compare;?></span></span>
						</a>
					</li>
				</ul>
			</div>
		<?endif;?>
		<!-- /noindex -->
		<?Bitrix\Main\Page\Frame::getInstance()->finishDynamicWithID('mobile-basket-with-compare-block'.$iCalledID);?>
	<?}

	public static function ShowMobileMenuContacts(){
		static $mphones_call;
		global $APPLICATION, $arTheme;

		$iCalledID = ++$mphones_call;
		$arBackParametrs = self::GetBackParametrsValues(SITE_ID);

		if(self::GetFrontParametrValue('USE_REGIONALITY', SITE_ID, true) == 'Y'){
			global $arRegion;
		}
		else{
			$arRegion = array();
		}
		$iCountPhones = ($arRegion ? (isset($arRegion['PHONES'][0]['PHONE']) && $arRegion['PHONES'][0]['PHONE'] ? count($arRegion['PHONES']) : 0) : $arBackParametrs['HEADER_PHONES']);
		$regionID = ($arRegion ? $arRegion['ID'] : '');
		?>
		<?if($iCountPhones):?>
			<?
			$phone = ($arRegion ? ($arRegion['PHONES'][0]['PHONE'] ?? '') : ($arBackParametrs['HEADER_PHONES_array_PHONE_VALUE_0'] ?? ''));
			$href = ($arRegion ? ($arRegion['PHONES'][0]['HREF'] ?? '') : ($arBackParametrs['HEADER_PHONES_array_PHONE_HREF_0'] ?? ''));
			if(!strlen($href)){
				$href = 'javascript:;';
			}

			$description = ($arRegion ? $arRegion['PROPERTY_PHONES_DESCRIPTION'][0] : $arBackParametrs['HEADER_PHONES_array_PHONE_DESCRIPTION_0']);
			$description = (!empty($description)) ? '<span class="descr">' . $description . '</span>' : '';

			$bHaveIcons = false;
			for($i = 0; $i < $iCountPhones; ++$i){
				if($bHaveIcons = strlen($arRegion ? ($arRegion['PHONES'][$i]['ICON'] ?? '') : ($arBackParametrs['HEADER_PHONES_array_PHONE_ICON_'.$i] ?? ''))){
					break;
				}
			}

			if($arRegion){
				$frame = new \Bitrix\Main\Page\FrameHelper('mobile-phone-block'.$iCalledID);
				$frame->begin();
			}
			?>
			<!-- noindex -->
			<div class="menu middle mobile-menu-contacts">
				<ul>
					<li>
						<a rel="nofollow" href="<?=($iCountPhones > 1 ? '' : $href)?>" class="dark-color<?=($iCountPhones > 1 ? ' parent' : '')?> <?=(empty($description)?'no-decript':'decript')?>">
							<i class="svg svg-phone"></i>
							<?=static::showSpriteIconSvg(SITE_TEMPLATE_PATH."/images/svg/header_icons_srite.svg#phone_black", "svg-inline-phone ", ['WIDTH' => 5,'HEIGHT' => 13]);?>
							<span><?=$phone?><?=$description?></span>
							<?if($iCountPhones > 1):?>
								<span class="arrow">
									<?=static::showIconSvg("triangle", SITE_TEMPLATE_PATH.'/images/svg/trianglearrow_right.svg', '', '', true, false);?>
								</span>
							<?endif;?>
						</a>
						<?if($iCountPhones > 1): // if more than one?>
							<ul class="dropdown <?=($bHaveIcons ? 'with_icons' : '')?>">
								<li class="menu_back"><a href="" class="dark-color" rel="nofollow"><?=static::showIconSvg('back_arrow', SITE_TEMPLATE_PATH.'/images/svg/return_mm.svg')?><?=Loc::getMessage('MAX_T_MENU_BACK')?></a></li>
								<li class="menu_title"><?=Loc::getMessage('MAX_T_MENU_CALLBACK')?></li>
								<?for($i = 0; $i < $iCountPhones; ++$i):?>
									<?
									$phone = ($arRegion ? $arRegion['PHONES'][$i]['PHONE'] : $arBackParametrs['HEADER_PHONES_array_PHONE_VALUE_'.$i]);
									$href = ($arRegion ? $arRegion['PHONES'][$i]['HREF'] : $arBackParametrs['HEADER_PHONES_array_PHONE_HREF_'.$i]);
									if(!strlen($href)){
										$href = 'javascript:;';
									}

									$icon = ($arRegion ? $arRegion['PHONES'][$i]['ICON'] : $arBackParametrs['HEADER_PHONES_array_PHONE_ICON_'.$i]);
									$icon = (strlen($icon) ? '<span class="icon">'.\Aspro\Max\Iconset::showIcon($icon, false, false).'</span>' : '');

									$description = ($arRegion ? $arRegion['PROPERTY_PHONES_DESCRIPTION'][$i] : $arBackParametrs['HEADER_PHONES_array_PHONE_DESCRIPTION_'.$i]);
									$description = (strlen($description) ? '<span class="descr">'.$description.'</span>' : '');
									?>
									<li><a rel="nofollow" href="<?=$href?>" class="bo	ld dark-color <?=(empty($description)?'no-decript':'decript')?>"><?=$icon?><?=$phone?><?=$description?></a></li>
								<?endfor;?>
								<?
								$callbackExploded = explode(',', $arTheme['SHOW_CALLBACK']['VALUE']);
								if( in_array('HEADER', $callbackExploded) ):?>
									<li><a rel="nofollow" class="dark-color" href="" data-event="jqm" data-param-form_id="CALLBACK" data-name="callback"><?=Loc::getMessage('CALLBACK')?></a></li>
								<?endif;?>
							</ul>
						<?endif;?>
					</li>
				</ul>
			</div>
			<!-- /noindex -->
			<?
			if($arRegion){
				$frame->end();
			}
			?>
		<?endif;?>
		<div class="contacts">
			<div class="title"><?=Loc::getMessage('MAX_T_MENU_CONTACTS_TITLE')?></div>
			<?if($arRegion):?>
			<?$frame = new \Bitrix\Main\Page\FrameHelper('mobile-contact-block');?>
			<?$frame->begin();?>
			<?endif;?>
			<?if($arRegion):?>
				<?if($arRegion['PROPERTY_ADDRESS_VALUE']):?>
					<div class="address">
						<?=self::showIconSvg("address", SITE_TEMPLATE_PATH."/images/svg/address.svg");?>
						<?=($arRegion['PROPERTY_ADDRESS_VALUE']['TEXT'] ?? '');?>
					</div>
				<?endif;?>
			<?else:?>
				<div class="address">
					<?=self::showIconSvg("address", SITE_TEMPLATE_PATH."/images/svg/address.svg");?>
					<?$APPLICATION->IncludeFile(SITE_DIR."include/top_page/site-address.php", array(), array(
							"MODE" => "html",
							"NAME" => "Address",
							"TEMPLATE" => "include_area.php",
						)
					);?>
				</div>
			<?endif;?>
			<?if($arRegion):?>
				<?if($arRegion['PROPERTY_EMAIL_VALUE']):?>
					<div class="email">
						<?=self::showIconSvg("email", SITE_TEMPLATE_PATH."/images/svg/email_footer.svg");?>
						<?foreach($arRegion['PROPERTY_EMAIL_VALUE'] as $value):?>
							<a href="mailto:<?=$value;?>"><?=$value;?></a>
						<?endforeach;?>
					</div>
				<?endif;?>
			<?else:?>
				<div class="email">
					<?=self::showIconSvg("email", SITE_TEMPLATE_PATH."/images/svg/email_footer.svg");?>
					<?$APPLICATION->IncludeFile(SITE_DIR."include/footer/site-email.php", array(), array(
							"MODE" => "html",
							"NAME" => "Address",
							"TEMPLATE" => "include_area.php",
						)
					);?>
				</div>
			<?endif;?>
			<?if($arRegion):?>
			<?$frame->end();?>
			<?endif;?>
		</div>
		<?
	}

	public static function ShowMobileRegions(){
		global $APPLICATION;

		static $mregions_call;
		$iCalledID = ++$mregions_call;

		\CBitrixComponent::includeComponentClass('aspro:regionality.list.max');
		$arResult = \Aspro\Max\Components\RegionalityList::$lastResult;			

		if (
			$arResult &&
			(
				$arResult['USE_REGIONALITY'] ||
				(
					$arResult['SHOW_CITY'] &&
					$arResult['REAL_CITY'] 
				)
			)
		) {
			\Bitrix\Main\Composite\Engine::getInstance()->startDynamicWithID('mobile-region-block'.$iCalledID);
			?>
			<!-- noindex -->
			<div class="menu middle mobile_regions">
				<ul>
					<li>
						<a rel="nofollow" href="javascript:;" class="dark-color<?=($arResult['USE_REGIONALITY'] ? ' parent' : '')?>">
							<?=static::showIconSvg('region_arrow', SITE_TEMPLATE_PATH.'/images/svg/location_mm.svg')?>
							<span><?=$arResult['CURRENT_REGION_TITLE_IN_HEADER']?></span>
							<?if ($arResult['USE_REGIONALITY']):?>
								<span class="arrow">
									<?=static::showIconSvg("triangle", SITE_TEMPLATE_PATH.'/images/svg/trianglearrow_right.svg', '', '', true, false);?>
								</span>
							<?endif;?>
						</a>
						<?if ($arResult['USE_REGIONALITY']):?>
							<ul class="dropdown">
								<li class="menu_back"><a href="" class="dark-color" rel="nofollow"><?=static::showIconSvg('back_arrow', SITE_TEMPLATE_PATH.'/images/svg/return_mm.svg')?><?=Loc::getMessage('MAX_T_MENU_BACK')?></a></li>
								<li class="menu_autocomplete menu_autocomplete--regions">
									<div class="h-search autocomplete-block" id="title-search-city">
										<div class="wrapper">
											<div class="search_icon"><?=CMax::showIconSvg("search2", SITE_TEMPLATE_PATH."/images/svg/Search.svg");?></div>
											<input id="mobile-region-search" class="autocomplete text" type="text" placeholder="<?=Loc::getMessage('CITY_PLACEHOLDER');?>">
											<div class="clean_icon" style="display:none"><?=CMax::showIconSvg('', SITE_TEMPLATE_PATH.'/images/svg/Close.svg')?></div>
										</div>
									</div>
								</li>
								<li class="menu-item-fixed scrollblock">
									<ul class="mobile-cities">
										<?if (!$arResult['ONLY_SEARCH_ROW']):?>
											<li><div class="loadings" style="height:47px;"></div></li>
										<?endif;?>
									</ul>
								</li>
							</ul>
						<?endif;?>
					</li>
				</ul>
			</div>
			<!-- /noindex -->
			<?
			\Bitrix\Main\Composite\Engine::getInstance()->finishDynamicWithID('mobile-region-block'.$iCalledID);
		}
	}

	public static function getFieldImageData(array &$arItem, array $arKeys, $entity = 'ELEMENT', $ipropertyKey = 'IPROPERTY_VALUES'){
		if (empty($arItem) || empty($arKeys))
            return;

        $entity = (string)$entity;
        $ipropertyKey = (string)$ipropertyKey;

        foreach ($arKeys as $fieldName)
        {
            if(!isset($arItem[$fieldName]) || (!isset($arItem['~'.$fieldName]) || !$arItem['~'.$fieldName]))
                continue;
            $imageData = false;
            $imageId = (int)$arItem['~'.$fieldName];
            if ($imageId > 0)
                $imageData = \CFile::getFileArray($imageId);
            unset($imageId);
            if (is_array($imageData))
            {
                if (isset($imageData['SAFE_SRC']))
                {
                    $imageData['UNSAFE_SRC'] = $imageData['SRC'];
                    $imageData['SRC'] = $imageData['SAFE_SRC'];
                }
                else
                {
                    $imageData['UNSAFE_SRC'] = $imageData['SRC'];
                    $imageData['SRC'] = \CHTTP::urnEncode($imageData['SRC'], 'UTF-8');
                }
                $imageData['ALT'] = '';
                $imageData['TITLE'] = '';

                if ($ipropertyKey != '' && isset($arItem[$ipropertyKey]) && is_array($arItem[$ipropertyKey]))
                {
                    $entityPrefix = $entity.'_'.$fieldName;
                    if (isset($arItem[$ipropertyKey][$entityPrefix.'_FILE_ALT']))
                        $imageData['ALT'] = $arItem[$ipropertyKey][$entityPrefix.'_FILE_ALT'];
                    if (isset($arItem[$ipropertyKey][$entityPrefix.'_FILE_TITLE']))
                        $imageData['TITLE'] = $arItem[$ipropertyKey][$entityPrefix.'_FILE_TITLE'];
                    unset($entityPrefix);
                }
                if ($imageData['ALT'] == '' && isset($arItem['NAME']))
                    $imageData['ALT'] = $arItem['NAME'];
                if ($imageData['TITLE'] == '' && isset($arItem['NAME']))
                    $imageData['TITLE'] = $arItem['NAME'];
            }
            $arItem[$fieldName] = $imageData;
            unset($imageData);
        }

        unset($fieldName);
	}

	public static function GetDirMenuParametrs($dir){
		if(strlen($dir)){
			$file = str_replace('//', '/', $dir.'/.section.php');
			if(file_exists($file)){
				@include($file);
				return $arDirProperties;
			}
		}

		return false;
	}

	public static function FormatNewsUrl($arItem){
    	$url = $arItem['DETAIL_PAGE_URL'];
    	if(strlen($arItem['DISPLAY_PROPERTIES']['REDIRECT']['VALUE'] ?? ''))
		{
			$url = $arItem['DISPLAY_PROPERTIES']['REDIRECT']['VALUE'];
			return $url;
		}
    	if($arItem['ACTIVE_FROM'])
    	{
    		if($arDateTime = ParseDateTime($arItem['ACTIVE_FROM'], FORMAT_DATETIME))
    		{
		        $url = str_replace("#YEAR#", $arDateTime['YYYY'], $arItem['DETAIL_PAGE_URL']);
		        return $url;
    		}
    	}
    	return $url;
    }

	public static function GetSections($arItems, $arParams){
		$arSections = array(
			'PARENT_SECTIONS' => array(),
			'CHILD_SECTIONS' => array(),
			'ALL_SECTIONS' => array(),
		);
		if(is_array($arItems) && $arItems)
		{
			$arSectionsIDs = array();
			foreach($arItems as $arItem)
			{
				if($SID = $arItem['IBLOCK_SECTION_ID'])
					$arSectionsIDs[] = $SID;
			}
			if($arSectionsIDs)
			{
				$arSections['ALL_SECTIONS'] = CMaxCache::CIBLockSection_GetList(array('SORT' => 'ASC', 'NAME' => 'ASC', 'CACHE' => array('TAG' => CMaxCache::GetIBlockCacheTag($arParams['IBLOCK_ID']), 'GROUP' => array('ID'), 'MULTI' => 'N')), array('ID' => $arSectionsIDs));
				$bCheckRoot = false;
				foreach($arSections['ALL_SECTIONS'] as $key => $arSection)
				{
					if($arSection['DEPTH_LEVEL'] > 1)
					{
						$bCheckRoot = true;
						$arSections['CHILD_SECTIONS'][$key] = $arSection;
						unset($arSections['ALL_SECTIONS'][$key]);

						$arFilter = array('IBLOCK_ID'=>$arSection['IBLOCK_ID'], '<=LEFT_BORDER' => $arSection['LEFT_MARGIN'], '>=RIGHT_BORDER' => $arSection['RIGHT_MARGIN'], 'DEPTH_LEVEL' => 1);
						$arSelect = array('ID', 'SORT', 'IBLOCK_ID', 'NAME');
						$arParentSection = CMaxCache::CIBLockSection_GetList(array('SORT' => 'ASC', 'NAME' => 'ASC', 'CACHE' => array('TAG' => CMaxCache::GetIBlockCacheTag($arParams['IBLOCK_ID']), 'MULTI' => 'N')), $arFilter, false, $arSelect);

						$arSections['ALL_SECTIONS'][$arParentSection['ID']]['SECTION'] = $arParentSection;
						$arSections['ALL_SECTIONS'][$arParentSection['ID']]['CHILD_IDS'][$arSection['ID']] = $arSection['ID'];

						$arSections['PARENT_SECTIONS'][$arParentSection['ID']] = $arParentSection;
					}
					else
					{
						$arSections['ALL_SECTIONS'][$key]['SECTION'] = $arSection;
						$arSections['PARENT_SECTIONS'][$key] = $arSection;
					}
				}

				if($bCheckRoot)
				{
					// get root sections
					$arFilter = array('IBLOCK_ID' => $arParams['IBLOCK_ID'], 'ACTIVE' => 'Y', 'DEPTH_LEVEL' => 1, 'ID' => array_keys($arSections['ALL_SECTIONS']));
					$arSelect = array('ID', 'SORT', 'IBLOCK_ID', 'NAME');
					$arRootSections = CMaxCache::CIBLockSection_GetList(array('SORT' => 'ASC', 'NAME' => 'ASC', 'CACHE' => array('TAG' => CMaxCache::GetIBlockCacheTag($arParams['IBLOCK_ID']))), $arFilter, false, $arSelect);
					foreach($arRootSections as $arSection)
					{
						$arSections['ALL_SECTIONS']['SORTED'][$arSection['ID']] = $arSections['ALL_SECTIONS'][$arSection['ID']];
						unset($arSections['ALL_SECTIONS'][$arSection['ID']]);
					}
					foreach($arSections['ALL_SECTIONS']['SORTED'] as $key => $arSection)
					{
						$arSections['ALL_SECTIONS'][$key] = $arSection;
					}
					unset($arSections['ALL_SECTIONS']['SORTED']);
				}
			}
		}
		return $arSections;
	}

	public static function showAllAdminRows($optionCode, $arTab, $arOption, $module_id, $arPersonTypes, $optionsSiteID, $arDeliveryServices, $arPaySystems, $arCurrency, $arOrderPropertiesByPerson, $bSearchMode){
		if(array_key_exists($optionCode, $arTab["OPTIONS"]) || $arOption["TYPE"] == 'note' || $arOption["TYPE"] == 'includefile')
		{
			$arControllerOption = CControllerClient::GetInstalledOptions(self::moduleID);
			if($optionCode === "ONECLICKBUY_PERSON_TYPE"){
				$arOption['LIST'] = $arPersonTypes[$arTab["SITE_ID"]];
			}
			elseif($optionCode === "ONECLICKBUY_DELIVERY"){
				$arOption['LIST'] = $arDeliveryServices[$arTab["SITE_ID"]];
			}
			elseif($optionCode === "ONECLICKBUY_PAYMENT"){
				$arOption['LIST'] = $arPaySystems;
			}
			elseif($optionCode === "ONECLICKBUY_CURRENCY"){
				$arOption['LIST'] = $arCurrency;
			}
			elseif($optionCode === "ONECLICKBUY_PROPERTIES" || $optionCode === "ONECLICKBUY_REQUIRED_PROPERTIES"){
				$defaultPersonType = '';

				if (
					$arPersonTypes &&
					$arPersonTypes[$arTab["SITE_ID"]]
				) {
					$defaultPersonType = key($arPersonTypes[$arTab["SITE_ID"]]);
				}

				$arOption['LIST'] = $arOrderPropertiesByPerson[Option::get(self::moduleID, 'ONECLICKBUY_PERSON_TYPE', $defaultPersonType, $arTab["SITE_ID"])];
			}

			$searchClass = '';
			if($bSearchMode)
			{
				if(isset($arOption["SEARCH_FIND"]) && $arOption["SEARCH_FIND"]) {
					$searchClass = 'visible_block';
				}
			}

			if($arOption['TYPE'] === 'array')
			{
				$itemsCount = Option::get(self::moduleID, $optionCode, 0, $optionsSiteID);
				if($arOption['OPTIONS'] && is_array($arOption['OPTIONS']))
				{
					$arOptionsKeys = array_keys($arOption['OPTIONS']);
					$newItemHtml = '';
					?>
					<div class="title"><?=$arOption["TITLE"]?></div>
					<div class="item array <?=($itemsCount ? '' : 'empty_block');?> js_block" data-class="<?=$optionCode;?>" data-search="<?=$searchClass;?>">
						<div >
							<div class="aspro-admin-item">
								<?if($optionCode !== 'HEADER_PHONES'):?>
									<div class="wrapper has_title no_drag">
										<div class="inner_wrapper">
											<?foreach($arOptionsKeys as $_optionKey):?>
												<div class="inner">
													<div class="title_wrapper"><div class="subtitle"><?=$arOption['OPTIONS'][$_optionKey]['TITLE']?></div></div>
													<?=self::ShowAdminRow(
														$optionCode.'_array_'.$_optionKey.'_#INDEX#',
														$arOption['OPTIONS'][$_optionKey],
														$arTab,
														$arControllerOption
													);?>
												</div>
											<?endforeach;?>
										</div>
									</div>
								<?endif;?>
								<?for($itemIndex = 0; $itemIndex <= $itemsCount; ++$itemIndex):?>
									<?$bNew = $itemIndex == $itemsCount;?>
									<?if($bNew):?><?ob_start();?><?endif;?>
										<div class="wrapper">
											<div class="inner_wrapper">
												<?foreach($arOptionsKeys as $_optionKey):?>
													<?if($_optionKey === 'PHONE_ICON'):?><br /><?endif;?>
													<div class="inner">
														<?=self::ShowAdminRow(
															$optionCode.'_array_'.$_optionKey.'_'.($bNew ? '#INDEX#' : $itemIndex),
															$arOption['OPTIONS'][$_optionKey],
															$arTab,
															$arControllerOption
														);?>
													</div>
												<?endforeach;?>
												<div class="remove" title="<?=Loc::getMessage("REMOVE_ITEM")?>"></div>
												<div class="drag" title="<?=Loc::getMessage("TRANSFORM_ITEM")?>"></div>
											</div>
										</div>
									<?if($bNew):?><?$newItemHtml = ob_get_clean();?><?endif;?>
								<?endfor;?>
							</div>
							<div class="new-item-html" style="display:none;"><?=str_replace('no_drag', '', $newItemHtml)?></div>
							<div>
								<a href="javascript:;" class="adm-btn adm-btn-save adm-btn-add"><?=GetMessage('OPTIONS_ADD_BUTTON_TITLE')?></a>
							</div>
						</div>
					</div>
				<?}
			}
			else
			{
				if($arOption["TYPE"] == 'note')
				{
					if($optionCode === 'CONTACTS_EDIT_LINK_NOTE')
					{
						$contactsHref = str_replace('//', '/', $arTab['SITE_DIR'].'/contacts/?bitrix_include_areas=Y');
						$arOption["TITLE"] = GetMessage('CONTACTS_OPTIONS_EDIT_LINK_NOTE', array('#CONTACTS_HREF#' => $contactsHref));
					}
					?>
					<div class="notes-block visible_block1" data-option_code="<?=$optionCode;?>">
						<div align="center">
							<?=BeginNote('align="center" name="'.htmlspecialcharsbx($optionCode)."_".$optionsSiteID.'"');?>
							<?=($arOption["TITLE"] ? $arOption["TITLE"] : $arOption["NOTE"])?>
							<?=EndNote();?>
						</div>
					</div>
					<?
				}
				else
				{
					$optionName = $arOption["TITLE"];
					$optionType = $arOption["TYPE"];
					$optionList = $arOption["LIST"];
					$optionDefault = $arOption["DEFAULT"];
					$optionVal = $arTab["OPTIONS"][$optionCode];
					$optionSize = $arOption["SIZE"];
					$optionCols = $arOption["COLS"];
					$optionRows = $arOption["ROWS"];
					$optionChecked = $optionVal == "Y" ? "checked" : "";
					$optionDisabled = isset($arControllerOption[$optionCode]) || array_key_exists("DISABLED", $arOption) && $arOption["DISABLED"] == "Y" ? "disabled" : "";
					$optionSup_text = array_key_exists("SUP", $arOption) ? $arOption["SUP"] : "";
					$optionController = isset($arControllerOption[$optionCode]) ? "title='".GetMessage("MAIN_ADMIN_SET_CONTROLLER_ALT")."'" : "";
					?>
					<div class="item js_block <?=$optionType;?> <?=((isset($arOption["WITH_HINT"]) && $arOption["WITH_HINT"] == "Y") ? 'with-hint' : '');?> <?=((isset($arOption["BIG_BLOCK"]) && $arOption["BIG_BLOCK"] == "Y") ? 'big-block' : '');?>" data-class="<?=$optionCode;?>" data-search="<?=$searchClass;?>">
						<?if($arOption["HIDDEN"] != "Y"):?>
							<div data-optioncode="<?=$optionCode;?>" <?=$style;?> class="js_block1">
								<div class="inner_wrapper <?=($optionType == "checkbox" ? "checkbox" : "");?>">
									<?=self::ShowAdminRow($optionCode, $arOption, $arTab, $arControllerOption);?>
								</div>
								<?if(isset($arOption["IMG"]) && $arOption["IMG"]):?>
									<div class="img"><img src="<?=$arOption["IMG"];?>" alt="<?=$arOption["TITLE"];?>" title="<?=$arOption["TITLE"];?>"></div>
								<?endif;?>
							</div>
						<?endif;?>
						<?if(isset($arOption['SUB_PARAMS']) && $arOption['SUB_PARAMS'] && (isset($arOption['LIST']) && $arOption['LIST'])): //nested params?>
							<?foreach($arOption['LIST'] as $key => $value):?>
								<?foreach((array)$arOption['SUB_PARAMS'][$key] as $key2 => $arValue)
								{
									if(isset($arValue['VISIBLE']) && $arValue['VISIBLE'] == 'N')
										unset($arOption['SUB_PARAMS'][$key][$key2]);
								}
								if($arOption['SUB_PARAMS'][$key]):?>
									<div class="parent-wrapper js-sub block_<?=$key.'_'.$optionsSiteID;?>" <?=($optionVal == $key ? "style='display:block;'" : "")?>>
										<?$param = "SORT_ORDER_".$optionCode."_".$key;?>
										<div data-parent='<?=$optionCode."_".$arTab["SITE_ID"]?>' class="block <?=$key?>" <?=($key == $arTab["OPTIONS"][$optionCode] ? "style='display:block'" : "style='display:none'");?>>
											<?if($arOption['SUB_PARAMS'][$key]):?><div><?=GetMessage('SUB_PARAMS');?></div><?endif;?>
										</div>
										<div class="aspro-admin-item" data-key="<?=$key;?>" data-site="<?=$optionsSiteID;?>">
											<?if($arTab['OPTIONS'][$param])
											{
												$arOrder = explode(",", $arTab['OPTIONS'][$param]);
												$arIndexList = array_keys($arOption['SUB_PARAMS'][$key]);
												$arNewBlocks = array_diff($arIndexList, $arOrder);
												if($arNewBlocks) {
													$arOrder = array_merge($arOrder, $arNewBlocks);
												}
												$arTmp = array();
												foreach($arOrder as $name)
												{
													$arTmp[$name] = $arOption['SUB_PARAMS'][$key][$name];
												}
												$arOption['SUB_PARAMS'][$key] = $arTmp;
												unset($arTmp);
											}?>
											<?$arIndexTemplate = array();?>
											<?foreach((array)$arOption['SUB_PARAMS'][$key] as $key2 => $arValue):
												if($arValue['VISIBLE'] != 'N'):?>
													<div data-parent='<?=$optionCode."_".$arTab["SITE_ID"]?>' class="block sub <?=$key?> <?=($arValue['DRAG'] == 'N' ? 'no_drag' : '');?>" <?=($key == $arTab["OPTIONS"][$optionCode] ? "style='display:block'" : "style='display:none'");?>>
														<div class="inner_wrapper <?=($arValue["TYPE"] == "checkbox" ? "checkbox" : "");?>">
															<?=self::ShowAdminRow($key.'_'.$key2, $arValue, $arTab, $arControllerOption);?>
															<?if($arValue['DRAG'] != 'N'):?>
																<div class="drag" title="<?=Loc::getMessage("TRANSFORM_ITEM")?>"></div>
															<?endif;?>
															<?if($arValue['FON']):?>
																<?$fon_option = 'fon'.$key.$key2?>
																<?$fon_value = Option::get(self::moduleID, $fon_option, $arValue['FON'], $arTab["SITE_ID"]);?>
																<?$fon_option .= '_'.$arTab["SITE_ID"]?>
																<div class="inner_wrapper fons">
																	<div class="title_wrapper">
																		<div class="subtitle">
																			<label for="<?=$fon_option?>"><?=Loc::getMessage("FON_BLOCK")?></label>
																		</div>
																	</div>
																	<div class="value_wrapper">
																		<input type="checkbox" id="<?=$fon_option?>" name="<?=$fon_option?>" value="Y" <?=($fon_value == 'Y' ? "checked" : "");?> class="adm-designed-checkbox">
																		<label class="adm-designed-checkbox-label" for="<?=$fon_option?>" title=""></label>
																	</div>
																</div>
															<?endif;?>
														</div>
													</div>
												<?endif;?>
												<?
												if(isset($arValue['TEMPLATE']) && $arValue['TEMPLATE'])
												{
													$code_tmp = $key2.'_TEMPLATE';
													$arIndexTemplate[$code_tmp] = $arValue['TEMPLATE'];
												}
												?>
											<?endforeach;?>
										</div>
										<input type="hidden" name="<?=$param.'_'.$arTab["SITE_ID"];?>" value="<?=$arTab["OPTIONS"][$param]?>" />
									</div>
									<?//show template index components?>
									<?if($arIndexTemplate):?>
										<div class="template-wrapper js-sub block_<?=$key.'_'.$optionsSiteID;?>" data-key="<?=$key;?>" data-site="<?=$optionsSiteID;?>" <?=($key == $arTab["OPTIONS"][$optionCode] ? "style='display:block'" : "style='display:none'");?>>
											<div class="title"><?=Loc::getMessage("FRONT_TEMPLATE_GROUP")?></div>
											<div class="sub-block item">
												<?foreach($arIndexTemplate as $key2 => $arValue):?>
													<div data-parent='<?=$optionCode."_".$arTab["SITE_ID"]?>' class="block <?=$key?>" <?=($key == $arTab["OPTIONS"][$optionCode] ? "style='display:block'" : "style='display:none'");?>>
														<?=self::ShowAdminRow($key.'_'.$key2, $arValue, $arTab, $arControllerOption);?>
													</div>
												<?endforeach;?>
											</div>
										</div>
									<?endif;?>
								<?endif;?>
							<?endforeach;?>
						<?endif;?>
						<?if(isset($arOption['DEPENDENT_PARAMS']) && $arOption['DEPENDENT_PARAMS']): //dependent params?>
							<?foreach($arOption['DEPENDENT_PARAMS'] as $key => $arValue):?>
								<?
								$searchClass = "";
								if($bSearchMode)
								{
									if(isset($arValue["SEARCH_FIND"]) && $arValue["SEARCH_FIND"])
										$searchClass = 'visible_block';
								}?>
								<?if(!isset($arValue['CONDITIONAL_VALUE']) || ($arValue['CONDITIONAL_VALUE'] && $arTab["OPTIONS"][$optionCode] == $arValue['CONDITIONAL_VALUE']))
								{
									$style = "style='display:block'";
								}
								else
								{
									$style = "style='display:none'";
									$searchClass = "";
								}
								?>
								<div data-optioncode="<?=$key;?>" class="depend-block js_block1 <?=$key?> <?=((isset($arValue['TO_TOP']) && $arValue['TO_TOP']) ? "to_top" : "");?>  <?=$arValue["TYPE"];?> <?=((isset($arValue['ONE_BLOCK']) && $arValue['ONE_BLOCK'] == "Y") ? "ones" : "");?>" <?=((isset($arValue['CONDITIONAL_VALUE']) && $arValue['CONDITIONAL_VALUE']) ? "data-show='".$arValue['CONDITIONAL_VALUE']."'" : "");?> data-class="<?=$key;?>" data-search="<?=$searchClass;?>" data-parent='<?=$optionCode."_".$arTab["SITE_ID"]?>' <?=$style;?>>
									<div class="inner_wrapper <?=($arValue["TYPE"] == "checkbox" ? "checkbox" : "");?>">
										<?=self::ShowAdminRow($key, $arValue, $arTab, $arControllerOption);?>
									</div>
								</div>
							<?endforeach;?>
						<?endif;?>
					</div>
					<?
				}
			}
		}
	}

	public static function ShowAdminRow($optionCode, $arOption, $arTab, $arControllerOption, $btable = false){
		$optionName = $arOption['TITLE'];
		$optionType = $arOption['TYPE'];
		$optionList = $arOption['LIST'];
		$optionDefault = $arOption['DEFAULT'];
		$optionVal = $arTab['OPTIONS'][$optionCode];
		$optionSize = $arOption['SIZE'];
		$optionCols = $arOption['COLS'];
		$optionRows = $arOption['ROWS'];
		$optionChecked = $optionVal == 'Y' ? 'checked' : '';
		$optionDisabled = isset($arControllerOption[$optionCode]) || array_key_exists('DISABLED', $arOption) && $arOption['DISABLED'] == 'Y' ? 'disabled' : '';
		$optionSup_text = array_key_exists('SUP', $arOption) ? $arOption['SUP'] : '';
		$optionController = isset($arControllerOption[$optionCode]) ? "title='".GetMessage("MAIN_ADMIN_SET_CONTROLLER_ALT")."'" : "";
		$optionsSiteID = $arTab['SITE_ID'];
		$isArrayItem = strpos($optionCode, '_array_') !== false;
		?>

		<?if($optionType == 'dynamic_iblock'):?>
			<?if(Loader::IncludeModule('iblock')):?>
				<div colspan="2">
					<div class="title"  align="center"><b><?=$optionName;?></b></div>
					<?
					$arIblocks = array();
					$arSort = array(
						"SORT" => "ASC",
						"ID" => "ASC"
					);
					$arFilter = array(
						"ACTIVE" => "Y",
						"SITE_ID" => $optionsSiteID,
						"TYPE" => "aspro_max_form"
					);
					$rsItems = CIBlock::GetList($arSort, $arFilter);
					while($arItem = $rsItems->Fetch()){
						if($arItem["CODE"] != "aspro_max_example" && $arItem["CODE"] != "aspro_max_order_page")
						{
							$arItem['THEME_VALUE'] = Option::get(self::moduleID, htmlspecialcharsbx($optionCode)."_".htmlspecialcharsbx(strtoupper($arItem['CODE'])), '', $optionsSiteID);
							$arIblocks[] = $arItem;
						}
					}
					if($arIblocks):?>
						<table width="100%">
							<?foreach($arIblocks as $arIblock):?>
								<tr>
									<td class="adm-detail-content-cell-l" width="50%">
										<?=GetMessage("SUCCESS_SEND_FORM", array("#IBLOCK_CODE#" => $arIblock["NAME"]));?>
									</td>
									<td class="adm-detail-content-cell-r" width="50%">
										<input type="text" <?=((isset($arOption['PARAMS']) && isset($arOption['PARAMS']['WIDTH'])) ? 'style="width:'.$arOption['PARAMS']['WIDTH'].'"' : '');?> <?=$optionController?> size="<?=$optionSize?>" maxlength="255" value="<?=htmlspecialcharsbx($arIblock['THEME_VALUE'])?>" name="<?=htmlspecialcharsbx($optionCode)."_".htmlspecialcharsbx($arIblock['CODE'])."_".$optionsSiteID?>" <?=$optionDisabled?>>
									</td>
								</tr>
							<?endforeach;?>
						</table>
					<?endif;?>
				</div>
			<?endif;?>
		<?elseif($optionType == "note"):?>
			<?if($optionCode == 'GOALS_NOTE')
			{
				$FORMS_GOALS_LIST = '';
				if(\Bitrix\Main\Loader::includeModule('form'))
				{
					if($optionsSiteID)
					{
						if($arForms = CMaxCache::CForm_GetList($by = array('by' => 's_id', 'CACHE' => array('TAG' => 'forms')), $order = 'asc', array('SITE' => $optionsSiteID, 'SITE_EXACT_MATCH' => 'Y'), $is_filtered))
						{
							foreach($arForms as $arForm)
								$FORMS_GOALS_LIST .= $arForm['NAME'].' - <i>goal_webform_success_'.$arForm['ID'].'</i><br />';
						}
					}
				}
				$arOption["NOTE"] = str_replace('#FORMS_GOALS_LIST#', $FORMS_GOALS_LIST, $arOption["NOTE"]);
			}
			?>
			<?if(!$btable):?>
				<div colspan="2" align="center">
			<?else:?>
				<td colspan="2" align="center">
			<?endif;?>
				<?=BeginNote('align="center"');?>
				<?=$arOption["NOTE"]?>
				<?=EndNote();?>
			<?if(!$btable):?>
				</div>
			<?else:?>
				</td>
			<?endif;?>
		<?else:?>
			<?if(!$isArrayItem):?>
				<?if(!isset($arOption['HIDE_TITLE_ADMIN']) || $arOption['HIDE_TITLE_ADMIN'] != 'Y'):?>
					<?if(!$btable):?>
						<div class="title_wrapper<?=(in_array($optionType, array("multiselectbox", "textarea", "statictext", "statichtml")) ? "adm-detail-valign-top" : "")?>">
					<?else:?>
						<td class="adm-detail-content-cell-l <?=(in_array($optionType, array("multiselectbox", "textarea", "statictext", "statichtml")) ? "adm-detail-valign-top" : "")?>" width="50%">
					<?endif;?>
						<div class="subtitle">
							<?if($optionType == "checkbox"):?>
								<label for="<?=htmlspecialcharsbx($optionCode)."_".$optionsSiteID?>"><?=$optionName?></label>
							<?else:?>
								<?if($optionCode == 'PAGE_CONTACTS'):?>
									<?$optionName = Loc::getMessage("BLOCK_VIEW_TITLE");?>
								<?endif;?>
								<?=$optionName.($optionCode == "BASE_COLOR_CUSTOM" ? ' #' : '')?>
							<?endif;?>
							<?if(strlen($optionSup_text)):?>
								<span class="required"><sup><?=$optionSup_text?></sup></span>
							<?endif;?>
						</div>
					<?if(!$btable):?>
						</div>
					<?else:?>
						</td>
					<?endif;?>
				<?endif;?>
			<?endif;?>
			<?if(!$btable):?>
				<div class="value_wrapper">
			<?else:?>
				<td<?=(!$isArrayItem ? ' width="50%" ' : '')?>>
			<?endif;?>
				<?
				if($optionCode == 'PAGE_CONTACTS')
				{
					$siteDir = str_replace('//', '/', $arTab['SITE_DIR']).'/';
					if($arPageBlocks = self::GetIndexPageBlocks($_SERVER['DOCUMENT_ROOT'].$siteDir.'contacts', 'page_contacts_', '')){
						$arTmp = array();
						foreach($arPageBlocks as $page => $value)
						{
							$value_ = str_replace('page_contacts_', '', $value);
							$arTmp[$value_] = $value;
						}
						foreach($arOption['LIST'] as $key_list => $arValue)
						{
							if(isset($arTmp[$key_list]))
								;
							else
								unset($arOption['LIST'][$key_list]);
						}
					}
					$optionList = $arOption['LIST'];
				}
				elseif($optionCode == 'BLOG_PAGE')
				{
					$optionList = self::getActualParamsValue( $arTab, $arOption, '/components/bitrix/news/blog');
				}
				elseif($optionCode == 'SERVIES_PAGE_SECTIONS')
				{
					$optionList = self::getActualParamsValue( $arTab, $arOption, '/components/bitrix/news/services');
				}
				elseif($optionCode == 'SERVIES_PAGE_SECTION')
				{
					$optionList = self::getActualParamsValue( $arTab, $arOption, '/components/bitrix/news/services');
				}
				elseif($optionCode == 'SERVIES_PAGE')
				{
					$optionList = self::getActualParamsValue( $arTab, $arOption, '/components/bitrix/news/services', 'LIST');
				}
				elseif($optionCode == 'NEWS_PAGE')
				{
					$optionList = self::getActualParamsValue( $arTab, $arOption, '/components/bitrix/news/news');
				}
				elseif($optionCode == 'PROJECTS_PAGE')
				{
					$optionList = self::getActualParamsValue( $arTab, $arOption, '/components/bitrix/news/projects');
				}
				elseif($optionCode == 'STAFF_PAGE')
				{
					$optionList = self::getActualParamsValue( $arTab, $arOption, '/components/bitrix/news/staff');
				}
				elseif($optionCode == 'PARTNERS_PAGE')
				{
					$optionList = self::getActualParamsValue( $arTab, $arOption, '/components/bitrix/news/partners');
				}
				elseif($optionCode == 'PARTNERS_PAGE_DETAIL')
				{
					$optionList = self::getActualParamsValue( $arTab, $arOption, '/components/bitrix/news/partners', 'ELEMENT');
				}
				elseif($optionCode == 'CATALOG_PAGE_DETAIL')
				{
					$optionList = self::getActualParamsValue( $arTab, $arOption, '/components/bitrix/catalog/main', 'ELEMENT');
				}
				elseif($optionCode == 'USE_FAST_VIEW_PAGE_DETAIL')
				{
					$optionList = self::getActualParamsValue( $arTab, $arOption, '/components/bitrix/catalog/main', 'FAST_VIEW_ELEMENT');
				}
				elseif($optionCode == 'USE_FAST_VIEW_SERVICES')
				{
					$optionList = self::getActualParamsValue( $arTab, $arOption, '/components/bitrix/news/services', 'FAST_VIEW_ELEMENT');
				}
				elseif($optionCode == 'VACANCY_PAGE')
				{
					$optionList = self::getActualParamsValue( $arTab, $arOption, '/components/bitrix/news/vacancy');
				}
				elseif($optionCode == 'LICENSES_PAGE')
				{
					$optionList = self::getActualParamsValue( $arTab, $arOption, '/components/bitrix/news/licenses');
				}
				elseif($optionCode == 'GRUPPER_PROPS')
				{
					$optionList = array_merge(
						(array)$optionList,
						array(
							'GRUPPER' => array(
								'TITLE' => Loc::getMessage('GRUPPER_PROPS_GRUPPER'),
								'REQUIREMENTS' => array(
									array(
										'TITLE' => Loc::getMessage('MODULE_REQUIRED', array('#MODULE_NAME#' => 'redsign.grupper')),
										'PASSED' => true,
									)
								),
							),
							'WEBDEBUG' => array(
								'TITLE' => Loc::getMessage('GRUPPER_PROPS_WEBDEBUG'),
								'REQUIREMENTS' => array(
									array(
										'TITLE' => Loc::getMessage('MODULE_REQUIRED', array('#MODULE_NAME#' => 'webdebug.utilities')),
										'PASSED' => true,
									)
								),
							),
							'YENISITE_GRUPPER' => array(
								'TITLE' => Loc::getMessage('GRUPPER_PROPS_YENISITE_GRUPPER'),
								'REQUIREMENTS' => array(
									array(
										'TITLE' => Loc::getMessage('MODULE_REQUIRED', array('#MODULE_NAME#' => 'yenisite.infoblockpropsplus')),
										'PASSED' => true,
									)
								),
							),
						)
					);

					if(!IsModuleInstalled('redsign.grupper')){
						$optionList['GRUPPER']['DISABLED'] = 'Y';
						$optionList['GRUPPER']['TITLE'] .= Loc::getMessage('NOT_INSTALLED', array('#MODULE_NAME#' => 'redsign.grupper'));
						$optionList['GRUPPER']['REQUIREMENTS']['PASSED'] = false;
					}

					if(!IsModuleInstalled('webdebug.utilities')){
						$optionList['WEBDEBUG']['DISABLED'] = 'Y';
						$optionList['WEBDEBUG']['TITLE'] .= Loc::getMessage('NOT_INSTALLED', array('#MODULE_NAME#' => 'webdebug.utilities'));
						$optionList['WEBDEBUG']['REQUIREMENTS']['PASSED'] = false;
					}

					if(!IsModuleInstalled('yenisite.infoblockpropsplus')){
						$optionList['YENISITE_GRUPPER']['DISABLED'] = 'Y';
						$optionList['YENISITE_GRUPPER']['TITLE'] .= Loc::getMessage('NOT_INSTALLED', array('#MODULE_NAME#' => 'yenisite.infoblockpropsplus'));
						$optionList['YENISITE_GRUPPER']['REQUIREMENTS']['PASSED'] = false;
					}
				}
				elseif($optionCode == 'BONUS_SYSTEM')
				{
					$optionList = array_merge(
						(array)$optionList,
						array(
							'LOGICTIM' => array(
								'TITLE' => Loc::getMessage('BONUS_SYSTEM_LOGICTIM'),
								'REQUIREMENTS' => array(
									array(
										'TITLE' => Loc::getMessage('MODULE_REQUIRED', array('#MODULE_NAME#' => 'logictim.balls')),
										'PASSED' => true,
									)
								),
							),
						)
					);

					if(!IsModuleInstalled('logictim.balls')){
						$optionList['LOGICTIM']['DISABLED'] = 'Y';
						$optionList['LOGICTIM']['TITLE'] .= Loc::getMessage('NOT_INSTALLED', array('#MODULE_NAME#' => 'logictim.balls'));
						$optionList['LOGICTIM']['REQUIREMENTS']['PASSED'] = false;
					}
				}

				$bIBlocks = false;
				?>
				<?if($optionType == "checkbox"):?>
					<input type="checkbox" <?=((isset($arOption['DEPENDENT_PARAMS']) && $arOption['DEPENDENT_PARAMS']) ? "class='depend-check'" : "");?> <?=$optionController?> id="<?=htmlspecialcharsbx($optionCode)."_".$optionsSiteID?>" name="<?=htmlspecialcharsbx($optionCode)."_".$optionsSiteID?>" value="Y" <?=$optionChecked?> <?=$optionDisabled?> <?=(strlen($optionDefault) ? $optionDefault : "")?>>
				<?elseif($optionType == "text" || $optionType == "password"):?>
					<?if(isset($arOption["PICKER"]) && $arOption["PICKER"] == "Y"):?>
						<?
						$defaultCode = (($optionCode == "CUSTOM_BGCOLOR_THEME") ? 'MAIN' : 0);
						$customColor = str_replace('#', '', (strlen($optionVal) ? $optionVal : self::$arParametrsList[$defaultCode]['OPTIONS'][$arOption["PARENT_PROP"].'_GROUP']['ITEMS'][$arOption["PARENT_PROP"]]['LIST'][self::$arParametrsList[$defaultCode]['OPTIONS'][$arOption["PARENT_PROP"].'_GROUP']['ITEMS'][$arOption["PARENT_PROP"]]['DEFAULT']]['COLOR']));?>
						<div class="custom_block picker">
							<div class="options">
								<div class="base_color base_color_custom <?=($arTab['OPTIONS'][$arOption["PARENT_PROP"]] == 'CUSTOM' ? 'current' : '')?>" data-name="<?=htmlspecialcharsbx($optionCode)."_".$optionsSiteID?>" data-value="CUSTOM" data-color="#<?=$customColor?>">

									<span class="animation-all click_block" data-option-id="<?=$arOption["PARENT_PROP"]."_".$optionsSiteID?>" data-option-value="CUSTOM" <?=($arTab['OPTIONS'][$arOption["PARENT_PROP"]] == 'CUSTOM' ? "style='border-color:#".$customColor."'" : '')?>><span class="vals">#<?=($arTab['OPTIONS'][$arOption["PARENT_PROP"]] == 'CUSTOM' ? $customColor : '')?></span><span class="bg" data-color="<?=$customColor?>" style="background-color: #<?=$customColor?>;"></span></span>
									<input type="hidden" id="<?=htmlspecialcharsbx($optionCode)."_".$optionsSiteID?>" name="<?=htmlspecialcharsbx($optionCode)."_".$optionsSiteID?>" value="<?=$customColor?>" />
								</div>
							</div>
						</div>
					<?elseif($optionCode === 'PRIORITY_SECTION_DESCRIPTION_SOURCE'):?>
						<?
						$arPriority = explode(',', $optionVal);
						if(!in_array('SMARTSEO', $arPriority)){
							$arPriority[] = 'SMARTSEO';
						}
						if(!in_array('SOTBIT_SEOMETA', $arPriority)){
							$arPriority[] = 'SOTBIT_SEOMETA';
						}
						if(!in_array('IBLOCK', $arPriority)){
							$arPriority[] = 'IBLOCK';
						}
						?>
						<div class="item array js_block" data-class="<?=$optionCode;?>" data-search="">
							<div>
								<div class="aspro-admin-item">
									<?foreach($arPriority as $i => $priorityCode):?>
										<?
										$bDisabled = false;
										$subtitle = Loc::getMessage('PRIORITY_SECTION_DESCRIPTION_SOURCE_'.$priorityCode);
										if($priorityCode === 'SOTBIT_SEOMETA'){
											if(!IsModuleInstalled('sotbit.seometa')){
												$bDisabled = true;
												$subtitle .= ' '.Loc::getMessage('NOT_INSTALLED', array('#MODULE_NAME#' => 'sotbit.seometa'));
											}
										}
										?>
										<div class="wrapper <?=($bDisabled ? 'disabled' : '')?>">
											<div class="inner_wrapper">
												<div class="inner">
													<div class="title_wrapper"><div class="subtitle"><?=$subtitle?></div></div>
												</div>
												<div class="drag" title="<?=Loc::getMessage("TRANSFORM_ITEM")?>"></div>
												<input type="hidden" value="<?=$priorityCode?>" name="<?=htmlspecialcharsbx($optionCode).'_'.$optionsSiteID.'[]'?>" />
											</div>
										</div>
									<?endforeach;?>
								</div>
							</div>
						</div>
					<?elseif(strpos($optionCode, 'HEADER_PHONES_array_PHONE_ICON') !== false):?>
						<div class="iconset_value" data-code="header_phones" title="<?=htmlspecialcharsbx($arOption['TITLE'])?>"><div class="iconset_value_wrap"><?=\Aspro\Max\Iconset::showIcon($optionVal, false, false)?></div><input type="hidden" value="<?=htmlspecialcharsbx($optionVal)?>" name="<?=htmlspecialcharsbx($optionCode)."_".$optionsSiteID?>"></div>
					<?else:?>
						<input type="<?=$optionType?>" <?=((isset($arOption['PARAMS']) && isset($arOption['PARAMS']['WIDTH'])) ? 'style="width:'.$arOption['PARAMS']['WIDTH'].'"' : '');?> <?=$optionController?> <?=($arOption['PLACEHOLDER'] ? "placeholder='".$arOption['PLACEHOLDER']."'" : '');?> size="<?=$optionSize?>" maxlength="255" value="<?=htmlspecialcharsbx($optionVal)?>" name="<?=htmlspecialcharsbx($optionCode)."_".$optionsSiteID?>" <?=$optionDisabled?> <?=($optionCode == "password" ? "autocomplete='off'" : "")?>>
					<?endif;?>
				<?elseif($optionType == "selectbox"):?>
					<?
					if(isset($arOption['TYPE_SELECT']))
					{
						if($arOption['TYPE_SELECT'] == 'STORES')
						{
							static $bStores;
							if ($bStores === null){
								$bStores = false;
								if(\Bitrix\Main\Loader::includeModule('catalog')){
									if(class_exists('CCatalogStore')){
										$dbRes = CCatalogStore::GetList(array(), array(), false, false, array());
										if($c = $dbRes->SelectedRowsCount()){
											$bStores = true;
										}
									}
								}
							}
							if(!$bStores)
								unset($optionList['STORES']);
						}
						elseif($arOption['TYPE_SELECT'] == 'IBLOCK')
						{
							\Bitrix\Main\Loader::includeModule('iblock');
							$rsIBlock=CIBlock::GetList(array("SORT" => "ASC", "ID" => "DESC"), array("LID" => $optionsSiteID));
							$arIBlocks=array();
							while($arIBlock=$rsIBlock->Fetch()){
								$arIBlocks[$arIBlock["ID"]]["NAME"]="(".$arIBlock["ID"].") ".$arIBlock["NAME"]."[".$arIBlock["CODE"]."]";
								$arIBlocks[$arIBlock["ID"]]["CODE"]=$arIBlock["CODE"];
							}
							if($arIBlocks)
							{
								$bIBlocks = true;
							}
						}
						elseif($arOption['TYPE_SELECT'] == 'GROUP')
						{
							static $arUserGroups;
							if($arUserGroups === null){
								$DefaultGroupID = 0;
								$rsGroups = CGroup::GetList($by = "id", $order = "asc", array("ACTIVE" => "Y"));
								while($arItem = $rsGroups->Fetch()){
									$arUserGroups[$arItem["ID"]] = $arItem["NAME"];
									if($arItem["ANONYMOUS"] == "Y"){
										$DefaultGroupID = $arItem["ID"];
									}
								}
							}
							$optionList = $arUserGroups;
						} 
						elseif($arOption['TYPE_SELECT'] == 'CURRENCY')
						{
							static $arCurrency;
							if($arCurrency === null){
								$arCurrency['N'] = '- '.Loc::getMessage("SERVICES_CURRENCY_EMPTY");
								$dbCurrency = CCurrency::GetList(($by="name"), ($order="asc"));
								while($res_cur = $dbCurrency->Fetch())
								{
									$arCurrency[$res_cur['CURRENCY']] = $res_cur["FULL_NAME"];
								}								
							}
							$optionList = $arCurrency;
						}
						elseif(is_array($arOption['TYPE_SELECT']) && $arOption['TYPE_SELECT']['TYPE'] == 'JS')
						{?>
							<script>
								if (typeof allCountries === 'undefined') {
									$.ajax({
										url: "<?=$arOption['TYPE_SELECT']['SRC'];?>",
										async: false,
										success: function (data) {}
									})
								}
								var selectOptions = selectOptions || '';
								if (allCountries && !selectOptions) {
									for (let i = 0; i < allCountries.length; i++) {
										selectOptions += '<option value="'+allCountries[i].iso2+'">'+allCountries[i].name+'</option>';
									}
									$('select[name=<?=$optionCode."_".$optionsSiteID;?>]').html(selectOptions)
								}
							</script>
							<?
						}
						elseif($arOption['TYPE_SELECT'] == 'CURRENCIES')
						{
							static $arCurrencies;
							if (Loader::includeModule('currency')) {
								if($arCurrencies === null){
									$currencyIterator = CCurrency::GetList($by='sort',$order='asc','ru');
									while ($currency = $currencyIterator->Fetch()) {
										$arCurrencies[$currency['CURRENCY']] = "[".$currency['CURRENCY']."] ".$currency['FULL_NAME'];
									}
								}
							}
							$optionList = $arCurrencies;
						}	
					}

					if(!is_array($optionList)) $optionList = (array)$optionList;
					$arr_keys = array_keys($optionList);

					if(isset($arOption["TYPE_EXT"]) && $arOption["TYPE_EXT"] == "colorpicker"):?>
						<div class="bases_block">
							<input type="hidden" id="<?=$optionCode?>" name="<?=$optionCode."_".$optionsSiteID;?>" value="<?=$optionVal?>" />
							<?foreach($arOption['LIST'] as $colorCode => $arColor):?>
								<?if($colorCode !== 'CUSTOM'):?>
									<div class="base_color <?=($colorCode == $optionVal ? 'current' : '')?>" data-value="<?=$colorCode?>" data-color="<?=$arColor['COLOR']?>">
										<span class="animation-all click_block status-block"  data-option-id="<?=$optionCode?>" data-option-value="<?=$colorCode?>" title="<?=$arColor['TITLE']?>"><span style="background-color: <?=$arColor['COLOR']?>;"></span></span>
									</div>
								<?endif;?>
							<?endforeach;?>
						</div>
					<?elseif((isset($arOption["IS_ROW"]) && $arOption["IS_ROW"] == "Y") ||(isset($arOption["SHOW_IMG"]) && $arOption["SHOW_IMG"] == "Y")):?>
						<?if($arOption["HIDDEN"] != "Y"):?>
							<div class="block_with_img <?=(isset($arOption["ROWS"]) && $arOption["ROWS"] == "Y" ? 'in_row' : '');?>">
								<input type="hidden" id="<?=$optionCode?>" name="<?=$optionCode."_".$optionsSiteID;?>" value="<?=$optionVal?>" />
								<div class="rows flexbox">
									<?foreach($arOption['LIST'] as $code => $arValue):?>
										<?if($arValue["TITLE"] == 'page_contacts_custom' || $arValue["TITLE"] == 'list_elements_custom' || $arValue["TITLE"] == 'element_custom')
											$arValue["TITLE"] = 'custom';?>
										<div>
											<div class="link-item animation-boxs block status-block <?=($code == $optionVal ? 'current' : '')?>" <?=($code == $optionVal ? 'data-current="Y"' : '')?> data-value="<?=$code?>" data-site="<?=$optionsSiteID;?>">
												<span class="title"><?=$arValue["TITLE"];?></span>
												<?if($arValue["IMG"]):?>
													<span><img src="<?=$arValue["IMG"];?>" alt="<?=$arValue["TITLE"];?>" title="<?=$arValue["TITLE"];?>" class="<?=($arValue["COLORED_IMG"] ? 'colored_theme_bg' : '')?>" /></span>
												<?if(isset($arValue['ADDITIONAL_OPTIONS']) && $arValue['ADDITIONAL_OPTIONS']):?>
													<div class="subs">
														<?foreach($arValue['ADDITIONAL_OPTIONS'] as $key => $arSubOption):?>
															<div class="sub-item inner_wrapper <?=($arSubOption['TYPE'] ?? 'checkbox');?>">
																<?//print_r($arSubOption);?>
																<?$codeTmp = (strpos($optionCode, '_TEMPLATE') !== false ? str_replace('_TEMPLATE', '_', $optionCode).$key.'_'.$code : $key.'_'.$code);?>
																<?=self::ShowAdminRow($codeTmp, $arSubOption, $arTab, array())?>
															</div>
														<?endforeach;?>
													</div>
												<?endif;?>
												<?endif;?>
											</div>
										</div>
									<?endforeach;?>
								</div>
							</div>
						<?endif;?>
					<?else:?>
						<select <?=((isset($arOption['DEPENDENT_PARAMS']) && $arOption['DEPENDENT_PARAMS']) ? "class='depend-check'" : "");?> data-site="<?=$optionsSiteID?>" name="<?=htmlspecialcharsbx($optionCode)."_".$optionsSiteID?>" <?=$optionController?> <?=$optionDisabled?>>
							<?if($bIBlocks)
							{
								foreach($arIBlocks as $key => $arValue) {
									$selected="";
									if(!$optionVal && $optionCode === "SERVICES_IBLOCK_ID" && $arValue["CODE"] === "aspro_max_services"){
										$selected="selected";
									}elseif(!$optionVal && $optionCode === "CATALOG_IBLOCK_ID" && $arValue["CODE"]=="aspro_max_catalog"){
										$selected="selected";
									}elseif($optionVal && $optionVal==$key){
										$selected="selected";
									}?>
									<option value="<?=$key;?>" <?=$selected;?>><?=htmlspecialcharsbx($arValue["NAME"]);?></option>
								<?}
							}
							elseif(
								$optionCode == 'GRUPPER_PROPS' ||
								$optionCode == 'BASKET_FILE_DOWNLOAD_TEMPLATE'
							){
								foreach($optionList as $key => $arValue):
									$selected = $disabled = '';

									if($optionVal && $optionVal == $key)
										$selected = 'selected';

									if(isset($arValue['DISABLED']))
										$disabled = 'disabled';
									?>
									<option value="<?=$key;?>" <?=$selected?> <?=$disabled?>><?=htmlspecialcharsbx($arValue["TITLE"]);?></option>
								<?endforeach;?>
							<?}
							else
							{
								for($j = 0, $c = count($arr_keys); $j < $c; ++$j):?>
									<option value="<?=$arr_keys[$j]?>" <?if($optionVal == $arr_keys[$j]) echo "selected"?> <?=(isset($optionList[$arr_keys[$j]]['DISABLED']) ? 'disabled' : '');?>><?=htmlspecialcharsbx((is_array($optionList[$arr_keys[$j]]) ? $optionList[$arr_keys[$j]]["TITLE"] : $optionList[$arr_keys[$j]]))?></option>
								<?endfor;
							}?>
						</select>
					<?endif;?>
				<?elseif($optionType == "multiselectbox"):?>
					<?
					if(isset($arOption['TYPE_SELECT']))
					{
						if($arOption['TYPE_SELECT'] == 'STORES')
						{
							$arStores = [];
							if (\Bitrix\Main\Loader::includeModule('catalog')) {
								if (class_exists('CCatalogStore')) {
									$dbRes = CCatalogStore::GetList(array(), array(), false, false, array('ID', 'TITLE'));
									while ($arStore = $dbRes->Fetch()) {
										$arStores[$arStore['ID']] = '['.$arStore['ID'].'] '.$arStore['TITLE'];
									}
								}
								if ($arStores) {
									$optionList = $arStores;
								}
							}
						}
						if($arOption['TYPE_SELECT'] == 'PRICES')
						{
							\Bitrix\Main\Loader::includeModule('catalog');
							$arPrices = array();
							$rsPrice = CCatalogGroup::GetList(array("SORT" => "ASC"), array());
							while($arPrice = $rsPrice->GetNext())
							{
								$name = ($arPrice["NAME_LANG"] ? $arPrice["NAME_LANG"] : $arPrice["NAME"]);
								$arPrices[$arPrice["ID"]]["TITLE"] = "(".$arPrice["ID"].") ".$name." [".$arPrice["XML_ID"]."]";
							}
							$optionList = $arPrices;
						}
						elseif($arOption['TYPE_SELECT'] == 'PRICES_CODE')
						{
							\Bitrix\Main\Loader::includeModule('catalog');
							$arPrices = array();
							$rsPrice = CCatalogGroup::GetList(array("SORT" => "ASC"), array());
							while($arPrice = $rsPrice->GetNext())
							{
								$name = ($arPrice["NAME_LANG"] ? $arPrice["NAME_LANG"] : $arPrice["NAME"]);
								$arPrices[$arPrice["NAME"]]["TITLE"] = "(".$arPrice["ID"].") ".$name." [".$arPrice["XML_ID"]."]";
							}
							$optionList = $arPrices;
						}
						elseif($arOption['TYPE_SELECT'] == 'IBLOCK')
						{
							static $bIBlocks;
							if ($bIBlocks === null || !$arIBlocks){
								$bIBlocks = false;
								\Bitrix\Main\Loader::includeModule('iblock');
								$rsIBlock=CIBlock::GetList(array("SORT" => "ASC", "ID" => "DESC"), array("LID" => $optionsSiteID));
								$arIBlocks=array();
								while($arIBlock=$rsIBlock->Fetch()){
									$arIBlocks[$arIBlock["ID"]]["NAME"]="(".$arIBlock["ID"].") ".$arIBlock["NAME"]." [".$arIBlock["CODE"]."]";
									$arIBlocks[$arIBlock["ID"]]["CODE"]=$arIBlock["CODE"];
								}
								if($arIBlocks)
								{
									$bIBlocks = true;
								}
							}
						} elseif(is_array($arOption['TYPE_SELECT']) && $arOption['TYPE_SELECT']['TYPE'] == 'JS') {?>
							<script>
								if (typeof allCountries === 'undefined') {
									$.ajax({
										url: "<?=$arOption['TYPE_SELECT']['SRC'];?>",
										async: false,
										success: function (data) {}
									})
								}
								var selectOptions = selectOptions || '';
								if (allCountries && !selectOptions) {
									for (let i = 0; i < allCountries.length; i++) {
										selectOptions += '<option value="'+allCountries[i].iso2+'">'+allCountries[i].name+'</option>\n';
									}
								}
							</script>
							<?
						}
						elseif($arOption['TYPE_SELECT'] == 'IBLOCK_PROPS')
						{
							
							if (Loader::includeModule('iblock')) {
								if($arOption['PROPS_SETTING']){
									
									$arFilter = [];
									$iblockIDCatalog = Option::get(self::moduleID, 'CATALOG_IBLOCK_ID', CMaxCache::$arIBlocks[$optionsSiteID]['aspro_max_catalog']['aspro_max_catalog'][0], $optionsSiteID);
									$iblockIDSCU = CCatalog::GetByID($iblockIDCatalog)['OFFERS_IBLOCK_ID'];
									if ($iblockIDCatalog) {
										$arFilter['IBLOCK_ID'] = $iblockIDSCU;
									}
									if ($arOption['PROPS_SETTING']['FILTER']) {
										$arFilter = array_merge($arFilter, $arOption['PROPS_SETTING']['FILTER']);
									}
									$arIblockProps = ['' => '-'];
									$rsProps = CIBlockProperty::GetList(
										array('SORT' => 'ASC', 'NAME' => 'ASC'),
										$arFilter,
									);
									while ($arProp = $rsProps->GetNext()) {
										if ($arOption['PROPS_SETTING']['IS_TREE']) {
											if (
												'L' == $arProp['PROPERTY_TYPE']
												|| 'E' == $arProp['PROPERTY_TYPE']
												|| ('S' == $arProp['PROPERTY_TYPE'] && 'directory' == $arProp['USER_TYPE'])
											) {
												$arIblockProps[$arProp['CODE']] = "[{$arProp['ID']}] {$arProp['NAME']} ({$arProp['CODE']})";
											}
										} elseif ($arOption['PROPS_SETTING']['IS_SELECTION_PICTURE']) {
											if ('S' == $arProp['PROPERTY_TYPE'] && 'directory' == $arProp['USER_TYPE'] && CIBlockPriceTools::checkPropDirectory($arProp) && strlen($arProp['USER_TYPE_SETTINGS']['TABLE_NAME'])){
												$arIblockProps[$arProp['CODE']] = "[{$arProp['ID']}] {$arProp['NAME']} ({$arProp['CODE']})";
											}
										}
										else {
											$arIblockProps[$arProp['CODE']] = "[{$arProp['ID']}] {$arProp['NAME']} ({$arProp['CODE']})";
										}
									}
								}
							}
							$optionList = $arIblockProps;
						}
						elseif($arOption['TYPE_SELECT'] == 'GROUP')
						{
							static $arUserGroups;
							if($arUserGroups === null){
								$DefaultGroupID = 0;
								$rsGroups = CGroup::GetList($by = "id", $order = "asc", array("ACTIVE" => "Y"));
								while($arItem = $rsGroups->Fetch()){
									$arUserGroups[$arItem["ID"]] = $arItem["NAME"];
									if($arItem["ANONYMOUS"] == "Y"){
										$DefaultGroupID = $arItem["ID"];
									}
								}
							}
							$optionList = $arUserGroups;
						}
						elseif($arOption['TYPE_SELECT'] == 'SITE')
						{
							static $arSites;
							if($arSites === null){
								$rsSites = \CSite::GetList($by="sort", $order="desc", array("ACTIVE" => "Y"));

								while($arItem = $rsSites->Fetch()){
									$arSites[$arItem["ID"]] = $arItem["NAME"];
								}
							}
							$optionList = $arSites;
						}
						elseif($arOption['TYPE_SELECT'] == 'PRICES_TYPE')
						{
							static $arPricesType;
							if (Loader::includeModule('catalog')) {
								if($arPricesType === null){
									$arPricesType = CCatalogIBlockParameters::getPriceTypesList();
								}
							}
							$optionList = $arPricesType;
							if (!$arPricesType) {
								$textNote = GetMessage('NEED_ADD_PRICES_TYPE');
							}
						}
					}

					if(!is_array($optionList)) $optionList = (array)$optionList;
					$arr_keys = array_keys($optionList);
					$optionValTmp = $optionVal;
					$optionVal = explode(",", $optionVal);
					if(!is_array($optionVal)) $optionVal = (array)$optionVal;
					?>
					<?if(isset($arOption['SHOW_CHECKBOX']) && $arOption['SHOW_CHECKBOX'] == 'Y'):?>
						<div class="props">
							<?for($j = 0, $c = count($arr_keys); $j < $c; ++$j):?>
								<div class="outer_wrapper <?=(in_array($arr_keys[$j], $optionVal) ? "checked" : "");?>">
									<div class="inner_wrapper checkbox">
										<div class="title_wrapper">
											<div class="subtitle"><label for="<?=$optionCode."_".$optionsSiteID."_".$j?>"><?=htmlspecialcharsbx((is_array($optionList[$arr_keys[$j]]) ? $optionList[$arr_keys[$j]]["TITLE"] : $optionList[$arr_keys[$j]]))?></label></div>
										</div>
										<div class="value_wrapper">
											<input type="checkbox" id="<?=$optionCode."_".$optionsSiteID."_".$j?>" name="temp_<?=htmlspecialcharsbx($optionCode)."_".$optionsSiteID?>" value="<?=$arr_keys[$j]?>" <?=(in_array($arr_keys[$j], $optionVal) ? "checked" : "");?>><label for="<?=$optionCode."_".$optionsSiteID."_".$j?>"></label>
										</div>
									</div>
								</div>
							<?endfor;?>
						</div>
					<?endif;?>
					<?//else:?>
						<select data-site="<?=$optionsSiteID?>" size="<?=$optionSize?>" <?=$optionController?> <?=$optionDisabled?> multiple name="<?=htmlspecialcharsbx($optionCode)."_".$optionsSiteID?>[]" >
							<?
							if(
								$optionCode === 'SEARCHTITLE_CATALOG_CATS' ||
								$optionCode === 'SEARCHTITLE_SITE_CATS'
							){
								$selected = is_array($optionVal) && $optionVal && in_array('main', $optionVal) ? 'selected' : '';
								?><option value="main" <?=$selected?>><?=Loc::getMessage("SEARCHTITLE_CATS_MAIN")?></option><?
								if(\CMaxCache::$arIBlocks[$optionsSiteID]){
									foreach(CMaxCache::$arIBlocks[$optionsSiteID] as $iblockTypeId => $arIblockCodes) {
										?><optgroup label="[iblock] <?=$iblockTypeId?>"><?
											$key = 'iblock_'.$iblockTypeId;
											$selected = is_array($optionVal) && $optionVal && in_array($key, $optionVal) ? 'selected' : '';
											?><option value="<?=$key?>" <?=$selected?>><?=Loc::getMessage("SEARCHTITLE_CATS_ALL")?></option><?

											foreach($arIblockCodes as $iblockCode => $iblocksIDs) {
												if ($iblocksIDs) {
													foreach($iblocksIDs as $iblockId) {
														$key = 'iblock_'.$iblockId;
														$selected = is_array($optionVal) && $optionVal && in_array($key, $optionVal) ? 'selected' : '';
														?><option value="<?=$key?>" <?=$selected?>><?='['.CMaxCache::$arIBlocksInfo[$iblockId]["ID"].'] '.htmlspecialcharsbx(CMaxCache::$arIBlocksInfo[$iblockId]["NAME"])?></option><?
													}
												}
											}
										?></optgroup><?
									}
								}

								if (Loader::includeModule('blog')){
									static $arSearchBlogs;

									if (!isset($arSearchBlogs)) {
										$rsBlog = \CBlog::GetList();
										while ($arBlog = $rsBlog->Fetch()) {
											$arSearchBlogs[$arBlog['ID']] = '['.$arBlog['ID'].'] '.$arBlog['NAME'];
										}
									}

									if ($arSearchBlogs) {
										$selected = is_array($optionVal) && $optionVal && in_array('blog_all', $optionVal) ? 'selected' : '';
										?><optgroup label="<?=Loc::getMessage('SEARCHTITLE_CATS_FORUM')?>"><?
										?><option value="blog_all" <?=$selected?>><?=Loc::getMessage("SEARCHTITLE_CATS_ALL")?></option><?
										foreach($arSearchBlogs as $key => $name) {
											$key = 'blog_'.$key;
											$selected = is_array($optionVal) && $optionVal && in_array($key, $optionVal) ? 'selected' : '';
											?><option value="<?=$key;?>" <?=$selected;?>><?=htmlspecialcharsbx($name);?></option><?
										}
										?></optgroup><?
									}
								}

								if (Loader::includeModule('forum')){
									static $arSearchForum;

									if (!isset($arSearchForum)) {
										$rsForum = \CForumNew::GetList();
										while ($arForum = $rsForum->Fetch()) {
											$arSearchForum[$arForum['ID']] = '['.$arForum['ID'].'] '.$arForum['NAME'];
										}
									}

									if ($arSearchForum) {
										$selected = is_array($optionVal) && $optionVal && in_array('forum_all', $optionVal) ? 'selected' : '';
										?><optgroup label="<?=Loc::getMessage('SEARCHTITLE_CATS_FORUM')?>"><?
										?><option value="forum_all" <?=$selected?>><?=Loc::getMessage("SEARCHTITLE_CATS_ALL")?></option><?
										foreach($arSearchForum as $key => $name) {
											$key = 'forum_'.$key;
											$selected = is_array($optionVal) && $optionVal && in_array($key, $optionVal) ? 'selected' : '';
											?><option value="<?=$key;?>" <?=$selected;?>><?=htmlspecialcharsbx($name);?></option><?
										}
										?></optgroup><?
									}
								}
							}
							elseif($bIBlocks){
								foreach($arIBlocks as $key => $arValue) {
									$selected = is_array($optionVal) && $optionVal && in_array($key, $optionVal) ? 'selected' : '';
									?><option value="<?=$key;?>" <?=$selected;?>><?=htmlspecialcharsbx($arValue["NAME"]);?></option><?
								}
							}
							else {
								?>
								<?for($j = 0, $c = count($arr_keys); $j < $c; ++$j):?>
									<option value="<?=$arr_keys[$j]?>" <?if(in_array($arr_keys[$j], $optionVal)) echo "selected"?>><?=htmlspecialcharsbx((is_array($optionList[$arr_keys[$j]]) ? $optionList[$arr_keys[$j]]["TITLE"] : $optionList[$arr_keys[$j]]))?></option>
								<?endfor;?>
								<?
							}
							?>
						</select>
					<?if(is_array($arOption['TYPE_SELECT']) && $arOption['TYPE_SELECT']['TYPE'] == 'JS') {?>
						<?if ($arOption['TYPE_SELECT']['NAME'] == 'PHONE') {?>
							<script>
								if (allCountries && selectOptions) {
									$('select[name^=<?=$optionCode."_".$optionsSiteID;?>]').html(selectOptions);
									<?if ($optionVal):?>
										let values = '<?=$optionValTmp;?>';
										$('select[name^=<?=$optionCode."_".$optionsSiteID;?>] option').each(function(node){
											if (values.includes(this.value)) {
												this.setAttribute('selected', 'selected')
											}
										})
									<?endif;?>
								}
							</script>
						<?}?>
					<?}?>
				<?elseif($optionType == "textarea"):?>
					<textarea <?=$optionController?> <?=$optionDisabled?> rows="<?=$optionRows?>" cols="<?=$optionCols?>" name="<?=htmlspecialcharsbx($optionCode)."_".$optionsSiteID?>"><?=htmlspecialcharsbx($optionVal)?></textarea>
				<?elseif($optionType == "statictext"):?>
					<?=htmlspecialcharsbx($optionVal)?>
				<?elseif($optionType == "statichtml"):?>
					<?=$optionVal?>
				<?elseif($optionType == "file"):?>
					<?$val = unserialize(Option::get(self::moduleID, $optionCode, serialize(array()), $optionsSiteID));

					$arOption['MULTIPLE'] = 'N';
					if($optionCode == 'LOGO_IMAGE' || $optionCode == 'LOGO_IMAGE_WHITE'){
						$arOption['WIDTH'] = 394;
						$arOption['HEIGHT'] = 140;
					}
					elseif($optionCode == 'FAVICON_IMAGE'){
						$arOption['WIDTH'] = 16;
						$arOption['HEIGHT'] = 16;
					}
					elseif($optionCode == 'APPLE_TOUCH_ICON_IMAGE'){
						$arOption['WIDTH'] = 180;
						$arOption['HEIGHT'] = 180;
					}
					self::__ShowFilePropertyField($optionCode."_".$optionsSiteID, $arOption, $val);?>
				<?elseif($optionType === 'includefile'):?>
					<?
					if(!is_array($arOption['INCLUDEFILE'])){
						$arOption['INCLUDEFILE'] = array($arOption['INCLUDEFILE']);
					}
					foreach($arOption['INCLUDEFILE'] as $includefile){
						$includefile = str_replace('//', '/', str_replace('#SITE_DIR#', $arTab['SITE_DIR'].'/', $includefile));
						$includefile = str_replace('//', '/', str_replace('#TEMPLATE_DIR#', $arTab['TEMPLATE']['DIR'].'/', $includefile));
						if(strpos($includefile, '#') === false){
							$template = (isset($arOption['TEMPLATE']) && strlen($arOption['TEMPLATE']) ? 'include_area.php' : $arOption['TEMPLATE']);
							if(strpos($includefile, 'invis-counter') === false)
							{
								$href = (!strlen($includefile) ? "javascript:;" : "javascript: new BX.CAdminDialog({'content_url':'/bitrix/admin/public_file_edit.php?site=".$arTab['SITE_ID']."&bxpublic=Y&from=includefile".($arOption['NO_EDITOR'] == 'Y' ? "&noeditor=Y" : "")."&templateID=".$arTab['TEMPLATE']['ID']."&path=".$includefile."&lang=".LANGUAGE_ID."&template=".$template."&subdialog=Y&siteTemplateId=".$arTab['TEMPLATE']['ID']."','width':'1009','height':'503'}).Show();");
							}
							else
							{

								$href = (!strlen($includefile) ? "javascript:;" : "javascript: new BX.CAdminDialog({'content_url':'/bitrix/admin/public_file_edit.php?site=".$arTab['SITE_ID']."&bxpublic=Y&from=includefile&noeditor=Y&templateID=".$arTab['TEMPLATE']['ID']."&path=".$includefile."&lang=".LANGUAGE_ID."&template=".$template."&subdialog=Y&siteTemplateId=".$arTab['TEMPLATE']['ID']."','width':'1009','height':'503'}).Show();");
							}
							?><a class="adm-btn" href="<?=$href?>" name="<?=htmlspecialcharsbx($optionCode)."_".$optionsSiteID?>" title="<?=GetMessage('OPTIONS_EDIT_BUTTON_TITLE')?>"><?=GetMessage('OPTIONS_EDIT_BUTTON_TITLE')?></a>&nbsp;<?
						}
					}
					?>
				<?endif;?>
			<?if(!$btable):?>
				</div>
			<?else:?>
				</td>
			<?endif;?>
		<?endif;?>
	<?}

	public static function getActualParamsValue($arTab, $arOption, $path, $field = 'ELEMENTS'){
		$optionList = $arOption['LIST'];
		// get site template
		$arTemplate = self::GetSiteTemplate($arTab['SITE_ID']);
		if($arTemplate && $arTemplate['PATH'])
		{
			if($arPageBlocks = self::GetComponentTemplatePageBlocks($arTemplate['PATH'].$path))
			{
				foreach($arOption['LIST'] as $key_list => $arValue)
				{
					if(isset($arPageBlocks[$field][$key_list]))
						;
					elseif($key_list != 'NO')
						unset($arOption['LIST'][$key_list]);
				}
			}
			$optionList = $arOption['LIST'];
		}
		return $optionList;
	}

	public static function UpdateFrontParametrsValues(){
		$arBackParametrs = self::GetBackParametrsValues(SITE_ID);
		if($arBackParametrs['THEME_SWITCHER'] === 'Y'){
			if($_REQUEST && isset($_REQUEST['BASE_COLOR']))
			{
				if($_REQUEST['THEME'] === 'default')
				{
					if(self::$arParametrsList && is_array(self::$arParametrsList))
					{
						foreach(self::$arParametrsList as $blockCode => $arBlock)
						{
							unset($_SESSION['THEME'][SITE_ID]);
							$_SESSION['THEME'][SITE_ID] = null;

							if(isset($_SESSION['THEME_ACTION']))
							{
								unset($_SESSION['THEME_ACTION'][SITE_ID]);
								$_SESSION['THEME_ACTION'][SITE_ID] = null;
							}
						}
					}
					Option::set(self::moduleID, 'NeedGenerateCustomTheme', 'Y', SITE_ID);
					Option::set(self::moduleID, 'NeedGenerateCustomThemeBG', 'Y', SITE_ID);
				}
				else{
					if(self::$arParametrsList && is_array(self::$arParametrsList)){
						foreach(self::$arParametrsList as $blockCode => $arBlock){
							if($arBlock['OPTIONS'] && is_array($arBlock['OPTIONS'])){
								foreach($arBlock['OPTIONS'] as $optionCode => $arOption){
									if($arOption['THEME'] === 'Y'){
										if(isset($_REQUEST[$optionCode])){
											if($optionCode == 'BASE_COLOR_CUSTOM' || $optionCode == 'CUSTOM_BGCOLOR_THEME')
												$_REQUEST[$optionCode] = self::CheckColor($_REQUEST[$optionCode]);

											if($optionCode == 'BASE_COLOR' && $_REQUEST[$optionCode] === 'CUSTOM')
												Option::set(self::moduleID, "NeedGenerateCustomTheme", 'Y', SITE_ID);

											if($optionCode == 'CUSTOM_BGCOLOR_THEME' && $_REQUEST[$optionCode] === 'CUSTOM')
												Option::set(self::moduleID, "NeedGenerateCustomThemeBG", 'Y', SITE_ID);

											if(isset($arOption['LIST']))
											{
												if($arOption['TYPE'] == 'multiselectbox') {
													if(!$_REQUEST[$optionCode]) {
														$_SESSION['THEME'][SITE_ID][$optionCode] = 'N';
													} else {
														$_SESSION['THEME'][SITE_ID][$optionCode] = $_REQUEST[$optionCode];
													}
												} else {
													if(isset($arOption['LIST'][$_REQUEST[$optionCode]])){
														$_SESSION['THEME'][SITE_ID][$optionCode] = $_REQUEST[$optionCode];
													}
													else{
														$_SESSION['THEME'][SITE_ID][$optionCode] = $arOption['DEFAULT'];
													}
												}
											}
											else
											{
												$_SESSION['THEME'][SITE_ID][$optionCode] = $_REQUEST[$optionCode];
											}
											/*if($optionCode == 'ORDER_VIEW')
												self::ClearSomeComponentsCache(SITE_ID);*/

											if(isset($arOption['ADDITIONAL_OPTIONS']) && $arOption['ADDITIONAL_OPTIONS']) //get additional params default value
											{
												if($arOption['LIST'])
												{
													foreach($arOption['LIST'] as $key => $arListOption)
													{
														if($arListOption['ADDITIONAL_OPTIONS'])
														{
															foreach($arListOption['ADDITIONAL_OPTIONS'] as $key2 => $arListOption2)
															{
																if($_REQUEST[$key2.'_'.$key])
																{
																	$_SESSION['THEME'][SITE_ID][$key2.'_'.$key] = $_REQUEST[$key2.'_'.$key];
																}
																else
																{
																	if($arListOption2['TYPE'] == 'checkbox')
																		$_SESSION['THEME'][SITE_ID][$key2.'_'.$key] = 'N';
																	else
																		$_SESSION['THEME'][SITE_ID][$key2.'_'.$key] = $arListOption2['DEFAULT'];
																}
															}
														}
													}
												}
											}

											if(isset($arOption['SUB_PARAMS']) && $arOption['SUB_PARAMS']) //nested params
											{

												if($arOption['TYPE'] == 'selectbox' && isset($arOption['LIST']))
												{
													$propValue = $_SESSION['THEME'][SITE_ID][$optionCode];
													if($arOption['SUB_PARAMS'][$propValue])
													{
														foreach($arOption['SUB_PARAMS'][$propValue] as $subkey => $arSubvalue)
														{
															if($_REQUEST[$propValue.'_'.$subkey])
															{
																$_SESSION['THEME'][SITE_ID][$propValue.'_'.$subkey] = $_REQUEST[$propValue.'_'.$subkey];
															}
															else
															{
																if($arSubvalue['TYPE'] == 'checkbox'  && !isset($arSubvalue["VISIBLE"]))
																	$_SESSION['THEME'][SITE_ID][$propValue.'_'.$subkey] = 'N';
																else
																	$_SESSION['THEME'][SITE_ID][$propValue.'_'.$subkey] = $arSubvalue['DEFAULT'];
															}

															//set fon index components
															if(isset($arSubvalue['FON']) && $arSubvalue['FON'])
															{
																$code_tmp = 'fon'.$propValue.$subkey;
																if($_REQUEST[$code_tmp])
																	$_SESSION['THEME'][SITE_ID][$code_tmp] = $_REQUEST[$code_tmp];
															}

															//set default template index components
															if(isset($arSubvalue['TEMPLATE']) && $arSubvalue['TEMPLATE'])
															{

																$code_tmp = $propValue.'_'.$subkey.'_TEMPLATE';
																if($_REQUEST[$code_tmp])
																	$_SESSION['THEME'][SITE_ID][$code_tmp] = $_REQUEST[$code_tmp];

																if($arSubvalue['TEMPLATE']['LIST'])
																{
																	foreach($arSubvalue['TEMPLATE']['LIST'] as $keyS => $arListOption)
																	{
																		if($arListOption['ADDITIONAL_OPTIONS'])
																		{
																			foreach($arListOption['ADDITIONAL_OPTIONS'] as $keyS2 => $arListOption2)
																			{
																				if($_REQUEST[$propValue.'_'.$subkey.'_'.$keyS2.'_'.$keyS])
																				{
																					$_SESSION['THEME'][SITE_ID][$propValue.'_'.$subkey.'_'.$keyS2.'_'.$keyS] = $_REQUEST[$propValue.'_'.$subkey.'_'.$keyS2.'_'.$keyS];
																				}
																				else
																				{
																					if($arListOption2['TYPE'] == 'checkbox')
																						$_SESSION['THEME'][SITE_ID][$propValue.'_'.$subkey.'_'.$keyS2.'_'.$keyS] = 'N';
																					else
																						$_SESSION['THEME'][SITE_ID][$propValue.'_'.$subkey.'_'.$keyS2.'_'.$keyS] = $arListOption2['DEFAULT'];
																				}
																			}
																		}
																	}
																}
															}
														}

														//sort order prop for main page
														$param = 'SORT_ORDER_'.$optionCode.'_'.$propValue;
														if(isset($_REQUEST[$param]))
														{
															if($_REQUEST[$param])
																$_SESSION['THEME'][SITE_ID][$param] = $_REQUEST[$param];
															else
																$_SESSION['THEME'][SITE_ID][$param] = '';
														}
													}
												}
											}

											if(isset($arOption['DEPENDENT_PARAMS']) && $arOption['DEPENDENT_PARAMS']) //dependent params
											{
												foreach($arOption['DEPENDENT_PARAMS'] as $key => $arSubOptions)
												{
													if($arSubOptions['THEME'] == 'Y')
													{
														if($_REQUEST[$key])
															$_SESSION['THEME'][SITE_ID][$key] = $_REQUEST[$key];
														else
														{
															if($arSubOptions['TYPE'] == 'checkbox')
															{
																if(isset($_SESSION['THEME_ACTION']) && (isset($_SESSION['THEME_ACTION'][SITE_ID][$key]) && $_SESSION['THEME_ACTION'][SITE_ID][$key]))
																{
																	$_SESSION['THEME'][SITE_ID][$key] = $_SESSION['THEME_ACTION'][SITE_ID][$key];
																	unset($_SESSION['THEME_ACTION'][SITE_ID][$key]);
																}
																else
																	$_SESSION['THEME'][SITE_ID][$key] = 'N';
															}
															else
															{
																if(isset($_SESSION['THEME_ACTION']) && (isset($_SESSION['THEME_ACTION'][SITE_ID][$key]) && $_SESSION['THEME_ACTION'][SITE_ID][$key]))
																{
																	$_SESSION['THEME'][SITE_ID][$key] = $_SESSION['THEME_ACTION'][SITE_ID][$key];
																	unset($_SESSION['THEME_ACTION'][SITE_ID][$key]);
																}
																else
																	$_SESSION['THEME'][SITE_ID][$key] = $arSubOptions['DEFAULT'];
															}
														}
													}
												}
											}

											$bChanged = true;
										}
										else
										{
											if($arOption['TYPE'] == 'checkbox' && !$_REQUEST[$optionCode])
											{
												$_SESSION['THEME'][SITE_ID][$optionCode] = 'N';
												if(isset($arOption['DEPENDENT_PARAMS']) && $arOption['DEPENDENT_PARAMS']) //dependent params save
												{
													foreach($arOption['DEPENDENT_PARAMS'] as $key => $arSubOptions)
													{
														if($arSubOptions['THEME'] == 'Y')
														{
															if(isset($_SESSION['THEME'][SITE_ID][$key]))
																$_SESSION['THEME_ACTION'][SITE_ID][$key] = $_SESSION['THEME'][SITE_ID][$key];
															else
																$_SESSION['THEME_ACTION'][SITE_ID][$key] = $arBackParametrs[$key];
														}
													}
												}
											}

											if(isset($arOption['SUB_PARAMS']) && $arOption['SUB_PARAMS']) //nested params
											{

												if($arOption['TYPE'] == 'selectbox' && isset($arOption['LIST']))
												{
													$propValue = $_SESSION['THEME'][SITE_ID][$optionCode];
													if($arOption['SUB_PARAMS'][$propValue])
													{
														foreach($arOption['SUB_PARAMS'][$propValue] as $subkey => $arSubvalue)
														{
															if($_REQUEST[$propValue.'_'.$subkey])
																$_SESSION['THEME'][SITE_ID][$propValue.'_'.$subkey] = $_REQUEST[$propValue.'_'.$subkey];
															else
															{
																if(!isset($arSubvalue["VISIBLE"]))
																	$_SESSION['THEME'][SITE_ID][$propValue.'_'.$subkey] = 'N';
															}
														}
													}
												}
											}

										}
									}
								}
							}
						}
					}

					if(isset($_REQUEST["backurl"]) && $_REQUEST["backurl"])
						LocalRedirect($_REQUEST["backurl"]);
				}
				if(isset($_REQUEST["BASE_COLOR"]) && $_REQUEST["BASE_COLOR"])
					LocalRedirect($_SERVER["HTTP_REFERER"]);
			}
			elseif($_REQUEST && isset($_REQUEST['aspro_preset'])){
				self::setFrontParametrsOfPreset($_REQUEST['aspro_preset'], SITE_ID);
				LocalRedirect($GLOBALS['APPLICATION']->GetCurPageParam('', array('aspro_preset')));
				die();
			}
		}
		else
		{
			unset($_SESSION['THEME'][SITE_ID]);
			if(isset($_SESSION['THEME_ACTION'][SITE_ID]))
				unset($_SESSION['THEME_ACTION'][SITE_ID]);
		}
	}

	public static function Start($siteID){
		global  $APPLICATION, $STARTTIME, $arRegion;
		$STARTTIME = time() * 1000;

		$GLOBALS['_USE_GAPS_REPLACE_MAX_'] = true; // global flag for OnEndBufferContentHandler

		if (!isset($_SESSION['ASPRO_MAX_USE_MODIFIER'])) {
			$_SESSION['ASPRO_MAX_USE_MODIFIER'] = 'Y';// flag for OnSaleComponentOrderOneStepProcess
		}

		if (!isset($_COOKIE["ASPRO_MAX_USE_MODIFIER"])) {
			setcookie("ASPRO_MAX_USE_MODIFIER", 'Y');
		}

		if (isset($_REQUEST['color_theme']) && $_REQUEST['color_theme']) {
			LocalRedirect($_SERVER['HTTP_REFERER']);
		}

		if (CModule::IncludeModuleEx(self::moduleID) == 1) {
			$APPLICATION->AddHeadString('<link href="'.$APPLICATION->oAsset->getFullAssetPath(SITE_TEMPLATE_PATH.'/css/critical.css').'" data-skip-moving="true" rel="stylesheet">'); // little css with only required styles

			if (!defined('_USE_AJAX_HANDLER_MAX_')) {
				define('_USE_AJAX_HANDLER_MAX_', true); // global flag for onAfterAjaxResponseHandler
			}

			$bIndexBot = self::checkIndexBot(); // is indexed yandex/google bot
			if (!$bIndexBot) {
				self::UpdateFrontParametrsValues(); //update theme values
				self::GenerateThemes($siteID); //generate theme.css and bgtheme.css
			}

			$arTheme = self::GetFrontParametrsValues($siteID); //get site options
			self::setThemeColorsValues();
			self::setMaxWidth($arTheme);

			$bShowSimple = (static::IsBasketPage() || static::IsOrderPage()) && $arTheme['SIMPLE_BASKET'] == 'Y';

			self::setFonts($arTheme);

			// mutation observer
			$APPLICATION->AddHeadString('<script src="'.SITE_TEMPLATE_PATH.'/js/observer.js" async defer></script>');

			if (!$bIndexBot) {
				if ($arTheme['USE_LAZY_LOAD'] === 'Y' && !self::checkMask(Option::get('aspro.max', 'LAZY_LOAD_EXCEPTIONS', ''))) {
					// if async don't work, replace to defer
					$APPLICATION->AddHeadString('<script data-skip-moving="true">window.lazySizesConfig = window.lazySizesConfig || {};lazySizesConfig.loadMode = 1;lazySizesConfig.expand = 200;lazySizesConfig.expFactor = 1;lazySizesConfig.hFac = 0.1;window.lazySizesConfig.lazyClass = "lazy";</script>');
					$APPLICATION->AddHeadString('<script src="'.SITE_TEMPLATE_PATH.'/js/lazysizes.min.js" data-skip-moving="true" defer></script>');
					$APPLICATION->AddHeadString('<script src="'.SITE_TEMPLATE_PATH.'/js/ls.unveilhooks.min.js" data-skip-moving="true" defer></script>');
				}
			}

			$APPLICATION->SetPageProperty('viewport', 'initial-scale=1.0, width=device-width, maximum-scale=1');
			$APPLICATION->SetPageProperty('HandheldFriendly', 'true');
			$APPLICATION->SetPageProperty('apple-mobile-web-app-capable', 'yes');
			$APPLICATION->SetPageProperty('apple-mobile-web-app-status-bar-style', 'black');
			$APPLICATION->SetPageProperty('SKYPE_TOOLBAR', 'SKYPE_TOOLBAR_PARSER_COMPATIBLE');

			\Aspro\Max\PWA::showMeta(SITE_ID);
			
			$APPLICATION->SetAdditionalCSS(SITE_TEMPLATE_PATH.'/css/blocks/dark-light-theme.css');
			$APPLICATION->SetAdditionalCSS(SITE_TEMPLATE_PATH.'/css/colored.css');

			// register js and css libs
			\Aspro\Max\Functions\Extensions::register();

			// bootstrap
			if ($arTheme['USE_BOOTSTRAP'] === 'Y') {
				\Aspro\Max\Functions\Extensions::init('bootstrap');
			} else {
				$APPLICATION->SetAdditionalCSS(SITE_TEMPLATE_PATH.'/css/lite.bootstrap.css');
				$APPLICATION->AddHeadScript(SITE_TEMPLATE_PATH.'/js/lite.bootstrap.js');
			}

			$APPLICATION->SetAdditionalCSS(SITE_TEMPLATE_PATH.'/css/styles.css');

			/*BEM blocks*/
			$APPLICATION->SetAdditionalCSS(SITE_TEMPLATE_PATH.'/css/blocks/blocks.css');

			$APPLICATION->SetAdditionalCSS(SITE_TEMPLATE_PATH.'/css/blocks/common.blocks/counter-state/counter-state.css');
			/**/
			// $APPLICATION->SetAdditionalCSS(SITE_TEMPLATE_PATH.'/css/banners.css'); //long banner
			$APPLICATION->SetAdditionalCSS(SITE_TEMPLATE_PATH.'/css/menu.css'); // menu
			$APPLICATION->SetAdditionalCSS(SITE_TEMPLATE_PATH.'/css/header.css', true); // header
			if (self::IsBasketPage()) {
				$APPLICATION->SetAdditionalCSS(SITE_TEMPLATE_PATH.'/css/basket.css');

				\Aspro\Max\Functions\Extensions::init('buy_services');
				
				//bonus system
				if ($arTheme['BONUS_SYSTEM'] !== 'NOT') {
					\Aspro\Max\Functions\Extensions::init('bonus_system');
				}
			}
			if (self::IsOrderPage()) {
				$APPLICATION->SetAdditionalCSS(SITE_TEMPLATE_PATH.'/css/order.css');
			}
			if (!self::IsMainPage()) {
				$APPLICATION->SetAdditionalCSS(SITE_TEMPLATE_PATH.'/css/catalog.css');
			}
			if (self::IsPersonalPage()) {
				$APPLICATION->SetAdditionalCSS(SITE_TEMPLATE_PATH.'/css/personal.css');
			}

			$APPLICATION->SetAdditionalCSS(SITE_TEMPLATE_PATH.'/css/media.css', true);

			if (!$bIndexBot) {
				$APPLICATION->AddHeadString('<link href="'.$APPLICATION->oAsset->getFullAssetPath(SITE_TEMPLATE_PATH.'/css/print.css').'" data-template-style="true" rel="stylesheet" media="print">');

				if ($arTheme['USE_FOOTABLE'] == 'Y') {
					$APPLICATION->SetAdditionalCSS(SITE_TEMPLATE_PATH.'/vendor/css/footable.standalone.min.css');
				}
				$APPLICATION->SetAdditionalCSS(SITE_TEMPLATE_PATH.'/vendor/css/ripple.css');
				$APPLICATION->SetAdditionalCSS(SITE_TEMPLATE_PATH.'/css/stores.css');
				$APPLICATION->SetAdditionalCSS(SITE_TEMPLATE_PATH.'/css/yandex_map.css');

				// $APPLICATION->SetAdditionalCSS(SITE_TEMPLATE_PATH.'/css/buy_services.css');

				if ($bShowSimple) {
					$APPLICATION->SetAdditionalCSS(SITE_TEMPLATE_PATH.'/css/simpleBasket.css');
				}

				if ($arTheme['TOP_MENU_FIXED'] == "Y") {
					$APPLICATION->SetAdditionalCSS(SITE_TEMPLATE_PATH.'/css/header_fixed.css');
				}
			}

			if($arTheme['H1_STYLE'] == '2') // 2 - Normal
				$APPLICATION->SetAdditionalCSS(SITE_TEMPLATE_PATH.'/css/h1-normal.css', true);
			elseif($arTheme['H1_STYLE'] == '3') // 3 - medium
				$APPLICATION->SetAdditionalCSS(SITE_TEMPLATE_PATH.'/css/h1-medium.css', true);
			else // 1 - Bold
				$APPLICATION->SetAdditionalCSS(SITE_TEMPLATE_PATH.'/css/h1-bold.css', true);

			if ($arTheme['ROUND_ELEMENTS'] == 'Y') {
				$APPLICATION->SetAdditionalCSS(SITE_TEMPLATE_PATH.'/css/round-elements.css', true);
			}

			if($arTheme['FONT_BUTTONS'] == 'LOWER') {
				$APPLICATION->SetAdditionalCSS(SITE_TEMPLATE_PATH.'/css/lower-buttons.css', true);
			}

			if (!$bIndexBot) {
				// inline jquery for fast inline CheckTopMenuDotted function
				$APPLICATION->AddHeadString('
					<script data-skip-moving="true" src="'.self::getJQuerySrc().'"></script>
					<script data-skip-moving="true" src="'.SITE_TEMPLATE_PATH.'/js/speed.min.js?='.filemtime($_SERVER['DOCUMENT_ROOT'].SITE_TEMPLATE_PATH.'/js/speed.min.js').'"></script>
				');

				$APPLICATION->AddHeadScript(SITE_TEMPLATE_PATH.'/js/jquery.actual.min.js');
				if ($arTheme['USE_BXSLIDER'] == 'Y') {
					$APPLICATION->AddHeadScript(SITE_TEMPLATE_PATH.'/vendor/js/jquery.bxslider.js');
				}
				// $APPLICATION->AddHeadScript(SITE_TEMPLATE_PATH.'/js/jqModal.js');
				$APPLICATION->AddHeadScript(SITE_TEMPLATE_PATH.'/vendor/js/ripple.js');

				$APPLICATION->AddHeadScript(SITE_TEMPLATE_PATH.'/js/browser.js');
				$APPLICATION->AddHeadScript(SITE_TEMPLATE_PATH.'/js/jquery.uniform.min.js');
			}

			if (!$bIndexBot) {
				if($arTheme['USE_FOOTABLE'] == 'Y') {
					$APPLICATION->AddHeadScript(SITE_TEMPLATE_PATH.'/vendor/js/moment.min.js');
					$APPLICATION->AddHeadScript(SITE_TEMPLATE_PATH.'/vendor/js/footable.js');
				}
				$APPLICATION->AddHeadScript(SITE_TEMPLATE_PATH.'/vendor/js/sticky-sidebar.js');
				if((!isset($_SERVER['HTTP_X_REQUESTED_WITH']) ||(isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) != 'xmlhttprequest')) && !isset($_REQUEST['ajax']))
					$APPLICATION->AddHeadScript(SITE_TEMPLATE_PATH.'/js/jquery.validate.min.js');
				$APPLICATION->AddHeadScript(SITE_TEMPLATE_PATH.'/js/jquery.alphanumeric.js');
				$APPLICATION->AddHeadScript(SITE_TEMPLATE_PATH.'/js/jquery.cookie.js');

				if ((!isset($_SERVER['HTTP_X_REQUESTED_WITH']) ||(isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) != 'xmlhttprequest')) && (strtolower($_REQUEST['ajax']) != 'y')) {
					$APPLICATION->AddHeadScript(SITE_TEMPLATE_PATH.'/js/mobile.js');
					$APPLICATION->AddHeadScript(SITE_TEMPLATE_PATH.'/js/main.js');
					$APPLICATION->AddHeadScript(SITE_TEMPLATE_PATH.'/js/blocks/blocks.js');
					$APPLICATION->AddHeadScript(SITE_TEMPLATE_PATH.'/js/custom.js', true);
				}
			}

			if (strlen($arTheme['FAVICON_IMAGE'])) {
				$file_ext = pathinfo($arTheme['FAVICON_IMAGE'], PATHINFO_EXTENSION);
				$fav_ext = $file_ext ? $file_ext : 'ico';
				$fav_type = '';

				switch ($fav_ext) {
					case 'ico': 
						$fav_type = 'image/x-icon';
						break;
					case 'svg': 
						$fav_type = 'image/svg+xml';
						break;
					case 'png': 
						$fav_type = 'image/png';
						break;
					case 'jpg': 
						$fav_type = 'image/jpeg';
						break;
					case 'gif': 
						$fav_type = 'image/gif';
						break;
					case 'bmp': 
						$fav_type = 'image/bmp';
						break;
				}

				$APPLICATION->AddHeadString('<link rel="shortcut icon" href="'.$arTheme['FAVICON_IMAGE'].'" type="'.$fav_type.'" />', true);
			}

			if (strlen($arTheme['APPLE_TOUCH_ICON_IMAGE'])) {
				$APPLICATION->AddHeadString('<link rel="apple-touch-icon" sizes="180x180" href="'.$arTheme['APPLE_TOUCH_ICON_IMAGE'].'" />', true);
			}

			if (!$bIndexBot) {
				CAjax::Init();

				if(self::IsBasketPage())
				{
					CJSCore::Init(array('fx'));?>

					<?//clear basket session counter in basket page?>
					<?if($_COOKIE['click_basket'] && $_COOKIE['click_basket'] == 'Y'):?>
						<?static::clearBasketCounters();?>
						<?unset($_COOKIE['click_basket'])?>
						<script>
							$.removeCookie('click_basket', {path: '/'});
						</script>
					<?endif;?>

				<?}

				\Bitrix\Main\Loader::includeModule('iblock');
				\Bitrix\Main\Loader::includeModule('sale');
				\Bitrix\Main\Loader::includeModule('catalog');
			}

			//cart notice
			if ($arTheme['SHOW_BASKET_NOTICE'] === 'Y') {
				\Aspro\Max\Functions\Extensions::init('notice');
			}

			//bonus system
			if ($arTheme['BONUS_SYSTEM'] !== 'NOT') {
				// \Aspro\Max\Functions\Extensions::init('bonus_system');
			}

			if($arTheme['USE_REGIONALITY'] == 'Y') {
				$arRegion = CMaxRegionality::getCurrentRegion(); //get current region from regionality module
			}

			return true;
		} else {
			$APPLICATION->SetAdditionalCSS(SITE_TEMPLATE_PATH.'/css/styles.css');
			$APPLICATION->SetTitle(GetMessage('ERROR_INCLUDE_MODULE'));
			$APPLICATION->IncludeFile(SITE_DIR.'include/error_include_module.php', Array(), Array()); die();
		}
	}

	public static function setFonts($arTheme){
		global $APPLICATION;

		$bSelfHostedFonts = $arTheme['SELF_HOSTED_FONTS'] !== 'N';

		if(!$arTheme['CUSTOM_FONT']){
			if(
				!$arTheme['FONT_STYLE'] ||
				!self::$arParametrsList['MAIN']['OPTIONS']['FONT_STYLE']['LIST'][$arTheme['FONT_STYLE']]
			){
				$arTheme['FONT_STYLE'] = 10;
			}

			$font_family = self::$arParametrsList['MAIN']['OPTIONS']['FONT_STYLE']['LIST'][$arTheme['FONT_STYLE']]['LINK'];
			$font_template = self::$arParametrsList['MAIN']['OPTIONS']['FONT_STYLE']['LIST'][$arTheme['FONT_STYLE']]['TEMPLATE_LINK'];

			if(
				$bSelfHostedFonts &&
				$font_template &&
				@file_exists($_SERVER['DOCUMENT_ROOT'].SITE_TEMPLATE_PATH.$font_template)
			){
				// $APPLICATION->AddHeadString('<link rel="preload" href="'.SITE_TEMPLATE_PATH.$font_template.'.woff2" as="font" type="font/woff2" crossorigin="anonymous">');
				//$APPLICATION->AddHeadString('<link href="'.SITE_TEMPLATE_PATH.$font_template.'" rel="preload" as="style">');
				$APPLICATION->SetAdditionalCSS(SITE_TEMPLATE_PATH.$font_template);
			}
			else{
				$APPLICATION->AddHeadString('<link href="https://fonts.gstatic.com" rel="preconnect" crossorigin>');
				$APPLICATION->AddHeadString('<link rel="preload" href="'.(CMain::IsHTTPS() ? 'https' : 'http').'://fonts.googleapis.com/css?family='.$font_family.'&display=swap" as="style" crossorigin>');
				$APPLICATION->AddHeadString('<link rel="stylesheet" href="'.(CMain::IsHTTPS() ? 'https' : 'http').'://fonts.googleapis.com/css?family='.$font_family.'&display=swap" crossorigin>');
			}
		}
		else{
			$APPLICATION->AddHeadString('<link href="https://fonts.gstatic.com" rel="preconnect" crossorigin>');
			$APPLICATION->AddHeadString('<'.$arTheme['CUSTOM_FONT'].' rel="preload" as="style" crossorigin>');
			$APPLICATION->AddHeadString('<'.$arTheme['CUSTOM_FONT'].' rel="stylesheet" crossorigin>');
		}
	}

	public static function setThemeColorsValues() {
		global $APPLICATION, $baseColor;
		$arThemeValues = self::GetFrontParametrsValues(SITE_ID);

		$isBaseCustom = $arThemeValues['BASE_COLOR'] == 'CUSTOM';

		$arBaseColors = self::$arParametrsList['MAIN']['OPTIONS']['BASE_COLOR']['LIST'];

		if ($isBaseCustom) {
			$baseColor = (strpos($arThemeValues['BASE_COLOR_CUSTOM'], '#') === false ? '#' : '').$arThemeValues['BASE_COLOR_CUSTOM'];
		} else {
			$baseColor = $arBaseColors[$arThemeValues['BASE_COLOR']]['COLOR'];
		}

		//hsl color
		$colorHsl = self::hexToHSL($baseColor);
		$hueColor = $colorHsl[0];
		$saturationColor = $colorHsl[1];
		$lightnessColor = $colorHsl[2];
		
		$rootColorsVariables = [
			'--theme-base-color: '.$baseColor.';',
			'--theme-base-opacity-color: '.$baseColor.'1a;',
		];
		if ($hueColor)
			$rootColorsVariables[] = '--theme-base-color-hue:'.$hueColor.';';
		if ($saturationColor)
			$rootColorsVariables[] = '--theme-base-color-saturation:'.$saturationColor.'%;';
		if ($lightnessColor)
			$rootColorsVariables[] = '--theme-base-color-lightness:'.$lightnessColor.'%;';
		if ($lightnessHoverDiff)
			$rootColorsVariables[] = '--theme-lightness-hover-diff:'.$lightnessHoverDiff.'%;';

		$APPLICATION->AddHeadString('<meta name="theme-color" content="'.$baseColor.'">');
		$APPLICATION->AddHeadString('<style>:root{'.implode('', $rootColorsVariables).'}</style>');
	}

	public static function setMaxWidth($arTheme){
		$page_width_padding = '32';
		$page_width = (int)str_replace(' ', '', self::$arParametrsList['MAIN']['OPTIONS']['PAGE_WIDTH']['LIST'][$arTheme['PAGE_WIDTH']]);

		$GLOBALS['APPLICATION']->AddHeadString('<style>html {--theme-page-width: '.$page_width.'px;--theme-page-width-padding: '.$page_width_padding.'px}</style>');
	}

	public static function hexToRgb($hex, $alpha = false) {
		$hex      = str_replace('#', '', $hex);
		$length   = strlen($hex);
		$rgb[] = hexdec($length == 6 ? substr($hex, 0, 2) : ($length == 3 ? str_repeat(substr($hex, 0, 1), 2) : 0));
		$rgb[] = hexdec($length == 6 ? substr($hex, 2, 2) : ($length == 3 ? str_repeat(substr($hex, 1, 1), 2) : 0));
		$rgb[] = hexdec($length == 6 ? substr($hex, 4, 2) : ($length == 3 ? str_repeat(substr($hex, 2, 1), 2) : 0));
		if ( $alpha ) {
		   $rgb[] = $alpha;
		}
		return $rgb;
	 }

	public static function hexToHSL($color) {

		$rgbColor = self::hexToRgb($color);

		$r = $rgbColor[0] / 255;
		$g = $rgbColor[1] / 255;
		$b = $rgbColor[2] / 255;

		$min = min($r, $g, $b);
		$max = max($r, $g, $b);

		$L = ($min + $max) / 2;
		if ($min == $max) {
			$S = $H = 0;
		} else {
			if ($L < 0.5)
				$S = ($max - $min)/($max + $min);
			else
				$S = ($max - $min)/(2.0 - $max - $min);

			if ($r == $max) $H = ($g - $b)/($max - $min);
			elseif ($g == $max) $H = 2.0 + ($b - $r)/($max - $min);
			elseif ($b == $max) $H = 4.0 + ($r - $g)/($max - $min);

		}

		$out = array(round(($H < 0 ? $H + 6 : $H)*60),
			round($S*100),
			round($L*100),
		);

		if (count($rgbColor) > 3) $out[] = $rgbColor[3]; // copy alpha
		return $out;
	}

	public static function checkBgImage($siteID){
		global $APPLICATION, $arRegion, $arTheme;
		static $arBanner;
		if($arBanner === NULL)
		{
			$arFilterBanner = array('IBLOCK_ID' => CMaxCache::$arIBlocks[$siteID]['aspro_max_adv']['aspro_max_bg_images'][0], 'ACTIVE'=>'Y');

			if($arRegion && isset($arTheme['USE_REGIONALITY']['DEPENDENT_PARAMS']['REGIONALITY_FILTER_ITEM']) && $arTheme['USE_REGIONALITY']['DEPENDENT_PARAMS']['REGIONALITY_FILTER_ITEM']['VALUE'] == 'Y')
				$arFilterBanner['PROPERTY_LINK_REGION'] = $arRegion['ID'];

			$arItems = CMaxCache::CIBLockElement_GetList(array('SORT' => 'ASC', 'CACHE' => array('TAG' => CMaxCache::GetIBlockCacheTag(CMaxCache::$arIBlocks[$siteID]['aspro_max_adv']['aspro_max_bg_images'][0]))), $arFilterBanner, false, false, array('ID', 'NAME', 'PREVIEW_PICTURE', 'PROPERTY_URL', 'PROPERTY_FIXED_BANNER', 'PROPERTY_URL_NOT_SHOW'));
			$arBanner = array();
			if($arItems)
			{
				$curPage = $APPLICATION->GetCurPage();
				foreach($arItems as $arItem)
				{
					if(isset($arItem['PROPERTY_URL_VALUE']) && $arItem['PREVIEW_PICTURE'])
					{
						if(!is_array($arItem['PROPERTY_URL_VALUE']))
							$arItem['PROPERTY_URL_VALUE'] = array($arItem['PROPERTY_URL_VALUE']);
						if($arItem['PROPERTY_URL_VALUE'])
						{
							foreach($arItem['PROPERTY_URL_VALUE'] as $url)
							{
								$url=str_replace('SITE_DIR', SITE_DIR, $url);
								if($arItem['PROPERTY_URL_NOT_SHOW_VALUE'])
								{
									if(!is_array($arItem['PROPERTY_URL_NOT_SHOW_VALUE']))
										$arItem['PROPERTY_URL_NOT_SHOW_VALUE'] = array($arItem['PROPERTY_URL_NOT_SHOW_VALUE']);
									foreach($arItem['PROPERTY_URL_NOT_SHOW_VALUE'] as $url_not_show)
									{
										$url_not_show=str_replace('SITE_DIR', SITE_DIR, $url_not_show);
										if(CSite::InDir($url_not_show))
											break 2;
									}
									foreach($arItem['PROPERTY_URL_NOT_SHOW_VALUE'] as $url_not_show)
									{
										$url_not_show = str_replace('SITE_DIR', SITE_DIR, $url_not_show);
										if(CSite::InDir($url_not_show))
										{
											// continue;
											break 2;
										}
										else
										{
											if(CSite::InDir($url))
											{
												$arBanner = $arItem;
												break;
											}
										}
									}
								}
								else
								{
									if(CSite::InDir($url))
									{
										$arBanner = $arItem;
										break;
									}
								}
							}
						}
					}
				}
			}
		}
		return $arBanner;
	}

	public static function isChildsSelected($arChilds){
		if($arChilds && is_array($arChilds)){
			foreach($arChilds as $arChild){
				if($arChild['SELECTED']){
					return true;
				}
			}
		}
		return false;
	}

	/*static function GetBannerStyle($bannerwidth, $topmenu){
        $style = '';

        if($bannerwidth == 'WIDE'){
            $style = '.maxwidth-banner{max-width: 1550px;}';
        }
        elseif($bannerwidth == 'MIDDLE'){
            $style = '.maxwidth-banner{max-width: 1450px;}';
        }
        elseif($bannerwidth == 'NARROW'){
            $style = '.maxwidth-banner{max-width: 1343px; padding: 0 16px;}';
			if($topmenu !== 'LIGHT'){
				$style .= '.banners-big{margin-top:20px;}';
			}
        }
        else{
            $style = '.maxwidth-banner{max-width: auto;}';
        }

        return '<style>'.$style.'</style>';
    }*/

    public static function GetIndexPageBlocks($pageAbsPath, $pageBlocksPrefix, $pageBlocksDirName = 'page_blocks'){
    	$arResult = array();

    	if($pageAbsPath && $pageBlocksPrefix){
    		$pageAbsPath = str_replace('//', '//', $pageAbsPath).'/';
    		if(is_dir($pageBlocksAbsPath = str_replace('', '', $pageAbsPath.(strlen($pageBlocksDirName) ? $pageBlocksDirName : '')))){
    			if($arPageBlocks = glob($pageBlocksAbsPath.'/*.php')){
		    		foreach($arPageBlocks as $file){
						$file = str_replace('.php', '', basename($file));
						if(strpos($file, $pageBlocksPrefix) !== false){
							$arResult[$file] = $file;
						}
					}
    			}
    		}
    	}

    	return $arResult;
    }

    public static function GetComponentTemplatePageBlocks($templateAbsPath, $pageBlocksDirName = 'page_blocks'){
    	$arResult = array('SECTIONS' => array(), 'SUBSECTIONS' => array(), 'ELEMENTS' => array(), 'ELEMENT' => array(), 'LANDING' => array());

    	if($templateAbsPath){
    		$templateAbsPath = str_replace('//', '//', $templateAbsPath).'/';
    		if(is_dir($pageBlocksAbsPath = str_replace('//', '/', $templateAbsPath.(strlen($pageBlocksDirName) ? $pageBlocksDirName : '')))){
    			if($arPageBlocks = glob($pageBlocksAbsPath.'/*.php')){
		    		foreach($arPageBlocks as $file){
						$file = str_replace('.php', '', basename($file));
						if(strpos($file, 'sections_') !== false){
							$arResult['SECTIONS'][$file] = $file;
						}
						elseif(strpos($file, 'section_') !== false){
							$arResult['SUBSECTIONS'][$file] = $file;
						}
						elseif(strpos($file, 'list_elements_') !== false){
							$arResult['ELEMENTS'][$file] = $file;
						}
						elseif(strpos($file, 'element_') !== false){
							$arResult['ELEMENT'][$file] = $file;
						}
						elseif(strpos($file, 'fast_view_') !== false){
							$arResult['FAST_VIEW_ELEMENT'][$file] = $file;
						}
						elseif(strpos($file, 'bigdata_') !== false && strpos($file, 'bigdata_bottom') === false ){
							$arResult['BIGDATA'][$file] = $file;
						}
						elseif(strpos($file, 'bigdata_bottom') !== false){
							$arResult['BIGDATA_BOTTOM'][$file] = $file;
						}
						elseif(strpos($file, 'landing_') !== false){
							$arResult['LANDING'][$file] = $file;
						}
					}
    			}
    		}
    	}

    	return $arResult;
    }

    public static function GetComponentTemplatePageBlocksParams($arPageBlocks, $arAdditonalConfig = []){
    	$arResult = array();

    	if($arPageBlocks && is_array($arPageBlocks)){
			$arBaseConfig = [
				'PARENT' => 'BASE',
				'SORT' => 1,
				'TYPE' => 'LIST'
			];
    		if(isset($arPageBlocks['SECTIONS']) && $arPageBlocks['SECTIONS'] && is_array($arPageBlocks['SECTIONS'])){
				$arConfig = [
					'NAME' => GetMessage('M_SECTIONS_TYPE_VIEW'),
					'VALUES' => $arPageBlocks['SECTIONS'],
					'DEFAULT' => key($arPageBlocks['SECTIONS'])
				];
				if ($arAdditonalConfig['SECTIONS']) $arConfig += $arAdditonalConfig['SECTIONS'];
    			$arResult['SECTIONS_TYPE_VIEW'] = array_merge($arBaseConfig, $arConfig);
    		}
    		if(isset($arPageBlocks['SUBSECTIONS']) && $arPageBlocks['SUBSECTIONS'] && is_array($arPageBlocks['SUBSECTIONS'])){
				$arConfig = [
					'NAME' => GetMessage('M_SECTION_TYPE_VIEW'),
					'VALUES' => $arPageBlocks['SUBSECTIONS'],
					'DEFAULT' => key($arPageBlocks['SUBSECTIONS']),
				];
				if ($arAdditonalConfig['SUBSECTIONS']) $arConfig += $arAdditonalConfig['SUBSECTIONS'];
    			$arResult['SECTION_TYPE_VIEW'] = array_merge($arBaseConfig, $arConfig);
    		}
    		if(isset($arPageBlocks['ELEMENTS']) && $arPageBlocks['ELEMENTS'] && is_array($arPageBlocks['ELEMENTS'])){
				$arConfig = [
					'NAME' => GetMessage('M_SECTION_ELEMENTS_TYPE_VIEW'),
					'VALUES' => $arPageBlocks['ELEMENTS'],
					'DEFAULT' => key($arPageBlocks['ELEMENTS']),
				];
				if ($arAdditonalConfig['ELEMENTS']) $arConfig += $arAdditonalConfig['ELEMENTS'];
    			$arResult['SECTION_ELEMENTS_TYPE_VIEW'] = array_merge($arBaseConfig, $arConfig);
    		}
    		if(isset($arPageBlocks['ELEMENT']) && $arPageBlocks['ELEMENT'] && is_array($arPageBlocks['ELEMENT'])){
				$arConfig = [
					'NAME' => GetMessage('M_ELEMENT_TYPE_VIEW'),
					'VALUES' => $arPageBlocks['ELEMENT'],
					'DEFAULT' => key($arPageBlocks['ELEMENT'])
				];
				if ($arAdditonalConfig['ELEMENT']) $arConfig += $arAdditonalConfig['ELEMENT'];
    			$arResult['ELEMENT_TYPE_VIEW'] = array_merge($arBaseConfig, $arConfig);
    		}
    		if(isset($arPageBlocks['LANDING']) && $arPageBlocks['LANDING'] && is_array($arPageBlocks['LANDING'])){
				$arConfig = [
					'NAME' => GetMessage('M_LANDING_TYPE_VIEW'),
					'VALUES' => $arPageBlocks['LANDING'],
					'DEFAULT' => key($arPageBlocks['LANDING'])
				];
				if ($arAdditonalConfig['LANDING']) $arConfig += $arAdditonalConfig['LANDING'];
    			$arResult['LANDING_TYPE_VIEW'] = array_merge($arBaseConfig, $arConfig);
    		}
    	}

    	return $arResult;
    }

    protected static function IsComponentTemplateHasModuleSectionsPageBlocksParam($templateName, $arExtParams = array()){
    	$section_param = ((isset($arExtParams['SECTION']) && $arExtParams['SECTION']) ? $arExtParams['SECTION'] : 'SECTION');
    	$template_param = ((isset($arExtParams['OPTION']) && $arExtParams['OPTION']) ? $arExtParams['OPTION'] : strtoupper($templateName));
	    return $templateName && isset(self::$arParametrsList[$section_param]['OPTIONS'][$template_param.'_PAGE_SECTIONS']);
    }

    protected static function IsComponentTemplateHasModuleSectionPageBlocksParam($templateName, $arExtParams = array()){
    	$section_param = ((isset($arExtParams['SECTION']) && $arExtParams['SECTION']) ? $arExtParams['SECTION'] : 'SECTION');
    	$template_param = ((isset($arExtParams['OPTION']) && $arExtParams['OPTION']) ? $arExtParams['OPTION'] : strtoupper($templateName));
	    return $templateName && isset(self::$arParametrsList[$section_param]['OPTIONS'][$template_param.'_PAGE_SECTION']);
    }

    protected static function IsComponentTemplateHasModuleElementsPageBlocksParam($templateName, $arExtParams = array()){
    	$section_param = ((isset($arExtParams['SECTION']) && $arExtParams['SECTION']) ? $arExtParams['SECTION'] : 'SECTION');
    	$template_param = ((isset($arExtParams['OPTION']) && $arExtParams['OPTION']) ? $arExtParams['OPTION'] : strtoupper($templateName));
	    return $templateName && isset(self::$arParametrsList[$section_param]['OPTIONS'][$template_param.'_PAGE']);
    }

    protected static function IsComponentTemplateHasModuleElementPageBlocksParam($templateName, $arExtParams = array()){
    	$section_param = ((isset($arExtParams['SECTION']) && $arExtParams['SECTION']) ? $arExtParams['SECTION'] : 'SECTION');
    	$template_param = ((isset($arExtParams['OPTION']) && $arExtParams['OPTION']) ? $arExtParams['OPTION'] : strtoupper($templateName));
	    return $templateName && isset(self::$arParametrsList[$section_param]['OPTIONS'][$template_param.'_PAGE_DETAIL']);
    }

    protected static function IsComponentTemplateHasModuleLandingsPageBlocksParam($templateName, $arExtParams = array()){
    	$section_param = ((isset($arExtParams['SECTION']) && $arExtParams['SECTION']) ? $arExtParams['SECTION'] : 'SECTION');
    	$template_param = ((isset($arExtParams['OPTION']) && $arExtParams['OPTION']) ? $arExtParams['OPTION'] : strtoupper($templateName));
	    return $templateName && isset(self::$arParametrsList[$section_param]['OPTIONS'][$template_param.'_PAGE_LANDINGS']);
    }

    public static function AddComponentTemplateModulePageBlocksParams($templateAbsPath, &$arParams, $arExtParams = array()){
    	if($templateAbsPath && $arParams && is_array($arParams)){
    		$templateAbsPath = str_replace('//', '//', $templateAbsPath).'/';
    		$templateName = basename($templateAbsPath);
    		if(self::IsComponentTemplateHasModuleSectionsPageBlocksParam($templateName, $arExtParams)){
    			$arParams['SECTIONS_TYPE_VIEW']['VALUES'] = array_merge(array('FROM_MODULE' => GetMessage('M_FROM_MODULE_PARAMS')), $arParams['SECTIONS_TYPE_VIEW']['VALUES']);
    			$arParams['SECTIONS_TYPE_VIEW']['DEFAULT'] = 'FROM_MODULE';
    		}
    		if(self::IsComponentTemplateHasModuleSectionPageBlocksParam($templateName, $arExtParams)){
    			$arParams['SECTION_TYPE_VIEW']['VALUES'] = array_merge(array('FROM_MODULE' => GetMessage('M_FROM_MODULE_PARAMS')), $arParams['SECTION_TYPE_VIEW']['VALUES']);
    			$arParams['SECTION_TYPE_VIEW']['DEFAULT'] = 'FROM_MODULE';
    		}
    		if(self::IsComponentTemplateHasModuleElementsPageBlocksParam($templateName, $arExtParams)){
    			$arParams['SECTION_ELEMENTS_TYPE_VIEW']['VALUES'] = array_merge(array('FROM_MODULE' => GetMessage('M_FROM_MODULE_PARAMS')), $arParams['SECTION_ELEMENTS_TYPE_VIEW']['VALUES']);
    			$arParams['SECTION_ELEMENTS_TYPE_VIEW']['DEFAULT'] = 'FROM_MODULE';
    		}
    		if(self::IsComponentTemplateHasModuleElementPageBlocksParam($templateName, $arExtParams)){
    			$arParams['ELEMENT_TYPE_VIEW']['VALUES'] = array_merge(array('FROM_MODULE' => GetMessage('M_FROM_MODULE_PARAMS')), $arParams['ELEMENT_TYPE_VIEW']['VALUES']);
    			$arParams['ELEMENT_TYPE_VIEW']['DEFAULT'] = 'FROM_MODULE';
    		}
    		if(self::IsComponentTemplateHasModuleLandingsPageBlocksParam($templateName, $arExtParams)){
    			$arParams['LANDING_TYPE_VIEW']['VALUES'] = array_merge(array('FROM_MODULE' => GetMessage('M_FROM_MODULE_PARAMS')), $arParams['LANDING_TYPE_VIEW']['VALUES']);
    			$arParams['LANDING_TYPE_VIEW']['DEFAULT'] = 'FROM_MODULE';
    		}
    	}
    }

    public static function CheckComponentTemplatePageBlocksParams(&$arParams, $templateAbsPath, $pageBlocksDirName = 'page_blocks'){
    	$arPageBlocks = self::GetComponentTemplatePageBlocks($templateAbsPath, $pageBlocksDirName);

    	if(!isset($arParams['SECTIONS_TYPE_VIEW']) || !$arParams['SECTIONS_TYPE_VIEW'] || (!isset($arPageBlocks['SECTIONS'][$arParams['SECTIONS_TYPE_VIEW']]) && $arParams['SECTIONS_TYPE_VIEW'] !== 'FROM_MODULE')){
    		$arParams['SECTIONS_TYPE_VIEW'] = key($arPageBlocks['SECTIONS']);
    	}
    	if(!isset($arParams['SECTION_TYPE_VIEW']) || !$arParams['SECTION_TYPE_VIEW'] || (!isset($arPageBlocks['SUBSECTIONS'][$arParams['SECTION_TYPE_VIEW']]) && $arParams['SECTION_TYPE_VIEW'] !== 'FROM_MODULE')){
    		$arParams['SECTION_TYPE_VIEW'] = key($arPageBlocks['SUBSECTIONS']);
    	}
    	if(!isset($arParams['SECTION_ELEMENTS_TYPE_VIEW']) || !$arParams['SECTION_ELEMENTS_TYPE_VIEW'] || (!isset($arPageBlocks['ELEMENTS'][$arParams['SECTION_ELEMENTS_TYPE_VIEW']]) && $arParams['SECTION_ELEMENTS_TYPE_VIEW'] !== 'FROM_MODULE')){
    		$arParams['SECTION_ELEMENTS_TYPE_VIEW'] = key($arPageBlocks['ELEMENTS']);
    	}
    	if(!isset($arParams['ELEMENT_TYPE_VIEW']) || !$arParams['ELEMENT_TYPE_VIEW'] || (!isset($arPageBlocks['ELEMENT'][$arParams['ELEMENT_TYPE_VIEW']]) && $arParams['ELEMENT_TYPE_VIEW'] !== 'FROM_MODULE')){
    		$arParams['ELEMENT_TYPE_VIEW'] = key($arPageBlocks['ELEMENT']);
    	}
    }

    public static function Add2OptionCustomPageBlocks(&$arOption, $templateAbsPath, $filename, $prefix = ''){
		if($arOption && isset($arOption['LIST'])){
			if($templateAbsPath)
			{
	    		$templateAbsPath = str_replace('//', '//', $templateAbsPath).'/';
	    		if(is_dir($pageBlocksAbsPath = str_replace('//', '/', $templateAbsPath)))
	    		{
	    			if($arPageBlocks = glob($pageBlocksAbsPath.'/'.$filename.'*.php'))
	    			{
			    		foreach($arPageBlocks as $file)
			    		{
			    			$replace = array(
								$filename,
			    				'.php',
			    			);
							$title = basename($file);
							$file = str_replace($replace, '', basename($file));
			    			if($prefix) {
			    				$file = $prefix.$file;
							}
							if(!isset($arOption['LIST'][$file]))
							{
								$arOption['LIST'][$file] = array(
									'TITLE' => $title,
									'HIDE' => 'Y',
									'IS_CUSTOM' => 'Y',
								);
							}
			    		}
			    	}
				}
				if(!$arOption['DEFAULT'] && $arOption['LIST'])
					$arOption['DEFAULT'] = key($arOption['LIST']);
			}
		}
    }
    public static function Add2OptionCustomComponentTemplatePageBlocks(&$arOption, $templateAbsPath){
		if($arOption && isset($arOption['LIST'])){
			if($arPageBlocks = self::GetComponentTemplatePageBlocks($templateAbsPath)){
				foreach($arPageBlocks['ELEMENTS'] as $page => $value){
					if(!isset($arOption['LIST'][$page])){
						$arOption['LIST'][$page] = array(
							'TITLE' => $value,
							'HIDE' => 'Y',
							'IS_CUSTOM' => 'Y',
						);
					}
				}
				if(!$arOption['DEFAULT'] && $arOption['LIST']){
					$arOption['DEFAULT'] = key($arOption['LIST']);
				}
			}
		}
    }

    public static function Add2OptionCustomComponentTemplatePageBlocksSections(&$arOption, $templateAbsPath){
		if($arOption && isset($arOption['LIST'])){
			if($arPageBlocks = self::GetComponentTemplatePageBlocks($templateAbsPath)){
				foreach($arPageBlocks['SECTIONS'] as $page => $value){
					if(!isset($arOption['LIST'][$page])){
						$arOption['LIST'][$page] = array(
							'TITLE' => $value,
							'HIDE' => 'Y',
							'IS_CUSTOM' => 'Y',
						);
					}
				}
				if(!$arOption['DEFAULT'] && $arOption['LIST']){
					$arOption['DEFAULT'] = key($arOption['LIST']);
				}
			}
		}
    }

    public static function Add2OptionCustomComponentTemplatePageBlocksLandings(&$arOption, $templateAbsPath){
		if($arOption && isset($arOption['LIST'])){
			if($arPageBlocks = self::GetComponentTemplatePageBlocks($templateAbsPath)){
				foreach($arPageBlocks['LANDING'] as $page => $value){
					if(!isset($arOption['LIST'][$page])){
						$arOption['LIST'][$page] = array(
							'TITLE' => $value,
							'HIDE' => 'Y',
							'IS_CUSTOM' => 'Y',
						);
					}
				}
				if(!$arOption['DEFAULT'] && $arOption['LIST']){
					$arOption['DEFAULT'] = key($arOption['LIST']);
				}
			}
		}
    }

    public static function Add2OptionCustomComponentTemplatePageBlocksElement(&$arOption, $templateAbsPath, $field = 'ELEMENT'){
		if($arOption && isset($arOption['LIST'])){
			if($arPageBlocks = self::GetComponentTemplatePageBlocks($templateAbsPath)){
				if($arPageBlocks[$field])
				{
					foreach($arPageBlocks[$field] as $page => $value){
						if(!isset($arOption['LIST'][$page])){
							$arOption['LIST'][$page] = array(
								'TITLE' => $value,
								'HIDE' => 'Y',
								'IS_CUSTOM' => 'Y',
							);
						}
					}
				}
				if(!$arOption['DEFAULT'] && $arOption['LIST']){
					$arOption['DEFAULT'] = key($arOption['LIST']);
				}
			}
		}
    }

    public static function GetCurrentElementFilter(&$arVariables, &$arParams){
        $arFilter = array('IBLOCK_ID' => $arParams['IBLOCK_ID'], 'INCLUDE_SUBSECTIONS' => 'Y');
        if($arParams['CHECK_DATES'] == 'Y'){
            $arFilter = array_merge($arFilter, array('ACTIVE' => 'Y', 'SECTION_GLOBAL_ACTIVE' => 'Y', 'ACTIVE_DATE' => 'Y'));
        }
        if($arVariables['ELEMENT_ID']){
            $arFilter['ID'] = $arVariables['ELEMENT_ID'];
        }
        elseif(strlen($arVariables['ELEMENT_CODE'])){
            $arFilter['CODE'] = $arVariables['ELEMENT_CODE'];
        }
		if($arVariables['SECTION_ID']){
			$arFilter['SECTION_ID'] = ($arVariables['SECTION_ID'] ? $arVariables['SECTION_ID'] : false);
		}
		if($arVariables['SECTION_CODE']){
			$arFilter['SECTION_CODE'] = ($arVariables['SECTION_CODE'] ? $arVariables['SECTION_CODE'] : false);
		}
        if(!$arFilter['SECTION_ID'] && !$arFilter['SECTION_CODE']){
            unset($arFilter['SECTION_GLOBAL_ACTIVE']);
        }
        if(strlen($arParams['FILTER_NAME'])){
        	if($GLOBALS[$arParams['FILTER_NAME']]){
				$arFilter = array_merge($arFilter, $GLOBALS[$arParams['FILTER_NAME']]);
			}
        }
        return $arFilter;
    }

	public static function GetCurrentSectionFilter(&$arVariables, &$arParams){
		$arFilter = array('IBLOCK_ID' => $arParams['IBLOCK_ID']);
		if($arParams['CHECK_DATES'] == 'Y'){
			$arFilter = array_merge($arFilter, array('ACTIVE' => 'Y', 'GLOBAL_ACTIVE' => 'Y', 'ACTIVE_DATE' => 'Y'));
		}
		if($arVariables['SECTION_ID']){
			$arFilter['ID'] = $arVariables['SECTION_ID'];
		}
		if(strlen($arVariables['SECTION_CODE'])){
			$arFilter['CODE'] = $arVariables['SECTION_CODE'];
		}
		if(!$arVariables['SECTION_ID'] && !strlen($arFilter['CODE'])){
			$arFilter['ID'] = 0; // if section not found
		}
		return $arFilter;
	}

	public static function GetCurrentSectionElementFilter(&$arVariables, &$arParams, $CurrentSectionID = false){
		$arFilter = array('IBLOCK_ID' => $arParams['IBLOCK_ID'], 'INCLUDE_SUBSECTIONS' => 'N');
		if($arParams['CHECK_DATES'] == 'Y'){
			$arFilter = array_merge($arFilter, array('ACTIVE' => 'Y', 'SECTION_GLOBAL_ACTIVE' => 'Y', 'ACTIVE_DATE' => 'Y'));
		}
		if(!$arFilter['SECTION_ID'] = ($CurrentSectionID !== false ? $CurrentSectionID : ($arVariables['SECTION_ID'] ? $arVariables['SECTION_ID'] : false))){
			unset($arFilter['SECTION_GLOBAL_ACTIVE']);
		}
		if(strlen($arParams['FILTER_NAME'])){
			$GLOBALS[$arParams['FILTER_NAME']] = (array)$GLOBALS[$arParams['FILTER_NAME']];
			foreach($arUnsetFilterFields = array('SECTION_ID', 'SECTION_CODE', 'SECTION_ACTIVE', 'SECTION_GLOBAL_ACTIVE') as $filterUnsetField){
				foreach($GLOBALS[$arParams['FILTER_NAME']] as $filterField => $filterValue){
					if(($p = strpos($filterUnsetField, $filterField)) !== false && $p < 2){
						unset($GLOBALS[$arParams['FILTER_NAME']][$filterField]);
					}
				}
			}
			if($GLOBALS[$arParams['FILTER_NAME']]){
				$arFilter = array_merge($arFilter, $GLOBALS[$arParams['FILTER_NAME']]);
			}
		}
		return $arFilter;
	}

	public static function ShowRSSIcon($href){
		$text = '<a class="rss" href="'.$href.'" title="RSS" target="_blank">'.static::showIconSvg("print", SITE_TEMPLATE_PATH."/images/svg/rss.svg",'','colored_theme_hover_bg-el-svg').'</a>';
		$GLOBALS['APPLICATION']->AddHeadString('<link rel="alternate" type="application/rss+xml" title="rss" href="'.$href.'" />');

		return $text;
	}

	public static function ShowLogo(){
		global $arSite;
		$arTheme = self::GetFrontParametrsValues(SITE_ID);
		$text = '<a href="'.SITE_DIR.'">';
		if($arImg = unserialize(Option::get(self::moduleID, "LOGO_IMAGE", serialize(array()))))
			$text .= '<img src="'.CFile::GetPath($arImg[0]).'" alt="'.$arSite["SITE_NAME"].'" title="'.$arSite["SITE_NAME"].'" data-src="" />';
		elseif(self::checkContentFile(SITE_DIR.'/include/logo_svg.php'))
			$text .= File::getFileContents($_SERVER['DOCUMENT_ROOT'].SITE_DIR.'/include/logo_svg.php');
		else
			$text .= '<img src="'.$arTheme["LOGO_IMAGE"].'" alt="'.$arSite["SITE_NAME"].'" title="'.$arSite["SITE_NAME"].'" data-src="" />';
		$text .= '</a>';

		return $text;
	}

	public static function ShowLogoFixed(){
		global $arSite;
		$arTheme = self::GetFrontParametrsValues(SITE_ID);
		$text = '<a href="'.SITE_DIR.'">';
		if($arImg = unserialize(Option::get(self::moduleID, "LOGO_IMAGE_FIXED", serialize(array()))))
			$text .= '<img src="'.CFile::GetPath($arImg[0]).'" alt="'.$arSite["SITE_NAME"].'" title="'.$arSite["SITE_NAME"].'" data-src="" />';
		elseif($arImg = unserialize(Option::get(self::moduleID, "LOGO_IMAGE", serialize(array()))))
			$text .= '<img src="'.CFile::GetPath($arImg[0]).'" alt="'.$arSite["SITE_NAME"].'" title="'.$arSite["SITE_NAME"].'" data-src="" />';
		elseif(self::checkContentFile(SITE_DIR.'/include/logo_svg.php'))
			$text .= File::getFileContents($_SERVER['DOCUMENT_ROOT'].SITE_DIR.'/include/logo_svg.php');
		else
			$text .= '<img src="'.$arTheme["LOGO_IMAGE"].'" alt="'.$arSite["SITE_NAME"].'" title="'.$arSite["SITE_NAME"].'" data-src="" />';
		$text .= '</a>';

		return $text;
	}

	public static function showIconSvg($class = 'phone', $path, $title = '', $class_icon = '', $show_wrapper = true, $binline = true){
		$text ='';
		if(self::checkContentFile($path))
		{
			static $svg_call;
			$iSvgID = ++$svg_call;

			$content = File::getFileContents($_SERVER['DOCUMENT_ROOT'].$path);

			$pattern = '/(<svg[^\>]*?|<path[^\>]*?)(id\s*=[\'\"].*?[\'\"])/i';
			$content = preg_replace($pattern, '${1}', $content);

			$pattern = '/(<svg[^>]*?>[^\/]*?)<metadata>.*?<\/metadata>/is';
			$content = preg_replace($pattern, '${1}', $content);

			$pattern = '/>(\s+?)</i';
			$content = preg_replace($pattern, '><', $content);

			$pattern = '/<style>.*?<\//is';
			preg_match_all($pattern, $content, $matches);
			$need = str_replace(array(' ', PHP_EOL), '', $matches[0]);
			$content = str_replace($matches[0], $need, $content);

			$content = str_replace('markID', $iSvgID, $content);

			if($show_wrapper)
				$text = '<i class="svg '.($binline ? 'inline ' : '').$class_icon.' svg-inline-'.$class.'" aria-hidden="true" '.($title ? 'title="'.$title.'"' : '').'>';

				$text .= $content;

			if($show_wrapper)
				$text .= '</i>';
		}

		return $text;
	}

	public static function showSpriteIconSvg($path, $class = '', $arOptions = []) {
		$width = (isset($arOptions['WIDTH']) && $arOptions['WIDTH'] ? 'width="'.$arOptions['WIDTH'].'"' : '');
		$height = (isset($arOptions['HEIGHT']) && $arOptions['HEIGHT'] ? 'height="'.$arOptions['HEIGHT'].'"' : '');
		$viewbox = (isset($arOptions['VIEWBOX']) && $arOptions['VIEWBOX'] ? 'viewbox="'.$arOptions['VIEWBOX'].'"' : '');
		if (!isset($arOptions['INLINE']) || $arOptions['INLINE'] !== 'N') {
			$class .= ' inline ';
		}
		return '<i class="svg '.$class.'" aria-hidden="true"><svg '.$width.' '.$height.' '.$viewbox.'><use xlink:href="'.$path.'"></use></svg></i>';
	}

	public static function checkContentFile($path){
		if(File::isFileExists($_SERVER['DOCUMENT_ROOT'].$path))
			$content = File::getFileContents($_SERVER['DOCUMENT_ROOT'].$path);
		return (!empty($content));
	}

	public static function ShowPageProps($prop){
		/** @global CMain $APPLICATION */
		global $APPLICATION;
		$APPLICATION->AddBufferContent(array("CMax", "GetPageProps"), $prop);
	}

	public static function GetPageProps($prop){
		/** @global CMain $APPLICATION */
		global $APPLICATION;

		if($prop == 'ERROR_404')
		{
			return (defined($prop) ? 'with_error' : '');
		}
		else
		{
			if($prop == 'HIDE_LEFT_BLOCK')
			{
				global $isIndex, $bActiveTheme, $isShowIndexLeftBlock;
				if($isIndex && $bActiveTheme && !$isShowIndexLeftBlock)
					return 'Y';
				$prop = 'HIDE_LEFT_BLOCK';
			}
			$val = $APPLICATION->GetProperty($prop);
			if(!empty($val))
				return $val;
		}
		return '';
	}

	public static function getCurrentThemeClasses(){
		global $arTheme;
		$basket_class = ($arTheme['ORDER_BASKET_VIEW']['VALUE'] == 'FLY2' ? 'fly fly2' : strToLower($arTheme['ORDER_BASKET_VIEW']['VALUE']));
		$result = '';

		$result .= 'basket_'.$basket_class;
		$result .= ' basket_fill_'.$arTheme['ORDER_BASKET_COLOR']['VALUE'];
		$result .= ' side_'.$arTheme['SIDE_MENU']['VALUE'];
		$result .= ' block_side_'.$arTheme['LEFT_BLOCK_CATALOG_SECTIONS']['DEPENDENT_PARAMS']['LEFT_BLOCK_CS_TYPE']['VALUE'];
		$result .= ' catalog_icons_'.$arTheme['LEFT_BLOCK_CATALOG_ICONS']['VALUE'];
		$result .= ' banner_auto '.($arTheme['USE_FAST_VIEW_PAGE_DETAIL']['VALUE'] != 'NO' ? 'with_fast_view' : '');
		$result .= ' mheader-v'.$arTheme['HEADER_MOBILE']['VALUE'];
		$result .= ' header-v'.$arTheme['HEADER_TYPE']['VALUE'];
		$result .= ' header-font-lower_'.$arTheme['MENU_LOWERCASE']['VALUE'];
		$result .= ' regions_'.$arTheme['USE_REGIONALITY']['VALUE'];
		$result .= ' title_position_'.$arTheme['PAGE_TITLE_POSITION']['VALUE'];
		$result .= ' footer-v'.$arTheme['FOOTER_TYPE']['VALUE'];
		$result .= ' front-v'.$arTheme['INDEX_TYPE']['VALUE'];
		$result .= ' mfixed_'.$arTheme['HEADER_MOBILE_FIXED']['VALUE'];
		$result .= ' mfixed_view_'.strtolower($arTheme['HEADER_MOBILE_FIXED']['DEPENDENT_PARAMS']['HEADER_MOBILE_SHOW']['VALUE']);
		$result .= ' title-v'.$arTheme['PAGE_TITLE']['VALUE'];
		$result .= ' lazy_'.($arTheme['USE_LAZY_LOAD']['VALUE'] == 'Y' && !self::checkMask(Option::get('aspro.max', 'LAZY_LOAD_EXCEPTIONS', '')) ? 'Y' : 'N');
		$result .= (int)($arTheme['HEADER_PHONES']) > 0 ? ' with_phones' : '';
		$result .= $arTheme['MOBILE_CATALOG_LIST_ELEMENTS_COMPACT']['VALUE'] == 'Y' ? ' compact-catalog' : '';
		$result .= $arTheme['DARK_HOVER_OVERLAY']['VALUE'] == 'Y' ? ' dark-hover-overlay' : '';
		$result .= ' '.strtolower($arTheme['LIST_ELEMENTS_IMG_TYPE']['VALUE']).'-catalog-img';
		$result .= ' landing-'.$arTheme['CATALOG_PAGE_LANDINGS_VIEW']['VALUE'];
		$result .= ' big-banners-mobile-'.$arTheme['MOBILE_BIG_BANNER_INDEX']['VALUE'];
		$result .= ' bottom-icons-panel-'.$arTheme['BOTTOM_ICONS_PANEL']['VALUE'];
		$result .= ' compact-breadcrumbs-'.$arTheme['MOBILE_CATALOG_BREADCRUMBS']['VALUE'];
		$result .= ' catalog-delayed-btn-'.$arTheme['CATALOG_DELAY']['VALUE'];
		$result .= isset($arTheme['HEADER_TYPE']['LIST'][$arTheme['HEADER_TYPE']['VALUE']]['ADDITIONAL_OPTIONS']['SEARCH_HEADER_OPACITY']['VALUE']) && $arTheme['HEADER_TYPE']['LIST'][$arTheme['HEADER_TYPE']['VALUE']]['ADDITIONAL_OPTIONS']['SEARCH_HEADER_OPACITY']['VALUE'] == 'Y' ? ' header_search_opacity' : '';

		return $result;
	}

	public static function getCurrentBodyClass(){
		static $result;
		global $arTheme, $APPLICATION;

		if(!isset($result))
		{
			$result = $APPLICATION->AddBufferContent(array('CMax', 'showBodyClass'));
		}

		$result .= ' fill_bg_n';
		$result .= ' catalog-delayed-btn-'.(CMax::GetFrontParametrValue('CATALOG_DELAY'));		

		/* default|light|dark theme */
		$result .= ' theme-'.strtolower(CMax::GetFrontParametrValue('THEME_VIEW_COLOR'));
		/* */

		return $result;
	}

	public static function showBodyClass(){
		global $arTheme, $APPLICATION, $dopBodyClass;

		return $dopBodyClass;
	}

	public static function getCurrentHtmlClass(){
		static $result;

		global $arTheme, $APPLICATION;

		if(!isset($result))
		{
			$result = $APPLICATION->AddBufferContent(array('CMax', 'showHtmlClass'));
		}

		return $result;
	}

	public static function showHtmlClass(){
		global $arTheme, $APPLICATION, $dopHtmlClass;

		return $dopHtmlClass;
	}

	public static function getCurrentPageClass(){
		static $result;
		global $arTheme, $APPLICATION;

		if(!isset($result))
		{
			$result = $APPLICATION->AddBufferContent(array('CMax', 'showPageClass'));

			if(self::IsMainPage())
				$result = ' front';
			if(self::IsAuthSection())
				$result = ' auth';
			if(self::IsBasketPage())
				$result = ' basket';
			if(self::IsCatalogPage())
				$result = ' catalog';
			if(self::IsPersonalPage())
				$result = ' personal';
			if(self::IsOrderPage())
				$result = ' order';
			if(self::IsFormPage())
				$result = 'form';
			if($result)
				$result.='_page';
		}
		return $result;
	}

	public static function getBannerClass(){
		global $bLightHeader;

		$class = '';

		if ($bLightHeader) {
			$class = 'light-menu-color';
		}

		return $class;
	}

	public static function getVariable($var){
		global $APPLICATION;
		return $APPLICATION->AddBufferContent(array('CMax', 'checkVariable'), $var);
	}

	public static function checkVariable($var){
		global $$var;
		return $$var;
	}

	public static function showPageClass(){
		global $bLongBanner, $bLongHeader, $bLongHeader2, $bLongHeader3, $bColoredHeader, $dopClass, $APPLICATION, $bLongBannerContents, $needLEftBlock, $arTheme;
		$class = '';

		$class .= 'header_bg'.strtolower($arTheme["MENU_COLOR"]["VALUE"]);

		if($bLongBanner)
			$class .= ' long_banner';

		if($bLongHeader)
			$class .= ' long_header';

		if($bLongHeader2)
			$class .= ' long_header2';

		if($bLongHeader3)
			$class .= ' long_header3';

		if($bColoredHeader)
			$class .= ' colored_header';

		if($dopClass)
			$class .= ' '.$dopClass;

		if(!self::IsMainPage() && ($APPLICATION->GetProperty('HIDE_LEFT_BLOCK')!='Y' || $needLEftBlock)){
			$class .= ' with_left_block ';
		}

		if($bLongBannerContents)
			$class .= ' long_banner_contents ';

		return $class;
	}

	public static function IsMainPage(){
		static $result;

		if(!isset($result))
			$result = CSite::InDir(SITE_DIR.'index.php');

		return $result;
	}

	public static function IsAuthSection(){
		static $result;

		if(!isset($result))
			$result = CSite::InDir(SITE_DIR.'auth/');

		return $result;
	}

	public static function IsBasketPage($page = ''){
		static $result;

		if(!isset($result))
		{
			if(!$page)
			{
				$arOptions = self::GetBackParametrsValues(SITE_ID);
				if(!strlen($arOptions['BASKET_PAGE_URL']))
					$arOptions['BASKET_PAGE_URL'] = SITE_DIR.'basket/';
				$page = $arOptions['BASKET_PAGE_URL'];
			}
			$result = CSite::InDir($page);
		}

		return $result;
	}

	public static function IsCatalogPage($page = ''){
		static $result;

		if(!isset($result))
		{
			if(!$page)
			{
				$arOptions = self::GetBackParametrsValues(SITE_ID);
				if(!strlen($arOptions['CATALOG_PAGE_URL']))
					$arOptions['CATALOG_PAGE_URL'] = SITE_DIR.'catalog/';
				$page = $arOptions['CATALOG_PAGE_URL'];
			}
			$result = CSite::InDir($page);
		}

		return $result;
	}

	public static function IsSearchPage($page = ''){
		static $result;

		if(!isset($result))
		{
			if(!$page)
			{
				$arOptions = self::GetBackParametrsValues(SITE_ID);
				if(!strlen($arOptions['SEARCH_PAGE_URL']))
					$arOptions['SEARCH_PAGE_URL'] = SITE_DIR.'search/';
				$page = $arOptions['SEARCH_PAGE_URL'];
			}
			$result = CSite::InDir($page);
		}

		return $result;
	}

	public static function IsOrderPage($page = ''){
		static $result;

		if(!isset($result))
		{
			if(!$page)
			{
				$arOptions = self::GetBackParametrsValues(SITE_ID);
				if(!strlen($arOptions['ORDER_PAGE_URL']))
					$arOptions['ORDER_PAGE_URL'] = SITE_DIR.'order/';
				$page = $arOptions['ORDER_PAGE_URL'];
			}
			$result = CSite::InDir($page);
		}

		return $result;
	}

	public static function IsPersonalPage($page = ''){
		static $result;

		if(!isset($result))
		{
			if(!$page)
			{
				$arOptions = self::GetBackParametrsValues(SITE_ID);
				if(!strlen($arOptions['PERSONAL_PAGE_URL']))
					$arOptions['PERSONAL_PAGE_URL'] = SITE_DIR.'personal/';
				$page = $arOptions['PERSONAL_PAGE_URL'];
			}
			$result = CSite::InDir($page);
		}

		return $result;
	}

	public static function IsFormPage(){
		static $result;

		if(!isset($result))
			$result = CSite::InDir(SITE_DIR.'form/');

		return $result;
	}

	public static function GenerateMinCss($file){
		if(file_exists($file))
		{
			$content = @file_get_contents($file);
			if($content !== false)
			{
				$content = preg_replace('!/\*[^*]*\*+([^/][^*]*\*+)*/!', '', $content);
				$content = str_replace(array("\r\n", "\r", "\n", "\t"), '', $content);
				$content = preg_replace('/ {2,}/', ' ', $content);
				$content = str_replace(array(' : ', ': ', ' :',), ':', $content);
				$content = str_replace(array(' ; ', '; ', ' ;'), ';', $content);
				$content = str_replace(array(' > ', '> ', ' >'), '>', $content);
				$content = str_replace(array(' + ', '+ ', ' +'), '+', $content);
				$content = str_replace(array(' { ', '{ ', ' {'), '{', $content);
				$content = str_replace(array(' } ', '} ', ' }'), '}', $content);
				$content = str_replace(array(' ( ', '( ', ' ('), '(', $content);
				$content = str_replace(array(' ) ', ') ', ' )'), ')', $content);
				$content = str_replace('and(', 'and (', $content);
				$content = str_replace(')li', ') li', $content);
				$content = str_replace(').', ') .', $content);
				@file_put_contents(dirname($file).'/'.basename($file, '.css').'.min.css', $content);
			}
		}
		return false;
	}

	public static function GenerateThemes($siteID){
		$arBackParametrs = self::GetBackParametrsValues(SITE_ID);
		$arBaseColors = self::$arParametrsList['MAIN']['OPTIONS']['BASE_COLOR']['LIST'];
		$arBaseBgColors = self::$arParametrsList['MAIN']['OPTIONS']['BGCOLOR_THEME']['LIST'];
		$isCustomTheme = $_SESSION['THEME'][SITE_ID]['BASE_COLOR'] === 'CUSTOM';
		$isCustomThemeBG = $_SESSION['THEME'][SITE_ID]['BGCOLOR_THEME'] === 'CUSTOM';

		$bNeedGenerateAllThemes = Option::get(self::moduleID, 'NeedGenerateThemes', 'N', $siteID) === 'Y';
		$bNeedGenerateCustomTheme = Option::get(self::moduleID, 'NeedGenerateCustomTheme', 'N', $siteID) === 'Y';
		$bNeedGenerateCustomThemeBG = Option::get(self::moduleID, 'NeedGenerateCustomThemeBG', 'N', $siteID) === 'Y';

		$baseColorCustom = $baseColorBGCustom = '';

		$lastGeneratedBaseColorCustom = Option::get(self::moduleID, 'LastGeneratedBaseColorCustom', '', $siteID);
		if(isset(self::$arParametrsList['MAIN']['OPTIONS']['BASE_COLOR_CUSTOM']))
		{
			$baseColorCustom = $arBackParametrs['BASE_COLOR_CUSTOM'] = str_replace('#', '', $arBackParametrs['BASE_COLOR_CUSTOM']);
			if($arBackParametrs['THEME_SWITCHER'] === 'Y' && strlen($_SESSION['THEME'][SITE_ID]['BASE_COLOR_CUSTOM']))
				$baseColorCustom = $_SESSION['THEME'][SITE_ID]['BASE_COLOR_CUSTOM'] = str_replace('#', '', $_SESSION['THEME'][SITE_ID]['BASE_COLOR_CUSTOM']);
		}

		$lastGeneratedBaseColorBGCustom = Option::get(self::moduleID, 'LastGeneratedBaseColorBGCustom', '', $siteID);
		if(isset(self::$arParametrsList['MAIN']['OPTIONS']['CUSTOM_BGCOLOR_THEME']))
		{
			$baseColorBGCustom = $arBackParametrs['CUSTOM_BGCOLOR_THEME'] = str_replace('#', '', $arBackParametrs['CUSTOM_BGCOLOR_THEME']);
			if($arBackParametrs['THEME_SWITCHER'] === 'Y' && strlen($_SESSION['THEME'][SITE_ID]['CUSTOM_BGCOLOR_THEME']))
				$baseColorBGCustom = $_SESSION['THEME'][SITE_ID]['CUSTOM_BGCOLOR_THEME'] = str_replace('#', '', $_SESSION['THEME'][SITE_ID]['CUSTOM_BGCOLOR_THEME']);
		}

		$bGenerateAll = self::devMode || $bNeedGenerateAllThemes;
		$bGenerateCustom = $bGenerateAll || $bNeedGenerateCustomTheme || ($arBackParametrs['THEME_SWITCHER'] === 'Y' && $isCustomTheme && strlen($baseColorCustom) && $baseColorCustom != $lastGeneratedBaseColorCustom);
		$bGenerateCustomBG = $bGenerateAll || $bNeedGenerateCustomThemeBG || ($arBackParametrs['THEME_SWITCHER'] === 'Y' && $isCustomThemeBG && strlen($baseColorBGCustom) && $baseColorBGCustom != $lastGeneratedBaseColorBGCustom);

		if($arBaseColors && is_array($arBaseColors) && ($bGenerateAll || $bGenerateCustom || $bGenerateCustomBG))
		{
			if(!class_exists('lessc'))
				include_once 'lessc.inc.php';

			$less = new lessc;
			try
			{
				if(defined('SITE_TEMPLATE_PATH'))
				{
					$templateName = array_pop(explode('/', SITE_TEMPLATE_PATH));
				}

				foreach($arBaseColors as $colorCode => $arColor)
				{
					if(($bCustom = ($colorCode == 'CUSTOM')) && $bGenerateCustom)
					{

						$less->setVariables(array('bcolor' => (strlen($baseColorCustom) ? '#'.$baseColorCustom : $arBaseColors[self::$arParametrsList['MAIN']['OPTIONS']['BASE_COLOR']['DEFAULT']]['COLOR'])));

					}
					elseif($bGenerateAll)
					{
						$less->setVariables(array('bcolor' => $arColor['COLOR']));
					}

					if($bGenerateAll || ($bCustom && $bGenerateCustom))
					{
						if(defined('SITE_TEMPLATE_PATH'))
						{
							$themeDirPath = $_SERVER['DOCUMENT_ROOT'].SITE_TEMPLATE_PATH.'/themes/'.strToLower($colorCode.($colorCode !== 'CUSTOM' ? '' : '_'.$siteID)).'/';
							if(!is_dir($themeDirPath))
								mkdir($themeDirPath, 0755, true);
							$output = $less->compileFile(__DIR__.'/../../css/theme.less', $themeDirPath.'theme.css');
							if($output)
							{
								if($bCustom)
									Option::set(self::moduleID, 'LastGeneratedBaseColorCustom', $baseColorCustom, $siteID);

								self::GenerateMinCss($themeDirPath.'theme.css');
							}


							if($templateName && $templateName != 'aspro_max') {

								$themeDirPath = $_SERVER['DOCUMENT_ROOT'].'/bitrix/templates/aspro_max'.'/themes/'.strToLower($colorCode.($colorCode !== 'CUSTOM' ? '' : '_'.$siteID)).'/';
								if(!is_dir($themeDirPath))
									mkdir($themeDirPath, 0755, true);
								$output = $less->compileFile(__DIR__.'/../../css/theme.less', $themeDirPath.'theme.css');
								if($output)
								{
									self::GenerateMinCss($themeDirPath.'theme.css');
								}

							}

						}
					}
				}
				if($arBaseBgColors)
				{
					foreach($arBaseBgColors as $colorCode => $arColor)
					{
						if(($bCustom = ($colorCode == 'CUSTOM')) && $bGenerateCustomBG)
						{
							if(strlen($baseColorBGCustom))
							{
								$footerBgColor = $baseColorBGCustom === "FFFFFF" ? "F6F6F7" : $baseColorBGCustom;
								$less->setVariables(array('bcolor' => (strlen($baseColorBGCustom) ? '#'.$baseColorBGCustom : $arBaseBgColors[self::$arParametrsList['MAIN']['OPTIONS']['BGCOLOR_THEME']['DEFAULT']]['COLOR']), 'fcolor' => '#'.$footerBgColor));
							}
						}
						elseif($bGenerateAll)
						{
							$less->setVariables(array('bcolor' => $arColor['COLOR'], 'fcolor' => $arColor['COLOR']));
						}

						if($bGenerateAll || ($bCustom && $bGenerateCustomBG))
						{
							if(defined('SITE_TEMPLATE_PATH'))
							{
								$themeDirPath = $_SERVER['DOCUMENT_ROOT'].SITE_TEMPLATE_PATH.'/bg_color/'.strToLower($colorCode.($colorCode !== 'CUSTOM' ? '' : '_'.$siteID)).'/';
								if(!is_dir($themeDirPath))
									mkdir($themeDirPath, 0755, true);
								$output = $less->compileFile(__DIR__.'/../../css/bgtheme.less', $themeDirPath.'bgcolors.css');
								if($output)
								{
									if($bCustom)
										Option::set(self::moduleID, 'LastGeneratedBaseColorBGCustom', $baseColorBGCustom, $siteID);

									self::GenerateMinCss($themeDirPath.'bgcolors.css');
								}

								if($templateName && $templateName != 'aspro_max') {

									$themeDirPath = $_SERVER['DOCUMENT_ROOT'].'/bitrix/templates/aspro_max'.'/bg_color/'.strToLower($colorCode.($colorCode !== 'CUSTOM' ? '' : '_'.$siteID)).'/';
									if(!is_dir($themeDirPath))
										mkdir($themeDirPath, 0755, true);
									$output = $less->compileFile(__DIR__.'/../../css/bgtheme.less', $themeDirPath.'bgcolors.css');
									if($output)
									{
										self::GenerateMinCss($themeDirPath.'bgcolors.css');
									}

								}

							}
						}
					}
				}
			}
			catch(exception $e)
			{
				echo 'Fatal error: '.$e->getMessage();
				die();
			}

			if($bNeedGenerateAllThemes)
				Option::set(self::moduleID, "NeedGenerateThemes", 'N', $siteID);
			if($bNeedGenerateCustomTheme)
				Option::set(self::moduleID, "NeedGenerateCustomTheme", 'N', $siteID);
			if($bNeedGenerateCustomThemeBG)
				Option::set(self::moduleID, "NeedGenerateCustomThemeBG", 'N', $siteID);
		}
	}

	public static function get_banners_position($position, $show_all = 'N') {	
		global $bannerContentBottom;	
		if( $position === 'CONTENT_BOTTOM' && $bannerContentBottom )
			return;
		$arTheme = self::GetFrontParametrsValues(SITE_ID);
		$slider_mode = 'N';
		$slider_autoplay = 'N';
		$slider_speed = $arTheme["ADV_BANNER_SLIDESSHOWSPEED"];
		$animation_speed = $arTheme["ADV_BANNER_ANIMATIONSPEED"];
		if( $arTheme["ADV_SHOW_SLIDER_CONTENT"] == 'Y' && ($position === 'CONTENT_TOP' || $position === 'CONTENT_BOTTOM')){
			$slider_mode = 'Y';
			$show_all = 'Y';
			
			if($arTheme["ADV_SLIDER_AUTOPLAY"] == 'Y')
				$slider_autoplay = 'Y';
		}
		if ($arTheme["ADV_".$position] == 'Y'):
			global $APPLICATION, $arMainPageOrder;
			$countMainPageBlocks = is_array($arMainPageOrder) ? count($arMainPageOrder) : 0;
			?>
			<div class="banner_content_bottom_container" <?=$countMainPageBlocks > 0 ? 'style="order:'.$countMainPageBlocks.'"' : ""?>>
				<?include(str_replace('//', '/', $_SERVER['DOCUMENT_ROOT'].SITE_DIR.'include/comp_inner_banners.php'));?>
			</div>
		<?endif;
	}

	public static function formatPriceMatrix($arItem = array()){
		if(isset($arItem['PRICE_MATRIX']) && $arItem['PRICE_MATRIX'])
		{
			$result = false;
			$minPrice = 0;
			foreach($arItem['PRICE_MATRIX']['MATRIX'] as $key => $arPriceGroup)
			{
				foreach($arPriceGroup as $key2 => $arPrice)
				{
					if($arPrice['PRICE'])
					{
						if($arItem['PRICE_MATRIX']['CAN_BUY'] && in_array($key, $arItem['PRICE_MATRIX']['CAN_BUY']) && ($arItem['CATALOG_TYPE'] == 1 || $arItem['CATALOG_TYPE'] == 2))
						{
							if (empty($result))
							{
								$minPrice = ($arPrice['DISCOUNT_PRICE'] != $arPrice['PRICE'] ? $arPrice['DISCOUNT_PRICE'] : $arPrice['PRICE']);
								$result = $minPrice;
							}
							else
							{
								$comparePrice = ($arPrice['DISCOUNT_PRICE'] != $arPrice['PRICE'] ? $arPrice['DISCOUNT_PRICE'] : $arPrice['PRICE']);
								if ($minPrice > $comparePrice)
								{
									$minPrice = $comparePrice;
									$result = $minPrice;
								}
							}
							$arItem['MIN_PRICE']['VALUE'] = $result;
							$arItem['MIN_PRICE']['DISCOUNT_VALUE'] = $result;
							$arItem['MIN_PRICE']['PRINT_DISCOUNT_VALUE'] = CCurrencyLang::CurrencyFormat($result, $arPrice['CURRENCY'], true);
							$arItem['MIN_PRICE']['CURRENCY'] = $arPrice['CURRENCY'];
							$arItem['MIN_PRICE']['OLD_PRICE'] = $arPrice['PRICE'];
							$arItem['MIN_PRICE']['CAN_BUY'] = 'Y';
						}
						$arItem['PRICE_MATRIX']['MATRIX'][$key][$key2]['PRINT_PRICE'] =  CCurrencyLang::CurrencyFormat($arPrice['PRICE'], $arPrice['CURRENCY'], true);
					}
					if($arPrice['DISCOUNT_PRICE'])
						$arItem['PRICE_MATRIX']['MATRIX'][$key][$key2]['PRINT_DISCOUNT_PRICE'] =  CCurrencyLang::CurrencyFormat($arPrice['DISCOUNT_PRICE'], $arPrice['CURRENCY'], true);
				}
			}
		}
		return $arItem;
	}

	public static function showPriceMatrix($arItem = array(), $arParams, $strMeasure = '', $arAddToBasketData = array()){
		$html = '';
		if(
			isset($arItem['PRICE_MATRIX']) 
			&& $arItem['PRICE_MATRIX']
			&& ($arParams['USE_PRICE_COUNT'] === 'Y' || $arParams['USE_PRICE_COUNT'] === true || $arParams['USE_PRICE_COUNT'] === 'true')
		) {
			ob_start();?>
				<?$bShowPopupPrice = (
					static::GetFrontParametrValue('SHOW_POPUP_PRICE') == 'Y' 
					&& (
						count($arItem['PRICE_MATRIX']['ROWS']) > 1 
						|| count($arItem['PRICE_MATRIX']['COLS']) > 1
					)
				);?>
				<?if($bShowPopupPrice):?>
					<div class="js-info-block rounded3">
						<div class="block_title text-upper font_xs font-bold">
							<?=Loc::getMessage('T_JS_PRICES_MORE')?>
							<?=static::showIconSvg("close", SITE_TEMPLATE_PATH."/images/svg/Close.svg");?>
						</div>
						<div class="block_wrap">
							<div class="block_wrap_inner prices scrollblock">
				<?endif;?>
				<div class="price_matrix_block">
					<?
					$sMissingGoodsPriceDisplay = \CMax::GetFrontParametrValue('MISSING_GOODS_PRICE_DISPLAY');
					$sMissingGoodsPriceDisplayText = \CMax::GetFrontParametrValue('MISSING_GOODS_PRICE_DISPLAY_TEXT_VALUE');
					$sDiscountPrices = \Bitrix\Main\Config\Option::get(ASPRO_MAX_MODULE_ID, 'DISCOUNT_PRICE');
					$arDiscountPrices = array();
					if($sDiscountPrices)
						$arDiscountPrices = array_flip(explode(',', $sDiscountPrices));

					\Bitrix\Main\Type\Collection::sortByColumn($arItem['PRICE_MATRIX']['COLS'], array('SORT' => SORT_ASC));

					$arTmpPrice = (isset($arItem['ITEM_PRICES']) ? current($arItem['ITEM_PRICES']) : array());

					$iCountPriceGroup = count($arItem['PRICE_MATRIX']['COLS']);
					$bPriceRows = (count($arItem['PRICE_MATRIX']['ROWS']) > 1);?>
					<?foreach($arItem['PRICE_MATRIX']['COLS'] as $arPriceGroup):?>
						<?if($iCountPriceGroup > 1):?>
							<?
							$class = '';
							if($arTmpPrice)
							{
								if($arItem['PRICE_MATRIX']['MATRIX'][$arPriceGroup['ID']][$arTmpPrice['QUANTITY_HASH']]['ID'] == $arTmpPrice['ID'])
									$class = 'min';
							}?>
							<div class="price_group <?=$class;?> <?=$arPriceGroup['XML_ID']?>"><div class="price_name <?=($arItem['ITEM_PRICE_MODE'] == 'Q' || !$bShowPopupPrice ? 'font_xs darken' : 'font_xxs muted');?>"><?=$arPriceGroup["NAME_LANG"];?></div>
						<?endif;?>
						<div class="price_matrix_wrapper <?=($arDiscountPrices ? (isset($arDiscountPrices[$arPriceGroup['ID']]) ? 'strike_block' : '') : '');?>">
						<?$iCountPriceInterval = count($arItem['PRICE_MATRIX']['MATRIX'][$arPriceGroup['ID']]);?>
						<?foreach($arItem['PRICE_MATRIX']['MATRIX'][$arPriceGroup['ID']] as $key => $arPrice):?>
							<?if($iCountPriceInterval > 1):?>
								<div class="price_wrapper_block clearfix">
									<div class="price_interval pull-left font_xs muted777">
										<?
										$quantity_from = ($arItem['PRICE_MATRIX']['ROWS'][$key]['QUANTITY_FROM'] ? $arItem['PRICE_MATRIX']['ROWS'][$key]['QUANTITY_FROM'] : 0);
										$quantity_to = $arItem['PRICE_MATRIX']['ROWS'][$key]['QUANTITY_TO'];
										$text = ($quantity_to ? Loc::getMessage('FROM').' '.$quantity_from.' '.Loc::getMessage('TO').' '.$quantity_to :Loc::getMessage('FROM').' '.$quantity_from );
										?>
										<div><?=$text?><?if(($arParams["SHOW_MEASURE"]=="Y") && $strMeasure):?> <?=$strMeasure?><?endif;?></div>
									</div>
							<?endif;?>
								<div class="prices-wrapper<?=($iCountPriceInterval > 1 ? ' pull-right text-right' : '');?>">
									<?if($arPrice["PRICE"] > $arPrice["DISCOUNT_PRICE"]){?>
										<div class="price font-bold <?=(($iCountPriceInterval > 1) ? 'font_xs' : ($arParams['MD_PRICE'] ? 'font_mlg' : 'font_mxs'));?>" data-currency="<?=$arPrice["CURRENCY"];?>" data-value="<?=$arPrice["DISCOUNT_PRICE"];?>">
											<?if(strlen($arPrice["DISCOUNT_PRICE"])):?>
												<?if($arItem['SHOW_FROM_LANG'] == 'Y'):?><span><?=Loc::getMessage('FROM')?></span><?endif;?>
												<span class="values_wrapper"><?=\Aspro\Functions\CAsproMaxItem::getCurrentPrice("DISCOUNT_PRICE", $arPrice);?></span><?if(($arParams["SHOW_MEASURE"]=="Y") && $strMeasure && $arPrice["DISCOUNT_PRICE"]):?><span class="price_measure">/<?=$strMeasure?></span><?endif;?>
											<?endif;?>
										</div>
										<?if($arParams["SHOW_OLD_PRICE"]=="Y"):?>
											<div class="price discount" data-currency="<?=$arPrice["CURRENCY"];?>" data-value="<?=$arPrice["PRICE"];?>">
												<span class="values_wrapper <?=($arParams['MD_PRICE'] ? 'font_sm' : 'font_xs');?> muted"><?=\Aspro\Functions\CAsproMaxItem::getCurrentPrice("PRICE", $arPrice);?></span>
											</div>
										<?endif;?>
									<?}else{?>
										<div class="price font-bold <?=(($iCountPriceInterval > 1) ? 'font_xs' : ($arParams['MD_PRICE'] ? 'font_mlg' : 'font_mxs dd'));?>" data-currency="<?=$arPrice["CURRENCY"];?>" data-value="<?=$arPrice["DISCOUNT_PRICE"];?>">
											<? if($arPrice['DISCOUNT_PRICE'] || $sMissingGoodsPriceDisplay === 'PRICE'): ?>
												<?if($arItem['SHOW_FROM_LANG'] == 'Y'):?><span><?=Loc::getMessage('FROM')?></span><?endif;?>
												<span><span class="values_wrapper"><?=\Aspro\Functions\CAsproMaxItem::getCurrentPrice("PRICE", $arPrice);?></span><?if(($arParams["SHOW_MEASURE"]=="Y") && $strMeasure && $arPrice["PRICE"]):?><span class="price_measure">/<?=$strMeasure?></span><?endif;?></span>
											<? elseif($sMissingGoodsPriceDisplay === 'TEXT'): ?>
												<?= $sMissingGoodsPriceDisplayText; ?>
											<? endif; ?>
										</div>
									<?}?>
								</div>
							<?if($iCountPriceInterval > 1):?>
								</div>
							<?else:
								if($arParams['SHOW_DISCOUNT_PERCENT'] == 'Y' && $arPrice["PRICE"] > $arPrice["DISCOUNT_PRICE"]):?>
									<?$ratio = (!$bPriceRows ? $arAddToBasketData["MIN_QUANTITY_BUY"] : 1);?>
									<div class="sale_block">
										<div class="sale_wrapper font_xxs">
											<?$diff = ($arPrice["PRICE"] - $arPrice["DISCOUNT_PRICE"]);?>
											<?if($arParams['SHOW_DISCOUNT_PERCENT_NUMBER'] != 'Y'):?>
												<div class="inner-sale rounded1">
													<span class="title"><?=GetMessage("CATALOG_ECONOMY");?></span> <div class="text"><span class="values_wrapper" data-currency="<?=$arPrice["CURRENCY"];?>" data-value="<?=($diff*$ratio);?>"><?=\Aspro\Functions\CAsproMaxItem::getCurrentPrice($diff, $arPrice, false)?></span></div>
												</div>
											<?else:?>
												<div class="sale-number rounded2">
													<?$percent=round(($diff/$arPrice["PRICE"])*100);?>
													<?if($percent && $percent<100){?>
														<div class="value">-<span><?=$percent;?></span>%</div>
													<?}?>
													<div class="inner-sale rounded1">
														<div class="text"><?=GetMessage("CATALOG_ECONOMY");?> <span class="values_wrapper"><?=\Aspro\Functions\CAsproMaxItem::getCurrentPrice($diff, $arPrice, false);?></span></div>
													</div>
												</div>
											<?endif;?>
										</div>
									</div>
								<?endif;?>
							<?endif;?>
						<?endforeach;?>
						</div>
						<?if($iCountPriceGroup > 1):?>
							</div>
						<?endif;?>
					<?endforeach;?>
				</div>
				<?if($bShowPopupPrice):?>
							</div>
							<div class="more-btn text-center">
								<a href="" class="font_upper colored_theme_hover_bg"><?=Loc::getMessage("MORE_LINK")?></a>
							</div>
						</div>
					</div>
				<?endif;?>
			<?$html = ob_get_contents();
			ob_end_clean();

			foreach(GetModuleEvents(ASPRO_MAX_MODULE_ID, 'OnAsproShowPriceMatrix', true) as $arEvent) // event for manipulation price matrix
				ExecuteModuleEventEx($arEvent, array($arItem, $arParams, $strMeasure, $arAddToBasketData, &$html));
		}
		return $html;
	}

	public static function showPriceRangeTop($arItem, $arParams, $mess = '', $bOtherPrice = false){
		$html = '';
		if($arItem)
		{
			if(isset($arItem['PRICE_MATRIX']) && $arItem['PRICE_MATRIX'])
			{
				ob_start();
					$rangSelected = $arItem['ITEM_QUANTITY_RANGE_SELECTED'];
					$priceSelected = $arItem['ITEM_PRICE_SELECTED'];
					if(isset($arItem['FIX_PRICE_MATRIX']) && $arItem['FIX_PRICE_MATRIX'])
					{
						$rangSelected = $arItem['FIX_PRICE_MATRIX']['RANGE_SELECT'];
						$priceSelected = $arItem['FIX_PRICE_MATRIX']['PRICE_SELECT'];
					}

					if (isset($arItem['ITEM_PRICES']) && is_array($arItem['ITEM_PRICES'])) {
						foreach ($arItem['ITEM_PRICES'] as $key => $arPrice) {
							$maxValue = $arPrice['QUANTITY_TO'] ?? PHP_INT_MAX;
							if (isset($arPrice['MIN_QUANTITY']) && $arPrice['MIN_QUANTITY'] && $arPrice['MIN_QUANTITY'] <= $maxValue) {
								$priceSelected = $key;
								break;
							}
						}
					}
					?>
					<?if(
						static::GetFrontParametrValue('SHOW_POPUP_PRICE') == 'Y' 
						&& (
							count($arItem['PRICE_MATRIX']['ROWS']) > 1 
							|| count($arItem['PRICE_MATRIX']['COLS']) > 1
						)
						&& $arParams['USE_PRICE_COUNT'] == 'Y'
					):?>
						<div class="js-show-info-block more-item-info rounded3 bordered-block text-center"><?=static::showIconSvg("fw", SITE_TEMPLATE_PATH."/images/svg/dots.svg");?></div>
					<?endif;?>
					<? 
					$sMissingGoodsPriceDisplay = \CMax::GetFrontParametrValue('MISSING_GOODS_PRICE_DISPLAY');
					$sMissingGoodsPriceDisplayText = \CMax::GetFrontParametrValue('MISSING_GOODS_PRICE_DISPLAY_TEXT_VALUE');	
					?>
					<div class="with_matrix price_matrix_wrapper">
						<div class="prices-wrapper">
							<? if ($arItem['MIN_PRICE'] || !isset($arItem['MIN_PRICE']) && $arItem['ITEM_PRICES'] || $sMissingGoodsPriceDisplay === 'PRICE'): ?>
								<?if($bOtherPrice):?>
									<div class="price font-bold font_mxs">
										<div class="price_value_block values_wrapper">
											<?=\Aspro\Functions\CAsproMaxItem::getCurrentPrice("VALUE", $arItem['MIN_PRICE']);?></div><?if (($arParams['SHOW_MEASURE'] == 'Y') && $arSetItem["MEASURE"][$arSetItem["ID"]]["MEASURE"]["SYMBOL_RUS"]):?><span class="price_measure">/<?=$arSetItem["MEASURE"][$arSetItem["ID"]]["MEASURE"]["SYMBOL_RUS"]?></span><?endif;?>
									</div>
									<?if($arParams['SHOW_OLD_PRICE'] == 'Y' && $arItem['MIN_PRICE']['OLD_PRICE'] > $arItem['MIN_PRICE']['VALUE']):?>
										<div class="price discount"><span class="discount values_wrapper font_xs muted"><?=\Aspro\Functions\CAsproMaxItem::getCurrentPrice("OLD_PRICE", $arItem['MIN_PRICE']);?></span></div>
									<?endif;?>
								<?else:?>
									<div class="price font-bold font_mxs">
										<?if($arItem['ITEM_PRICES'][$priceSelected]["PRICE"] || $sMissingGoodsPriceDisplay === 'PRICE'):?>
											<div class="price_value_block values_wrapper">
												<?if($arItem['SHOW_FROM_LANG'] == 'Y'):?><span><?=Loc::getMessage('FROM')?></span><?endif;?>
												<?=\Aspro\Functions\CAsproMaxItem::getCurrentPrice("PRICE", $arItem['ITEM_PRICES'][$priceSelected]);?></div><?if (($arParams['SHOW_MEASURE'] == 'Y') && $arItem['CATALOG_MEASURE_NAME'] && $arItem['ITEM_PRICES'][$priceSelected]["PRICE"]):?><span class="price_measure">/<?=$arItem['CATALOG_MEASURE_NAME']?></span><?endif;?>
										<? elseif($sMissingGoodsPriceDisplay === 'TEXT'): ?>
											<?= $sMissingGoodsPriceDisplayText; ?>
										<? endif; ?>
									</div>
									<?if($arParams['SHOW_OLD_PRICE'] == 'Y' && $arItem['ITEM_PRICES'][$priceSelected]['BASE_PRICE'] > $arItem['ITEM_PRICES'][$priceSelected]['PRICE']):?>
										<div class="price discount"><span class="discount values_wrapper font_xs muted"><?=\Aspro\Functions\CAsproMaxItem::getCurrentPrice("BASE_PRICE", $arItem['ITEM_PRICES'][$priceSelected]);?></span></div>
									<?endif;?>
								<?endif;?>
							<? elseif ($sMissingGoodsPriceDisplay === 'TEXT'): ?>
								<div class="price font-bold font_mxs">
									<?=$sMissingGoodsPriceDisplayText; ?>
								</div>
							<? endif; ?>
						</div>
						<?if($arParams['SHOW_DISCOUNT_PERCENT'] == 'Y' && $arItem['ITEM_PRICES'][$priceSelected]['DISCOUNT']):?>
							<div class="sale_block matrix">
								<div class="sale_wrapper font_xxs">
									<?if($arParams['SHOW_DISCOUNT_PERCENT_NUMBER'] != 'Y'):?>
										<div class="inner-sale rounded1">
											<span class="title"><?=$mess;?></span>
											<div class="text">
											<span class="values_wrapper"><?=\Aspro\Functions\CAsproMaxItem::getCurrentPrice("DISCOUNT", $arItem['ITEM_PRICES'][$priceSelected]);?></span></div>
										</div>
									<?else:?>
										<div class="sale-number rounded2">
											<?$percent=$arItem['ITEM_PRICES'][$priceSelected]["PERCENT"];?>
											<?if($percent && $percent<100){?>
												<div class="value">-<span><?=$percent;?></span>%</div>
											<?}?>
											<div class="inner-sale rounded1">
												<div class="text">
												<span class="title"><?=$mess;?></span>
												<span class="values_wrapper"><?=\Aspro\Functions\CAsproMaxItem::getCurrentPrice("DISCOUNT", $arItem['ITEM_PRICES'][$priceSelected]);?></span></div>
											</div>
										</div>
									<?endif;?>
									<div class="clearfix"></div>
								</div>
							</div>
						<?endif;?>
					</div>
				<?$html = ob_get_contents();
				ob_end_clean();
				foreach(GetModuleEvents(ASPRO_MAX_MODULE_ID, 'OnAsproShowPriceRangeTop', true) as $arEvent) // event for manipulation price matrix top
					ExecuteModuleEventEx($arEvent, array($arItem, $arParams, $mess, &$html));
			}
		}
		return $html;
	}

	public static function checkPriceRangeExt($arResult = array()){
		$arData = array();
		if($arResult)
		{
			if(isset($arResult['ITEM_PRICE_MODE']) && $arResult['ITEM_PRICE_MODE'] == 'Q')
			{
				$arRang = array();
				$bFound = false;
				$quantity = (int)$arResult['CATALOG_MEASURE_RATIO'];

				$rangSelected = $arResult['ITEM_QUANTITY_RANGE_SELECTED'];
				$priceSelected = $arResult['ITEM_PRICE_SELECTED'];

				foreach($arResult['ITEM_QUANTITY_RANGES'] as $key => $arItemRang)
				{
					$arRang = $arItemRang;
					if($quantity >= (int)$arRang['SORT_FROM'] && (strpos($arRang['SORT_TO'], 'INF') !== false || $quantity <= (int)$arRang['SORT_TO']))
					{
						$bFound = true;
						$rangSelected = $arRang['HASH'];
						break;
					}
				}
				if(!$bFound && ($arRang = self::getMinPriceRangeExt($arResult['ITEM_QUANTITY_RANGES'])))
				{
					$rangSelected = $arRang['HASH'];
				}

				foreach($arResult['ITEM_PRICES'] as $key => $arPrice)
				{
					if ($arPrice['QUANTITY_HASH'] == $rangSelected)
					{
						$priceSelected = $key;
						break;
					}
				}

				$arData = array(
					'RANGE_SELECT' => $rangSelected,
					'PRICE_SELECT' => $priceSelected,
				);
			}
		}
		return $arData;
	}

	public static function getMinPriceRangeExt($arPriceRange = array()){
		$arRang = array();
		if($arPriceRange)
		{
			foreach($arPriceRange as $key => $arItemRang)
			{
				if(!$arRang || ((int)$arItemRang['SORT_FROM'] < (int)$arRang['SORT_FROM']))
				{
					$arRang = $arItemRang;
				}
			}
		}
		return $arRang;
	}

	public static function getChilds($input, &$start = 0, $level = 0){
		$childs = array();

		if(!$level){
			$lastDepthLevel = 1;
			if(is_array($input)){
				foreach($input as $i => $arItem){
					if($arItem["DEPTH_LEVEL"] > $lastDepthLevel){
						if($i > 0){
							$input[$i - 1]["IS_PARENT"] = 1;
						}
					}
					$lastDepthLevel = $arItem["DEPTH_LEVEL"];
				}
			}
		}

		for($i = $start, $count = count($input); $i < $count; ++$i){
			$item = $input[$i];
			if($level > $item['DEPTH_LEVEL'] - 1){
				break;
			}
			elseif(!empty($item['IS_PARENT'])){
				++$i;
				$item['CHILD'] = self::getChilds($input, $i, $level + 1);
				--$i;
			}
			$childs[] = $item;
		}

		$start = $i;

		if($GLOBALS['arTheme']['USE_REGIONALITY']['VALUE'] === 'Y' && $GLOBALS['arTheme']['USE_REGIONALITY']['DEPENDENT_PARAMS']['REGIONALITY_FILTER_ITEM']['VALUE'] === 'Y' && $GLOBALS['arRegion']){
			if(is_array($childs)){
				foreach($childs as $i => $item){
					if($item['PARAMS'] && isset($item['PARAMS']['LINK_REGION'])){
						if($item['PARAMS']['LINK_REGION']){
							if(!in_array($GLOBALS['arRegion']['ID'], $item['PARAMS']['LINK_REGION'])){
								unset($childs[$i]);
							}
						}
						else{
							unset($childs[$i]);
						}
					}
				}
			}
		}

		return $childs;
	}

	public static function unique_multidim_array($array, $key) {
	    $temp_array = array();
	    $i = 0;
	    $key_array = array();

	    foreach($array as $val) {
	        if (!in_array($val[$key], $key_array)) {
	            $key_array[$i] = $val[$key];
	            $temp_array[$i] = $val;
	        }
	        $i++;
	    }
	    return $temp_array;
	}

	public static function convertArray($array, $charset){
		global $APPLICATION;
	    if(is_array($array) && $array){
		    foreach($array as $key=>$arVal) {
		    	foreach($arVal as $key2=>$value){
					$array[$key][$key2]=$APPLICATION->ConvertCharset($value, 'UTF-8', $charset);
		    	}
		    }
		}else{
			$array=array();
		}
	    return $array;
	}

	public static function getChilds2($input, &$start = 0, $level = 0){
		static $arIblockItemsMD5 = array();

		if(!$level){
			$lastDepthLevel = 1;
			if($input && is_array($input)){
				foreach($input as $i => $arItem){
					if($arItem['DEPTH_LEVEL'] > $lastDepthLevel){
						if($i > 0){
							$input[$i - 1]['IS_PARENT'] = 1;
						}
					}
					$lastDepthLevel = $arItem['DEPTH_LEVEL'];
				}
			}
		}

		$childs = array();
		$count = count($input);
		for($i = $start; $i < $count; ++$i){
			$item = $input[$i];
			if(!isset($item)){
				continue;
			}
			if($level > $item['DEPTH_LEVEL'] - 1){
				break;
			}
			else{
				if(!empty($item['IS_PARENT'])){
					$i++;
					$item['CHILD'] = self::getChilds2($input, $i, $level+1);
					$i--;
				}

				$childs[] = $item;
			}
		}
		$start = $i;

		if(is_array($childs)){
			foreach($childs as $j => $item){
				if($item['PARAMS']){
					$md5 = md5($item['TEXT'].$item['LINK'].$item['SELECTED'].$item['PERMISSION'].$item['ITEM_TYPE'].$item['IS_PARENT'].serialize($item['ADDITIONAL_LINKS']).serialize($item['PARAMS']));
					if(isset($arIblockItemsMD5[$md5][$item['PARAMS']['DEPTH_LEVEL']])){
						if(isset($arIblockItemsMD5[$md5][$item['PARAMS']['DEPTH_LEVEL']][$level]) || ($item['DEPTH_LEVEL'] === 1 && !$level)){
							unset($childs[$j]);
							continue;
						}
					}
					if(!isset($arIblockItemsMD5[$md5])){
						$arIblockItemsMD5[$md5] = array($item['PARAMS']['DEPTH_LEVEL'] => array($level => true));
					}
					else{
						$arIblockItemsMD5[$md5][$item['PARAMS']['DEPTH_LEVEL']][$level] = true;
					}
				}
			}
		}

		if($GLOBALS['arTheme']['USE_REGIONALITY']['VALUE'] === 'Y' && $GLOBALS['arTheme']['USE_REGIONALITY']['DEPENDENT_PARAMS']['REGIONALITY_FILTER_ITEM']['VALUE'] === 'Y' && $GLOBALS['arRegion']){
			if(is_array($childs)){
				foreach($childs as $i => $item){
					if($item['PARAMS'] && isset($item['PARAMS']['LINK_REGION'])){
						if($item['PARAMS']['LINK_REGION']){
							if(!in_array($GLOBALS['arRegion']['ID'], $item['PARAMS']['LINK_REGION'])){
								unset($childs[$i]);
							}
						}
						else{
							unset($childs[$i]);
						}
					}
				}
			}
		}

		if(!$level){
			$arIblockItemsMD5 = array();
		}

		return $childs;
	}

	public static function getSectionChilds($PSID, &$arSections, &$arSectionsByParentSectionID, &$arItemsBySectionID, &$aMenuLinksExt, $bMenu = false){
		if($arSections && is_array($arSections)){

			foreach($arSections as $arSection){
				if($arSection['IBLOCK_SECTION_ID'] == $PSID){

					if (!$bMenu) {
						$arItem = array($arSection['NAME'], $arSection['SECTION_PAGE_URL'], array(), array('FROM_IBLOCK' => 1, 'DEPTH_LEVEL' => $arSection['DEPTH_LEVEL'], 'IBLOCK_ID' => $arSection['IBLOCK_ID']));
						$arItem[3]['IS_PARENT'] = (isset($arItemsBySectionID[$arSection['ID']]) || isset($arSectionsByParentSectionID[$arSection['ID']]) ? 1 : 0);
						if($arSection["PICTURE"])
							$arItem[3]["PICTURE"]=$arSection["PICTURE"];
						if($arSection["UF_REGION"] ?? false)
							$arItem[3]["LINK_REGION"]=$arSection["UF_REGION"];
						if($arSection["UF_MENU_BANNER"] ?? false)
							$arItem[3]["BANNERS"]=$arSection["UF_MENU_BANNER"];
						if($arSection["UF_MENU_BRANDS"] ?? false)
							$arItem[3]["BRANDS"]=$arSection["UF_MENU_BRANDS"];
						if($arSection["UF_CATALOG_ICON"] ?? false)
							$arItem[3]["SECTION_ICON"]=$arSection["UF_CATALOG_ICON"];
						$bCheck = ($arItem[3]['IS_PARENT']);
					} else {
						$arItem = array(
							'TEXT' => $arSection['NAME'],
							'LINK' => $arSection['SECTION_PAGE_URL'],
							array(),
							'PARAMS' => array('FROM_IBLOCK' => 1, 'DEPTH_LEVEL' => $arSection['DEPTH_LEVEL'], 'ID' => $arSection['ID'], 'IBLOCK_ID' => $arSection['IBLOCK_ID']),
							'DEPTH_LEVEL' => $arSection['DEPTH_LEVEL']
						);
						$arItem['PARAMS']['IS_PARENT'] = $arItem['IS_PARENT'] = (isset($arItemsBySectionID[$arSection['ID']]) || isset($arSectionsByParentSectionID[$arSection['ID']]) ? 1 : 0);
						if($arSection["PICTURE"])
							$arItem['PARAMS']["PICTURE"]=$arSection["PICTURE"];
						if($arSection["UF_REGION"])
							$arItem['PARAMS']["LINK_REGION"]=$arSection["UF_REGION"];
						if($arSection["UF_MENU_BANNER"])
							$arItem['PARAMS']["BANNERS"]=$arSection["UF_MENU_BANNER"];
						if($arSection["UF_MENU_BRANDS"])
							$arItem['PARAMS']["BRANDS"]=$arSection["UF_MENU_BRANDS"];
						if($arSection["UF_CATALOG_ICON"])
							$arItem['PARAMS']["SECTION_ICON"]=$arSection["UF_CATALOG_ICON"];
						$bCheck = ($arItem['PARAMS']['IS_PARENT']);
					}

					$aMenuLinksExt[] = $arItem;
					if($bCheck){
						// subsections
						self::getSectionChilds($arSection['ID'], $arSections, $arSectionsByParentSectionID, $arItemsBySectionID, $aMenuLinksExt, $bMenu);
						// section elements
						if($arItemsBySectionID[$arSection['ID']] && is_array($arItemsBySectionID[$arSection['ID']])){
							foreach($arItemsBySectionID[$arSection['ID']] as $arItem){
								if(is_array($arItem['DETAIL_PAGE_URL'])){
									if(isset($arItem['CANONICAL_PAGE_URL'])){
										$arItem['DETAIL_PAGE_URL'] = $arItem['CANONICAL_PAGE_URL'];
									}
									else{
										$arItem['DETAIL_PAGE_URL'] = $arItem['DETAIL_PAGE_URL'][key($arItem['DETAIL_PAGE_URL'])];
									}
								}

								$arTmpLink = array();
                                if($arItem['LINK_REGION']){
                                    $arTmpLink['LINK_REGION'] =  (array)$arItem['LINK_REGION'];
                                }
                                elseif(array_key_exists('PROPERTY_LINK_REGION_VALUE', $arItem)){
                                    $arTmpLink['LINK_REGION'] = (array)$arItem['PROPERTY_LINK_REGION_VALUE'];
                                }

								if (!$bMenu) {
									$aMenuLinksExt[] = array($arItem['NAME'], $arItem['DETAIL_PAGE_URL'], array(), array_merge(array('FROM_IBLOCK' => 1, 'DEPTH_LEVEL' => ($arSection['DEPTH_LEVEL'] + 1), 'IS_ITEM' => 1), $arTmpLink));
								} else {
									$aMenuLinksExt[] = array(
										'TEXT' => $arItem['NAME'],
										'LINK' => $arItem['DETAIL_PAGE_URL'],
										array(),
										'PARAMS' => array_merge(array('FROM_IBLOCK' => 1, 'DEPTH_LEVEL' => ($arSection['DEPTH_LEVEL'] + 1), 'IS_ITEM' => 1), $arTmpLink)
									);
								}
							}
						}
					}
				}
			}
		}
	}

	public static function getMenuChildsExt($arParams, &$aMenuLinksExt){
		if ($handler = \Aspro\Functions\CAsproMax::getCustomFunc(__FUNCTION__)) {
			call_user_func_array($handler, [$arParams, &$aMenuLinksExt]);
			return;
		}

		$catalog_id = \Bitrix\Main\Config\Option::get('aspro.max', 'CATALOG_IBLOCK_ID', CMaxCache::$arIBlocks[SITE_ID]['aspro_max_catalog']['aspro_max_catalog'][0]);
		$bIsCatalog = $arParams['IBLOCK_ID'] == $catalog_id;

		$arParams['CATALOG_IBLOCK_ID'] = $catalog_id;
		$arParams['IS_CATALOG_IBLOCK'] = $bIsCatalog;

		foreach(GetModuleEvents(ASPRO_MAX_MODULE_ID, 'BeforeAsproGetMenuChildsExt', true) as $arEvent) // event for manipulation store quantity block
			ExecuteModuleEventEx($arEvent, array($arParams, &$aMenuLinksExt));

		$arSectionFilter = array(
			'IBLOCK_ID' => $arParams['IBLOCK_ID'],
			'ACTIVE' => 'Y',
			'GLOBAL_ACTIVE' => 'Y',
			'ACTIVE_DATE' => 'Y',
			'<DEPTH_LEVEL' => \Bitrix\Main\Config\Option::get("aspro.max", "MAX_DEPTH_MENU", 2),
		);
		$arSectionSelect = array(
			'ID',
			'SORT',
			'ACTIVE',
			'IBLOCK_ID',
			'NAME',
			'SECTION_PAGE_URL',
			'DEPTH_LEVEL',
			'IBLOCK_SECTION_ID',
			'PICTURE',
			'UF_REGION',
		);

		if($bIsCatalog) {
			// $arSectionFilter = array_merge($arSectionFilter, array(  ));
			$arSectionSelect = array_merge($arSectionSelect, array( 'UF_MENU_BANNER', 'UF_MENU_BRANDS', 'UF_CATALOG_ICON', ));
		}

		if(array_key_exists('SECTION_FILTER', $arParams) && $arParams['SECTION_FILTER']) {
			$arSectionFilter = array_merge($arSectionFilter, $arParams['SECTION_FILTER']);
		}
		if(array_key_exists('SECTION_SELECT', $arParams) && $arParams['SECTION_SELECT']) {
			$arSectionSelect = array_merge($arSectionSelect, $arParams['SECTION_SELECT']);
		}

		if($arParams['MENU_PARAMS']['MENU_SHOW_SECTIONS'] == 'Y')
		{
			$arSections = CMaxCache::CIBlockSection_GetList(array('SORT' => 'ASC', 'ID' => 'ASC', 'CACHE' => array('TAG' => CMaxCache::GetIBlockCacheTag($arParams['IBLOCK_ID']), 'MULTI' => 'Y')), $arSectionFilter, false, $arSectionSelect);
			$arSectionsByParentSectionID = CMaxCache::GroupArrayBy($arSections, array('MULTI' => 'Y', 'GROUP' => array('IBLOCK_SECTION_ID')));
		}

		if(!$bIsCatalog) {
			if($arParams['MENU_PARAMS']['MENU_SHOW_ELEMENTS'] == 'Y'){
				$arElementFilter = array(
					'IBLOCK_ID' => $arParams['IBLOCK_ID'],
					'ACTIVE' => 'Y',
					'ACTIVE_DATE' => 'Y',
					'INCLUDE_SUBSECTIONS' => 'Y',
				);
				$useGlobalActive = \Bitrix\Main\Config\Option::get('aspro.max', 'USE_SECTION_GLOBAL_ACTIVE', 'N') === 'Y';
				if($useGlobalActive){
					$arElementFilter['SECTION_GLOBAL_ACTIVE'] = 'Y';
				}
				$arElementSelect = array(
					'ID',
					'SORT',
					'ACTIVE',
					'IBLOCK_ID',
					'NAME',
					'DETAIL_PAGE_URL',
					'DEPTH_LEVEL',
					'IBLOCK_SECTION_ID',
					'PROPERTY_LINK_REGION',
				);

				if(array_key_exists('ELEMENT_FILTER', $arParams) && $arParams['ELEMENT_FILTER']) {
					$arSectionFilter = array_merge($arSectionFilter, $arParams['ELEMENT_FILTER']);
				}
				if(array_key_exists('ELEMENT_SELECT', $arParams) && $arParams['ELEMENT_SELECT']) {
					$arSectionSelect = array_merge($arSectionSelect, $arParams['ELEMENT_SELECT']);
				}

				$arItems = CMaxCache::CIBlockElement_GetList(array('SORT' => 'ASC', 'ID' => 'DESC', 'CACHE' => array('TAG' => CMaxCache::GetIBlockCacheTag($arParams['IBLOCK_ID']), 'MULTI' => 'Y')), $arElementFilter, false, false, $arElementSelect);

				/*filter by region*/
				global $arRegion;
				if($arItems)
				{
					foreach($arItems as $key => $arItem)
					{
						$arTmpProp = array();
						$rsPropRegion = CIBlockElement::GetProperty($arItem['IBLOCK_ID'], $arItem['ID'], array('sort' => 'asc'), Array('CODE'=>'LINK_REGION'));
						while($arPropRegion = $rsPropRegion->Fetch())
						{
							if($arPropRegion['VALUE'])
								$arTmpProp[] = $arPropRegion['VALUE'];
						}
						$arItems[$key]['LINK_REGION'] = $arTmpProp;
					}
				}

				if($arParams['MENU_PARAMS']['MENU_SHOW_SECTIONS'] == 'Y'){
					$arItemsBySectionID = CMaxCache::GroupArrayBy($arItems, array('MULTI' => 'Y', 'GROUP' => array('IBLOCK_SECTION_ID')));
				}
				else{
					$arItemsRoot = CMaxCache::CIBlockElement_GetList(array('SORT' => 'ASC', 'ID' => 'DESC', 'CACHE' => array('TAG' => CMaxCache::GetIBlockCacheTag($arParams['IBLOCK_ID']), 'MULTI' => 'Y')), array('IBLOCK_ID' => $arParams['IBLOCK_ID'], 'ACTIVE' => 'Y', 'ACTIVE_DATE' => 'Y', 'SECTION_ID' => 0));
					$arItems = array_merge((array)$arItems, (array)$arItemsRoot);
				}
			}
		}

		foreach(GetModuleEvents(ASPRO_MAX_MODULE_ID, 'OnAsproGetMenuChildsExt', true) as $arEvent) // event for manipulation store quantity block
			ExecuteModuleEventEx($arEvent, array($arParams, &$aMenuLinksExt));

		if($arSections) {
			static::getSectionChilds(false, $arSections, $arSectionsByParentSectionID, $arItemsBySectionID, $aMenuLinksExt);
		}

		if(!$bIsCatalog) {
			if($arItems && $arParams['MENU_PARAMS']['MENU_SHOW_SECTIONS'] != 'Y'){
				foreach($arItems as $arItem){
					$arExtParam = array('FROM_IBLOCK' => 1, 'DEPTH_LEVEL' => 1);
					if(isset($arItem['LINK_REGION']))
						$arExtParam['LINK_REGION'] = $arItem['LINK_REGION'];
					$aMenuLinksExt[] = array($arItem['NAME'], $arItem['DETAIL_PAGE_URL'], array(), $arExtParam);
				}
			}
		}

		foreach(GetModuleEvents(ASPRO_MAX_MODULE_ID, 'AfterAsproGetMenuChildsExt', true) as $arEvent) // event for manipulation store quantity block
			ExecuteModuleEventEx($arEvent, array($arParams, &$aMenuLinksExt));
	}

	public static function replaceMenuChilds(&$arResult, $arParams) {
		global $arTheme;

		$arMegaLinks = $arMegaItems = array();
		$replaceType = $arTheme['MEGA_MENU_STRUCTURE']['DEPENDENT_PARAMS']['REPLACE_TYPE']['VALUE'];

		$menuIblockId = CMaxCache::$arIBlocks[SITE_ID]['aspro_max_catalog']['aspro_max_megamenu'][0];
		if($menuIblockId){
			$arMenuSections = CMaxCache::CIblockSection_GetList(
				array(
					'SORT' => 'ASC',
					'ID' => 'ASC',
					'CACHE' => array(
						'TAG' => CMaxCache::GetIBlockCacheTag($menuIblockId),
						'GROUP' => array('DEPTH_LEVEL'),
						'MULTI' => 'Y',
					)
				),
				array(
					'ACTIVE' => 'Y',
					'GLOBAL_ACTIVE' => 'Y',
					'IBLOCK_ID' => $menuIblockId,
					'<=DEPTH_LEVEL' => $arParams['MAX_LEVEL'],
				),
				false,
				array(
					'ID',
					'NAME',
					'IBLOCK_SECTION_ID',
					'DEPTH_LEVEL',
					'PICTURE',
					'UF_MENU_LINK',
					'UF_MEGA_MENU_LINK',
					"UF_CATALOG_ICON",
				)
			);

			ksort($arMenuSections);

			if($arMenuSections){
				$cur_page = $GLOBALS['APPLICATION']->GetCurPage(true);
				$cur_page_no_index = $GLOBALS['APPLICATION']->GetCurPage(false);
				$some_selected = false;
				$bMultiSelect = $arParams['ALLOW_MULTI_SELECT'] === 'Y';

				foreach($arMenuSections as $depth => $arLinks){
					foreach($arLinks as $arLink){
						$url = trim($arLink['UF_MEGA_MENU_LINK']);
						$url = $url ? $url : trim($arLink['UF_MENU_LINK']);
						if(
							(
								$depth == 1 &&
								strlen($url)
							) ||
							$depth > 1
						){
							$arMegaItem = array(
								'TEXT' => htmlspecialcharsbx($arLink['NAME']),
								'NAME' => htmlspecialcharsbx($arLink['NAME']),
								'LINK' => strlen($url) ? $url : 'javascript:;',
								'SECTION_PAGE_URL' => strlen($url) ? $url : 'javascript:;',
								'SELECTED' => false,
								'PARAMS' => array(
									'PICTURE' => $arLink['PICTURE'],
									'SORT' => $arLink['SORT'],
									'SECTION_ICON' => $arLink['UF_CATALOG_ICON'],
								),
								'CHILD' => array(),
							);

							if( $arLink['PICTURE'] ) {
								$arMegaItem['IMAGES']['src'] = CFile::GetPath($arLink['PICTURE']);
							}

							$arMegaItems[$arLink['ID']] =& $arMegaItem;

							if($depth > 1){
								if(
									strlen($url) &&
									($bMultiSelect || !$some_selected)
								){
									$arMegaItem['SELECTED'] = CMenu::IsItemSelected($url, $cur_page, $cur_page_no_index);
								}

								if($arMegaItems[$arLink['IBLOCK_SECTION_ID']]){
									$arMegaItems[$arLink['IBLOCK_SECTION_ID']]['IS_PARENT'] = 1;
									$arMegaItems[$arLink['IBLOCK_SECTION_ID']]['CHILD'][] =& $arMegaItems[$arLink['ID']];
								}
							}
							else{
								$arMegaLinks[] =& $arMegaItems[$arLink['ID']];
							}

							unset($arMegaItem);
						}
					}
				}
			}
		}

		if($arMegaLinks){
			foreach($arResult as $key => $arItem){
				foreach($arMegaLinks as $arLink){
					if($arItem['LINK'] == $arLink['LINK']){
						if($replaceType == 'REPLACE') {
							if($arResult[$key]['PARAMS']['MEGA_MENU_CHILDS']){
								array_splice($arResult, $key, 1, $arLink['CHILD']);
							}
							else{
								$arResult[$key]['CHILD'] =& $arLink['CHILD'];
								$arResult[$key]['IS_PARENT'] = boolval($arLink['CHILD']);
							}
						} else {
							if($arResult[$key]['PARAMS']['MEGA_MENU_CHILDS']){
								if( array_key_exists('CHILD', $arResult[$key]) && $arResult[$key]['CHILD'] ) {
									$arLink['CHILD'] = self::CompareMenuItems($arResult[$key]['CHILD'], $arLink['CHILD']);
								}
								array_splice($arResult, $key, 1, $arLink['CHILD']);
							}
							else{
								$arResult[$key]['CHILD'] = self::CompareMenuItems($arResult[$key]['CHILD'], $arLink['CHILD']);
								$arResult[$key]['IS_PARENT'] = boolval($arResult[$key]['CHILD']);
							}
						}
					}
				}
			}
		}
	}

	public static function getStoriesSections($arParams) {
		if ($handler = \Aspro\Functions\CAsproMax::getCustomFunc(__FUNCTION__)) {
			return call_user_func_array($handler, [$arParams]);
		}

		$arSectionsFilter = array(
			'IBLOCK_ID' => $arParams['IBLOCK_ID'],
			'ACTIVE' => 'Y',
			'GLOBAL_ACTIVE' => 'Y',
			'CNT_ACTIVE' => 'Y',
		);
		$arSectionsSelect = array(
			'ID',
			'NAME',
			'PICTURE',
			'IBLOCK_ID',
			'UF_REGION'
		);

		/*set region filter for sections*/
		global $arRegion;
		if ($arRegion && \Bitrix\Main\Config\Option::get(self::moduleID, 'REGIONALITY_FILTER_ITEM', 1000, SITE_ID) === 'Y') {
			$arSectionsFilter[] = [
				'LOGIC' => 'OR',
				['UF_REGION' => ''],
				['UF_REGION' => $arRegion['ID']],
			];
		}
		/**/

		foreach(GetModuleEvents(ASPRO_MAX_MODULE_ID, 'BeforeAsproGetStoriesSections', true) as $arEvent)
			ExecuteModuleEventEx($arEvent, array($arParams, &$arSectionsFilter, &$arSectionsSelect));

		$arSections = CMaxCache::CIBLockSection_GetList(array($arParams['SORT_DATA']['SORT'] => $arParams['SORT_DATA']['SORT_ORDER'], $arParams['SORT_DATA']['SORT_2'] => $arParams['SORT_DATA']['SORT_ORDER_2'],  'CACHE' => array('MULTI'=> 'N', "GROUP" => 'ID', 'TAG' => CMaxCache::GetIBlockCacheTag($arParams['IBLOCK_ID']))), $arSectionsFilter, true, $arSectionsSelect);

		if($arSections) {
			foreach($arSections as $sectionKey => $arSection) {
				if($arSection['PICTURE']) {
					$arSections[$sectionKey]['PICTURE'] = CFile::GetPath($arSection['PICTURE']);
				}
				if($arSection['ELEMENT_CNT'] > 0) {
					$arSections[$sectionKey]['CHILDS'] = array();
				} else {
					unset($arSections[$sectionKey]);
				}
			}

			if($arSections) {
				self::getStoriesElements($arParams['IBLOCK_ID'], $arSections);
			}
		}

		foreach(GetModuleEvents(ASPRO_MAX_MODULE_ID, 'AfterAsproGetStoriesSections', true) as $arEvent)
			ExecuteModuleEventEx($arEvent, array($arParams, &$arSections));

		/*hack for json_encode*/
		if ($arSections) {
			$arTmpSections = $arSections;
			$arSections = [];

			foreach ($arTmpSections as $arTmpSection) {
				$arSections[] = $arTmpSection;
			}

		}
		/**/

		return $arSections;
	}

	public static function getStoriesElements($iblockID, &$arSections) {
		if ($handler = \Aspro\Functions\CAsproMax::getCustomFunc(__FUNCTION__)) {
			call_user_func_array($handler, [$iblockID, &$arSections]);
			return;
		}

		$arSectionsIDs = array_keys($arSections);
		$arElementsFilter = array(
			'IBLOCK_ID' => $iblockID,
			'IBLOCK_SECTION_ID' => $arSectionsIDs,
			'ACTIVE' => 'Y',
		);
		$arElementsSelect = array(
			'ID',
			'PREVIEW_PICTURE',
			'IBLOCK_SECTION_ID',
			'PROPERTY_BTN_TEXT',
			'PROPERTY_BTN_CLASS',
			'PROPERTY_BTN_LINK',
		);

		/*set region filter for elements*/
		/*global $arRegion;
		if ($arRegion && \Bitrix\Main\Config\Option::get(self::moduleID, 'REGIONALITY_FILTER_ITEM', 1000, SITE_ID) === 'Y') {
			$arElementsFilter[] = ['PROPERTY_LINK_REGION' => $arRegion['ID']];
		}*/
		/**/

		foreach(GetModuleEvents(ASPRO_MAX_MODULE_ID, 'BeforeAsproGetStoriesElements', true) as $arEvent)
			ExecuteModuleEventEx($arEvent, array($arParams, &$arElementsFilter, &$arElementsSelect));

		$arElements = CMaxCache::CIBLockElement_GetList(array('SORT' => 'ASC', 'MULTI'=> 'Y', 'CACHE' => array('TAG' => CMaxCache::GetIBlockCacheTag($iblockID))), $arElementsFilter, false, false, $arElementsSelect);

		foreach(GetModuleEvents(ASPRO_MAX_MODULE_ID, 'AfterAsproGetStoriesElements', true) as $arEvent)
			ExecuteModuleEventEx($arEvent, array($arParams, &$arElements));

		if($arElements) {

			/*get PROPERTY_BTN_CLASS full info */
			$arPropertyBtnClassEnum = [];
			$arFilterProp = [
				'IBLOCK_ID' => $iblockID,
				'CODE' => 'BTN_CLASS'
			];

			$rsPropertyBtnClassEnum = \CIBlockPropertyEnum::GetList(
				[],
				$arFilterProp
			);
			while ($arProperty = $rsPropertyBtnClassEnum->Fetch()) {
				$arPropertyBtnClassEnum[$arProperty["ID"]] = $arProperty["XML_ID"];
			}
			/**/

			foreach($arElements as &$arElement) {
				if($arElement['PREVIEW_PICTURE']) {
					$arElement['PREVIEW_PICTURE'] = CFile::GetPath($arElement['PREVIEW_PICTURE']);
				}
				if ($arElement['PROPERTY_BTN_CLASS_ENUM_ID'] && $arPropertyBtnClassEnum && $arPropertyBtnClassEnum[$arElement['PROPERTY_BTN_CLASS_ENUM_ID']]) {
					$arElement['PROPERTY_BTN_CLASS_VALUE'] = $arPropertyBtnClassEnum[$arElement['PROPERTY_BTN_CLASS_ENUM_ID']];
				}
				$arSections[$arElement['IBLOCK_SECTION_ID']]['CHILDS'][] = $arElement;
			}
			unset($arElement);

			/*unset section with empty childs*/
			foreach ($arSections as $key => $arSection) {
				if ($arSection['ELEMENT_CNT'] && !$arSection['CHILDS']) {
					unset($arSections[$key]);
				}
			}
			/**/

		} else {
			$arSections = [];
		}
	}

	public static function CompareMenuItems($parentMenu, $childMenu) {
		$arMenuEnd = $childMenu;
		foreach($parentMenu as &$parentLink) {
			foreach($childMenu as $childKey => $childLink) {
				if($childLink['LINK'] == $parentLink['LINK']) {
					$parentLink['NAME'] = $parentLink['TEXT'] = $childLink['NAME'];

					if($childLink['PARAMS']['PICTURE'] && isset($parentLink['PARAMS']['PICTURE'])) {
						$parentLink['PARAMS']['PICTURE'] = $childLink['PARAMS']['PICTURE'];
					}

					if($childLink['PARAMS']['SORT'] && isset($parentLink['PARAMS']['SORT'])) {
						$parentLink['PARAMS']['SORT'] = $childLink['PARAMS']['SORT'];
					}

					if($childLink['CHILD']) {
						if($parentLink['CHILD']) {
							$parentLink['CHILD'] = self::CompareMenuItems($parentLink['CHILD'], $childLink['CHILD']);
						} else {
							$parentLink['CHILD'] = $childLink['CHILD'];
						}
					}
					unset($arMenuEnd[$childKey]);

					if($parentLink['CHILD'] && count($parentLink['CHILD']) > 1) {
						\Bitrix\Main\Type\Collection::sortByColumn(
							$parentLink['CHILD'],
							'PARAMS',
							function($params) {
								$result = isset($params['SORT']) ? $params['SORT'] : 500;
								return $result;
							}
						);
					}
				}
			}
		}

		if($arMenuEnd) {
			$parentMenu = array_merge($parentMenu, $arMenuEnd);
		}
		\Bitrix\Main\Type\Collection::sortByColumn(
			$parentMenu,
			'PARAMS',
			function($params) {
				$result = isset($params['SORT']) ? $params['SORT'] : 500;
				return $result;
			}
		);
		unset($parentLink);

		return $parentMenu;
	}

	public static function cmpByID($a, $b){
		return ($b['ID'] - $a['ID']);
	}

	public static function cmpBySort($a, $b){
		return ($a['SORT'] - $b['SORT']);
	}

	public static function cmpByIDFilter($a, $b){
		global $IDFilter;
		$ak = array_search($a['ID'], $IDFilter);
		$bk = array_search($b['ID'], $IDFilter);
		if($ak === $bk){
			return 0;
		}
		else{
			return ($ak > $bk ? 1 : -1);
		}
	}

	public static function getChainNeighbors($curSectionID, $chainPath){
		static $arSections, $arSectionsIDs, $arSubSections;
		$arResult = array();

		if($arSections === NULL){
			$arSections = $arSectionsIDs = $arSubSections = array();
			$IBLOCK_ID = false;
			$nav = CIBlockSection::GetNavChain(false, $curSectionID, array("ID", "IBLOCK_ID", "IBLOCK_SECTION_ID", "SECTION_PAGE_URL"));
			while($ar = $nav->GetNext()){
				$arSections[] = $ar;
				$arSectionsIDs[] = ($ar["IBLOCK_SECTION_ID"] ? $ar["IBLOCK_SECTION_ID"] : 0);
				$IBLOCK_ID = $ar["IBLOCK_ID"];
			}

			if($arSectionsIDs){
				$arSubSectionsFilter = array("ACTIVE" => "Y", "GLOBAL_ACTIVE" => "Y", "IBLOCK_ID" => $IBLOCK_ID, "SECTION_ID" => $arSectionsIDs);
				$resSubSection = CIBlockSection::GetList(array('SORT' => 'ASC'), self::makeSectionFilterInRegion($arSubSectionsFilter), false, array("ID", "NAME", "IBLOCK_SECTION_ID", "SECTION_PAGE_URL"));
				while($arSubSection = $resSubSection->GetNext()){
					$arSubSection["IBLOCK_SECTION_ID"] = ($arSubSection["IBLOCK_SECTION_ID"] ? $arSubSection["IBLOCK_SECTION_ID"] : 0);
					$arSubSections[$arSubSection["IBLOCK_SECTION_ID"]][] = $arSubSection;
				}

				if(in_array(0, $arSectionsIDs)){
					$arSubSectionsFilter = array("ACTIVE" => "Y", "GLOBAL_ACTIVE" => "Y", "IBLOCK_ID" => $IBLOCK_ID, "SECTION_ID" => false);
					$resSubSection = CIBlockSection::GetList(array('SORT' => 'ASC'), self::makeSectionFilterInRegion($arSubSectionsFilter), false, array("ID", "NAME", "IBLOCK_SECTION_ID", "SECTION_PAGE_URL"));
					while($arSubSection = $resSubSection->GetNext()){
						$arSubSections[$arSubSection["IBLOCK_SECTION_ID"]][] = $arSubSection;
					}
				}
			}
		}

		if($arSections && strlen($chainPath)){
			foreach($arSections as $arSection){
				if($arSection["SECTION_PAGE_URL"] == $chainPath){
					if($arSubSections[$arSection["IBLOCK_SECTION_ID"]]){
						foreach($arSubSections[$arSection["IBLOCK_SECTION_ID"]] as $arSubSection){
							if($curSectionID !== $arSubSection["ID"]){
								$arResult[] = array("NAME" => $arSubSection["NAME"], "LINK" => $arSubSection["SECTION_PAGE_URL"]);
							}
						}
					}
					break;
				}
			}
		}

		return $arResult;
	}

	public static function getSectionsIds_NotInRegion($iblockId = false, $regionId = false){
		static $arCache, $arIblockHasUFRegion;

		$arSectionsIds = array();

		if(!$iblockId){
			$iblockId = CMaxCache::$arIBlocks[SITE_ID]['aspro_max_catalog']['aspro_max_catalog'][0];
		}

		if($iblockId){
			if(!isset($arIblockHasUFRegion)){
				$arIblockHasUFRegion = array();
			}
			if(!isset($arIblockHasUFRegion[$iblockId])){
				$arIblockHasUFRegion[$iblockId] = false;

				$rsData = \CUserTypeEntity::GetList(array('ID' => 'ASC'), array('ENTITY_ID' => 'IBLOCK_'.$iblockId.'_SECTION', 'FIELD_NAME' => 'UF_REGION'));
				if($arRes = $rsData->Fetch()){
					$arIblockHasUFRegion[$iblockId] = true;
				}
			}

			if($arIblockHasUFRegion[$iblockId]){
				if(!$regionId && $GLOBALS['arRegion']){
					$regionId = $GLOBALS['arRegion']['ID'];
				}

				if($regionId){
					if(!isset($arCache)){
						$arCache = array();
					}

					if(!isset($arCache[$iblockId])){
						$arCache[$iblockId] = array();
					}

					if(!isset($arCache[$iblockId][$regionId])){
						if($arSections = CMaxCache::CIBLockSection_GetList(
							array(
								'CACHE' => array(
									'TAG' => CMaxCache::GetIBlockCacheTag($iblockId),
									'MULTI' => 'Y'
								)
							),
							array(
								'IBLOCK_ID' => $iblockId,
								'!UF_REGION' => $regionId,
							),
							false,
							array(
								'ID',
								'IBLOCK_ID',
								'UF_REGION',
								'RIGHT_MARGIN',
								'LEFT_MARGIN',
							),
							false
						)){
							$arSectionsIds = array_column($arSections, 'ID');
							if($arSectionsIds){
								if($arSectionsIds_ = CMaxCache::CIBLockSection_GetList(
									array(
										'CACHE' => array(
											'TAG' => CMaxCache::GetIBlockCacheTag($iblockId),
											'MULTI' => 'Y',
											'RESULT' => array('ID'),
										)
									),
									array(
										'IBLOCK_ID' => $iblockId,
										'ID' => $arSectionsIds,
										'UF_REGION' => $regionId,
									),
									false,
									array('ID'),
									false
								)){
									$arSectionsIds = array_diff($arSectionsIds, $arSectionsIds_);
								}
							}

							$arSubSectionsIds = array();
							foreach($arSections as $arSection){
								if(in_array($arSection['ID'], $arSectionsIds)){
									if(($arSection['LEFT_MARGIN'] + 1) < $arSection['RIGHT_MARGIN']){
										$arSubSectionsIds[] = $arSection['ID'];
									}
								}
							}

							while($arSubSectionsIds){
								if($arSections = CMaxCache::CIBLockSection_GetList(
									array(
										'CACHE' => array(
											'TAG' => CMaxCache::GetIBlockCacheTag($iblockId),
											'MULTI' => 'Y'
										)
									),
									array(
										'IBLOCK_ID' => $iblockId,
										'SECTION_ID' => $arSubSectionsIds,
									),
									false,
									array(
										'ID',
										'RIGHT_MARGIN',
										'LEFT_MARGIN',
									),
									false
								)){
									$arSubSectionsIds = array_column($arSections, 'ID');
									if($arSubSectionsIds){
										if($arSectionsIds_ = CMaxCache::CIBLockSection_GetList(
											array(
												'CACHE' => array(
													'TAG' => CMaxCache::GetIBlockCacheTag($iblockId),
													'MULTI' => 'Y',
													'RESULT' => array('ID'),
												)
											),
											array(
												'IBLOCK_ID' => $iblockId,
												'ID' => $arSubSectionsIds,
												'UF_REGION' => $regionId,
											),
											false,
											array('ID'),
											false
										)){
											$arSubSectionsIds = array_diff($arSubSectionsIds, $arSectionsIds_);
										}
									}

									if($arSubSectionsIds){
										$arSectionsIds = array_merge($arSectionsIds, $arSubSectionsIds);
									}

									$arSubSubSectionsIds = array();
									foreach($arSections as $arSection){
										if(in_array($arSection['ID'], $arSubSectionsIds)){
											if(($arSection['LEFT_MARGIN'] + 1) < $arSection['RIGHT_MARGIN']){
												$arSubSubSectionsIds[] = $arSection['ID'];
											}
										}
									}
									$arSubSectionsIds = $arSubSubSectionsIds;
								}
								else{
									$arSubSectionsIds = array();
								}
							}
						}

						$arCache[$iblockId][$regionId] = $arSectionsIds;
					}
					else{
						$arSectionsIds = $arCache[$iblockId][$regionId];
					}
				}
			}
		}

		return $arSectionsIds;
	}

	public static function makeSectionFilterInRegion(&$arFilter, $regionId = false){

		$bFilterItem = false;
		if( isset($GLOBALS['arTheme']['USE_REGIONALITY']['VALUE']) ) {
			$bFilterItem = $GLOBALS['arTheme']['USE_REGIONALITY']['VALUE'] === 'Y' && $GLOBALS['arTheme']['USE_REGIONALITY']['DEPENDENT_PARAMS']['REGIONALITY_FILTER_ITEM']['VALUE'] === 'Y';
		} else {
			$bFilterItem = $GLOBALS['arTheme']['USE_REGIONALITY'] === 'Y' && $GLOBALS['arTheme']['REGIONALITY_FILTER_ITEM'] === 'Y';
		}

		if($bFilterItem){
			$iblockId = $arFilter['IBLOCK_ID'];
			if(!$iblockId){
				$iblockId = CMaxCache::$arIBlocks[SITE_ID]['aspro_max_catalog']['aspro_max_catalog'][0];
			}

			if($iblockId){
				if(!$regionId && $GLOBALS['arRegion']){
					$regionId = $GLOBALS['arRegion']['ID'];
				}

				if($regionId){
					if($arSectionsIds = self::getSectionsIds_NotInRegion($arFilter['IBLOCK_ID'], $regionId)){
						$arFilter['!ID'] = $arSectionsIds;
					}
				}
			}
		}

		return $arFilter;
	}

	public static function makeElementFilterInRegion(&$arFilter, $regionId = false){

		$bFilterItem = false;
		if( isset($GLOBALS['arTheme']['USE_REGIONALITY']['VALUE']) ) {
			$bFilterItem = $GLOBALS['arTheme']['USE_REGIONALITY']['VALUE'] === 'Y' && $GLOBALS['arTheme']['USE_REGIONALITY']['DEPENDENT_PARAMS']['REGIONALITY_FILTER_ITEM']['VALUE'] === 'Y';
		} else {
			$bFilterItem = $GLOBALS['arTheme']['USE_REGIONALITY'] === 'Y' && $GLOBALS['arTheme']['REGIONALITY_FILTER_ITEM'] === 'Y';
		}

		if($bFilterItem){
			$iblockId = $arFilter['IBLOCK_ID'];
			if(!$iblockId){
				$iblockId = CMaxCache::$arIBlocks[SITE_ID]['aspro_max_catalog']['aspro_max_catalog'][0];
			}

			if($iblockId){
				if(!$regionId && $GLOBALS['arRegion']){
					$regionId = $GLOBALS['arRegion']['ID'];
				}

				if($regionId){
					if($arSectionsIds = self::getSectionsIds_NotInRegion($arFilter['IBLOCK_ID'], $regionId)){
						$arFilter['!IBLOCK_SECTION_ID'] = $arSectionsIds;
					}
				}
			}
		}
		return $arFilter;
	}

	public static function isIblockHasPropertyLinkRegion($iblockId){
		static $arIblockHasLinkRegion;

		if($iblockId){
			if(!isset($arIblockHasLinkRegion)){
				$arIblockHasLinkRegion = array();
			}

			if(!isset($arIblockHasLinkRegion[$iblockId])){
				$arIblockHasLinkRegion[$iblockId] = false;

				$rsData = \CIBlockProperty::GetList(array(), array('CODE' => 'LINK_REGION', 'IBLOCK_ID' => $iblockId, 'ACTIVE' => 'Y'));
				if($arRes = $rsData->Fetch()){
					$arIblockHasLinkRegion[$iblockId] = true;
				}
			}

			return $arIblockHasLinkRegion[$iblockId];
		}

		return false;
	}

	public static function checkElementsIdsInRegion(&$arIds, $iblockId = false, $regionId = false){

		$bFilterItem = false;
		if( isset($GLOBALS['arTheme']['USE_REGIONALITY']['VALUE']) ) {
			$bFilterItem = $GLOBALS['arTheme']['USE_REGIONALITY']['VALUE'] === 'Y' && $GLOBALS['arTheme']['USE_REGIONALITY']['DEPENDENT_PARAMS']['REGIONALITY_FILTER_ITEM']['VALUE'] === 'Y';
		} else {
			$bFilterItem = $GLOBALS['arTheme']['USE_REGIONALITY'] === 'Y' && $GLOBALS['arTheme']['REGIONALITY_FILTER_ITEM'] === 'Y';
		}

		if($bFilterItem && $arIds){
			if(!$iblockId){
				$iblockId = CMaxCache::$arIBlocks[SITE_ID]['aspro_max_catalog']['aspro_max_catalog'][0];
			}

			if($iblockId){
				if(!$regionId && $GLOBALS['arRegion']){
					$regionId = $GLOBALS['arRegion']['ID'];
				}

				if($regionId){
					if($arSectionsIds = self::getSectionsIds_NotInRegion($arFilter['IBLOCK_ID'], $regionId)){
						$arIds = CMaxCache::CIBLockElement_GetList(
							array(
								'CACHE' => array(
									'TAG' => CMaxCache::GetIBlockCacheTag($iblockId),
									'RESULT' => array('ID'),
									'MULTI' => 'Y',
								)
							),
							array(
								'ID' => $arIds,
								'IBLOCK_ID' => $iblockId,
								'!IBLOCK_SECTION_ID' => $arSectionsIds,
							),
							false,
							false,
							array('ID')
						);
					}
				}
			}
		}

		return $arIds;
	}

	public static function drawFormField($FIELD_SID, $arQuestion){
		?>
		<?$arQuestion["HTML_CODE"] = str_replace('name=', 'data-sid="'.$FIELD_SID.'" name=', $arQuestion["HTML_CODE"]);?>
		<?$arQuestion["HTML_CODE"] = str_replace('left', '', $arQuestion["HTML_CODE"]);?>
		<?$arQuestion["HTML_CODE"] = str_replace('size="0"', '', $arQuestion["HTML_CODE"]);?>
		<?if($arQuestion['STRUCTURE'][0]['FIELD_TYPE'] == 'hidden'):?>
			<?=$arQuestion["HTML_CODE"];?>
		<?else:?>
			<div class="form-control">
				<label><span><?=$arQuestion["CAPTION"]?><?=($arQuestion["REQUIRED"] == "Y" ? '&nbsp;<span class="star">*</span>' : '')?></span></label>
				<?
				if(strpos($arQuestion["HTML_CODE"], "class=") === false)
					$arQuestion["HTML_CODE"] = str_replace('input', 'input class=""', $arQuestion["HTML_CODE"]);

				if(is_array($arResult["FORM_ERRORS"]) && array_key_exists($FIELD_SID, $arResult['FORM_ERRORS']))
					$arQuestion["HTML_CODE"] = str_replace('class="', 'class="error ', $arQuestion["HTML_CODE"]);

				if($arQuestion["REQUIRED"] == "Y")
					$arQuestion["HTML_CODE"] = str_replace('name=', 'required name=', $arQuestion["HTML_CODE"]);

				if($arQuestion["STRUCTURE"][0]["FIELD_TYPE"] == "email")
					$arQuestion["HTML_CODE"] = str_replace('type="text"', 'type="email" placeholder="mail@domen.com"', $arQuestion["HTML_CODE"]);

				if((strpos($arQuestion["HTML_CODE"], "phone") !== false) || (strpos(strToLower($FIELD_SID), "phone") !== false))
					$arQuestion["HTML_CODE"] = str_replace('type="text"', 'type="tel"', $arQuestion["HTML_CODE"]);
				?>
				<?if($FIELD_SID == 'RATING'):?>
					<div class="votes_block nstar big with-text">
						<div class="ratings">
							<div class="inner_rating">
								<?for($i=1;$i<=5;$i++):?>
									<div class="item-rating" data-message="<?=GetMessage('RATING_MESSAGE_'.$i)?>"><?=static::showIconSvg("star", SITE_TEMPLATE_PATH."/images/svg/star.svg");?></div>
								<?endfor;?>
							</div>
						</div>
						<div class="rating_message muted" data-message="<?=GetMessage('RATING_MESSAGE_0')?>"><?=GetMessage('RATING_MESSAGE_0')?></div>
						<?=str_replace('type="text"', 'type="hidden"', $arQuestion["HTML_CODE"])?>
					</div>
				<?else:?>
					<?=$arQuestion["HTML_CODE"]?>
				<?endif;?>
			</div>
		<?endif;?>
		<?
	}

	public static function GetValidFormIDForSite(&$form_id){
		if (!is_numeric($form_id) && !in_array($form_id, array('auth', 'one_click_buy')) && \Bitrix\Main\Loader::includeModule('form')) {
			$rsForm = CForm::GetList($by = 'id', $order = 'asc', array('ACTIVE' => 'Y', 'SID' => $form_id, 'SID_EXACT_MATCH' => 'N', 'SITE' => array(SITE_ID)), $is_filtered);
			if($item = $rsForm->Fetch())
				$form_id = $item['ID'];
		}

		return $form_id;
	}

	public static function CheckTypeCount($totalCount){
		return floatval($totalCount);
	}

	public static function GetTotalCount($arItem, $arParams = array()){
		$totalCount = 0;

		//normalize STORES
		if($arParams['STORES'])
		{
			foreach($arParams['STORES'] as $key => $store)
			{
				if(!$store)
					unset($arParams['STORES'][$key]);
			}
		}

		if(
			$arParams['USE_REGION'] == 'Y' &&
			$arParams['STORES']
		){
			$arSelect = array('ID', 'PRODUCT_AMOUNT');
			$arFilter = array('ID' => $arParams['STORES']);

			if($arItem['OFFERS']){
				$arOffers = array_column($arItem['OFFERS'], 'ID');

				if($arOffers){
					$quantity = 0;

					$rsStore = self::CCatalogStore_GetList(array(), array_merge($arFilter, array('PRODUCT_ID' => $arOffers)), false, false, $arSelect);
					foreach($rsStore as $arStore){
						$quantity += $arStore['PRODUCT_AMOUNT'];
					}

					$totalCount = $quantity;
				}
			}
			elseif(
				isset($arItem['PRODUCT']['TYPE']) &&
				$arItem['PRODUCT']['TYPE'] == 2
			){
				if(!$arItem['SET_ITEMS']){
					$arItem['SET_ITEMS'] = array();

					if($arSets = CCatalogProductSet::getAllSetsByProduct($arItem['ID'], 1)){
						$arSets = reset($arSets);

						foreach($arSets['ITEMS'] as $v){
							$v['ID'] = $v['ITEM_ID'];
							unset($v['ITEM_ID']);
							$arItem['SET_ITEMS'][] = $v;
						}
					}
				}

			    $arProductSet = $arItem['SET_ITEMS'] ? array_column($arItem['SET_ITEMS'], 'ID') : array();

			    if($arProductSet){
					$arSelect[] = 'ELEMENT_ID';
					$quantity = array();

					$rsStore = self::CCatalogStore_GetList(array(), array_merge($arFilter, array('PRODUCT_ID' => $arProductSet)), false, false, $arSelect);
					foreach($rsStore as $arStore){
					    $quantity[$arStore['ELEMENT_ID']] += $arStore['PRODUCT_AMOUNT'];
					}

					if($quantity){
					    foreach($arItem['SET_ITEMS'] as $v) {
							$quantity[$v['ID']] /= $v['QUANTITY'];
							$quantity[$v['ID']] = floor($quantity[$v['ID']]);
					    }
						$totalCount = min($quantity);
					}
			    }
			}
			else{
				$quantity = 0;
				
				$rsStore = self::CCatalogStore_GetList(array(), array_merge($arFilter, array('PRODUCT_ID' => $arItem['ID'])), false, false, $arSelect);
				foreach( $rsStore as $arStore ){
					$quantity += $arStore['PRODUCT_AMOUNT'];
				}

				$totalCount = $quantity;
			}
		}
		else{
			if($arItem['OFFERS']){
				foreach($arItem['OFFERS'] as $arOffer)
					$totalCount += $arOffer['CATALOG_QUANTITY'];
			}
			else
				$totalCount += ($arItem['~CATALOG_QUANTITY'] != $arItem['CATALOG_QUANTITY'] ? $arItem['~CATALOG_QUANTITY'] : $arItem['CATALOG_QUANTITY']);
		}

		foreach(GetModuleEvents(ASPRO_MAX_MODULE_ID, 'OnAsproGetTotalQuantity', true) as $arEvent) // event for manipulation total quantity
			ExecuteModuleEventEx($arEvent, array($arItem, $arParams, &$totalCount));

		return self::CheckTypeCount($totalCount);
	}

	public static function GetQuantityArray(
		$totalCount,
		$arItemIDs = array(),
		$useStoreClick = "N",
		$bShowAjaxItems = false,
		$dopClass = '',
		$siteId = false,
		$userId = false,
		$dataAmount = []
	){
		if (is_array($totalCount)) {
			[
				'totalCount' => $totalCount,
				'arItemIDs' => $arItemIDs,
				'useStoreClick' => $useStoreClick,
				'bShowAjaxItems' => $bShowAjaxItems,
				'dopClass' => $dopClass,
				'siteId' => $siteId,
				'userId' => $userId,
				'dataAmount' => $dataAmount,
			] = $totalCount;
		}

		$siteId = strlen($siteId) ? $siteId : (defined('SITE_ID') ? SITE_ID : false);
		if(!$siteId){
			return;
		}

		$userId = ($userId = intval($userId)) > 0 ? $userId : self::GetUserID();

		static $arQuantityOptions, $arQuantityRights;
		if($arQuantityOptions === NULL){
			$arQuantityOptions = array(
				"USE_WORD_EXPRESSION" => Option::get(self::moduleID, "USE_WORD_EXPRESSION", "Y", $siteId),
				"MAX_AMOUNT" => Option::get(self::moduleID, "MAX_AMOUNT", "10", $siteId),
				"MIN_AMOUNT" => Option::get(self::moduleID, "MIN_AMOUNT", "2", $siteId),
				"EXPRESSION_FOR_MIN" => Option::get(self::moduleID, "EXPRESSION_FOR_MIN", GetMessage("EXPRESSION_FOR_MIN_DEFAULT"), $siteId),
				"EXPRESSION_FOR_MID" => Option::get(self::moduleID, "EXPRESSION_FOR_MID", GetMessage("EXPRESSION_FOR_MID_DEFAULT"), $siteId),
				"EXPRESSION_FOR_MAX" => Option::get(self::moduleID, "EXPRESSION_FOR_MAX", GetMessage("EXPRESSION_FOR_MAX_DEFAULT"), $siteId),
				"EXPRESSION_FOR_EXISTS" => Option::get(self::moduleID, "EXPRESSION_FOR_EXISTS", GetMessage("EXPRESSION_FOR_EXISTS_DEFAULT"), $siteId),
				"EXPRESSION_FOR_NOTEXISTS" => Option::get(self::moduleID, "EXPRESSION_FOR_NOTEXISTS", GetMessage("EXPRESSION_FOR_NOTEXISTS_DEFAULT"), $siteId),
				"SHOW_QUANTITY_FOR_GROUPS" => (($tmp = Option::get(self::moduleID, "SHOW_QUANTITY_FOR_GROUPS", "", $siteId)) ? explode(",", $tmp) : array()),
				"SHOW_QUANTITY_COUNT_FOR_GROUPS" => (($tmp = Option::get(self::moduleID, "SHOW_QUANTITY_COUNT_FOR_GROUPS", "", $siteId)) ? explode(",", $tmp) : array()),
			);

			$arQuantityRights = array(
				"SHOW_QUANTITY" => false,
				"SHOW_QUANTITY_COUNT" => false,
			);

			$res = CUser::GetUserGroupList($userId);
			while ($arGroup = $res->Fetch()){
				if(in_array($arGroup["GROUP_ID"], $arQuantityOptions["SHOW_QUANTITY_FOR_GROUPS"])){
					$arQuantityRights["SHOW_QUANTITY"] = true;
				}
				if(in_array($arGroup["GROUP_ID"], $arQuantityOptions["SHOW_QUANTITY_COUNT_FOR_GROUPS"])){
					$arQuantityRights["SHOW_QUANTITY_COUNT"] = true;
				}
			}
		}

		$indicators = 0;
		$totalAmount = $totalText = $totalHTML = $totalHTMLs = '';

		if($arQuantityRights["SHOW_QUANTITY"]){
			if($totalCount > $arQuantityOptions["MAX_AMOUNT"]){
				$indicators = 3;
				$totalAmount = $arQuantityOptions["EXPRESSION_FOR_MAX"];
			}
			elseif($totalCount < $arQuantityOptions["MIN_AMOUNT"] && $totalCount > 0){
				$indicators = 1;
				$totalAmount = $arQuantityOptions["EXPRESSION_FOR_MIN"];
			}
			else{
				$indicators = 2;
				$totalAmount = $arQuantityOptions["EXPRESSION_FOR_MID"];
			}

			if($totalCount > 0){
				if($arQuantityRights["SHOW_QUANTITY_COUNT"]){
					$totalHTML = '<span class="first'.($indicators >= 1 ? ' r' : '').'"></span><span class="'.($indicators >= 2 ? ' r' : '').'"></span><span class="last'.($indicators >= 3 ? ' r' : '').'"></span>';
				}
				else{
					$totalHTML = '<span class="first r"></span>';
				}
			}
			else{
				$totalHTML = '<span class="null"></span>';
			}

			if($totalCount > 0)
			{
				
				if (
					$dataAmount
					&& is_array($dataAmount)
				) {
					$resultAmount = '';
					
					$bImmediatelyAmount = $dataAmount['IMMEDIATELY'] === 'Y';
					unset($dataAmount['IMMEDIATELY']);
	
					if ($bImmediatelyAmount) {
						if ($arAmount = Aspro\Max\Product\Quantity::getStoresAmount([$dataAmount['ID']], $dataAmount['STORES'])) {
							$resultAmount = $arAmount[$dataAmount['ID']];
						}
					} else {
						$dataAmount = $GLOBALS['APPLICATION']->ConvertCharsetArray($dataAmount, SITE_CHARSET, 'UTF-8');
						$dataAmount = json_encode($dataAmount);
						$dataAmount = htmlspecialcharsbx($dataAmount);
					}
				}

				$storesAmountHtml = ($dataAmount ? ' <span><span class="status-amount--stores dark_link'.($bImmediatelyAmount ? ' status-amount--loaded status-amount--visible' : '').'"'.($bImmediatelyAmount ? '' : ' data-param-amount="'.$dataAmount.'"').'>'.$resultAmount.'</span></span>' : '');

				if($useStoreClick=="Y")
					$totalText = "<span class='store_view dotted'>".$arQuantityOptions["EXPRESSION_FOR_EXISTS"].(!$arQuantityRights["SHOW_QUANTITY_COUNT"] ? $storesAmountHtml : '').'</span>';
				else
					$totalText = $arQuantityOptions["EXPRESSION_FOR_EXISTS"].(!$arQuantityRights["SHOW_QUANTITY_COUNT"] ? $storesAmountHtml : '');
			}
			else
			{
				if($useStoreClick=="Y")
					$totalText = "<span class='store_view dotted'>".$arQuantityOptions["EXPRESSION_FOR_NOTEXISTS"].'</span>';
				else
					$totalText = $arQuantityOptions["EXPRESSION_FOR_NOTEXISTS"];
			}

			if($arQuantityRights["SHOW_QUANTITY_COUNT"] && $totalCount > 0)
			{
				if($arQuantityOptions["USE_WORD_EXPRESSION"] == "Y")
				{
					if(strlen($totalAmount))
					{
						if($useStoreClick=="Y")
							$totalText = "<span class='store_view dotted'>".$totalAmount.$storesAmountHtml.'</span>';
						else
							$totalText = $totalAmount.$storesAmountHtml;
					}
				}
				else
				{
					if($useStoreClick=="Y")
						$totalText .= (strlen($totalText) ? ": ".$totalCount.$storesAmountHtml : "<span class='store_view dotted'>".$totalCount.$storesAmountHtml.'</span>');
					else
						$totalText .= (strlen($totalText) ? ": ".$totalCount : $totalCount).$storesAmountHtml;
				}
			}

			$totalHTMLs ='<div data-click="'.$useStoreClick.'" class="item-stock'.($bShowAjaxItems ? ' js-show-stores js-show-info-block' : '').' '.$dopClass.'" '.($arItemIDs["ID"] ? 'data-id="'.$arItemIDs["ID"].'"' : '').' '.(isset($arItemIDs["STORE_QUANTITY"]) && $arItemIDs["STORE_QUANTITY"] ? "id=".$arItemIDs["STORE_QUANTITY"] : "").'>';
			$totalHTMLs .= '<span class="icon '.($totalCount > 0 ? 'stock' : ' order').'"></span><span class="value font_sxs">'.$totalText.'</span>';
			$totalHTMLs .='</div>';
		}

		$arOptions = array("OPTIONS" => $arQuantityOptions, "RIGHTS" => $arQuantityRights, "TEXT" => $totalText, "HTML" => $totalHTMLs);

		foreach(GetModuleEvents(ASPRO_MAX_MODULE_ID, 'OnAsproGetTotalQuantityBlock', true) as $arEvent) // event for manipulation store quantity block
			ExecuteModuleEventEx($arEvent, array($totalCount, &$arOptions));

		return $arOptions;
	}

	public static function GetAvailiableStore($totalCount = 0, $arItemIDs=array(), $detail=false){
		static $arQuantityOptions;
		if($arQuantityOptions === NULL){
			$arQuantityOptions = array(
				"EXPRESSION_FOR_EXISTS" => Option::get(self::moduleID, "EXPRESSION_FOR_EXISTS", GetMessage("EXPRESSION_FOR_EXISTS_DEFAULT"), SITE_ID),
				"EXPRESSION_FOR_NOTEXISTS" => Option::get(self::moduleID, "EXPRESSION_FOR_NOTEXISTS", GetMessage("EXPRESSION_FOR_NOTEXISTS_DEFAULT"), SITE_ID),
			);
		}
		$totalHTML='<div class="item-stock" '.($arItemIDs["STORE_QUANTITY"] ? "id=".$arItemIDs["STORE_QUANTITY"] : "").'>';
		if($totalCount){
			$totalHTML.='<span class="icon stock"></span><span>'.$arQuantityOptions["EXPRESSION_FOR_EXISTS"];
			if($detail=="Y"){
				$totalHTML.='<span class="store_link"> ('.$totalCount.')</span>';
			}else{
				$totalHTML.=' ('.$totalCount.')';
			}
			$totalHTML.='</span>';
		}else{
			$totalHTML.='<span class="icon order"></span><span>'.$arQuantityOptions["EXPRESSION_FOR_NOTEXISTS"].'</span>';
		}
		$totalHTML.='</div>';

		return array( "OPTIONS" => $arQuantityOptions, "HTML" => $totalHTML );
	}

	public static function GetPropertyViewType($IBLOCK_ID){
		global $DB;
		$IBLOCK_ID = intval($IBLOCK_ID);
		$SECTION_ID=64;
		// $IBLOCK_ID = 15;
        $result = array();
		/*$rs = $DB->Query($s = "
			SELECT
				B.SECTION_PROPERTY,
				BP.ID PROPERTY_ID,
				BSP.SECTION_ID LINK_ID,
				BSP.SMART_FILTER,
				BSP.DISPLAY_TYPE,
				BSP.DISPLAY_EXPANDED,
				BSP.FILTER_HINT,
				BP.SORT,
				BP.PROPERTY_TYPE,
				BP.USER_TYPE
			FROM
				b_iblock B
				INNER JOIN b_iblock_property BP ON BP.IBLOCK_ID = B.ID
				INNER JOIN b_iblock_section_property BSP ON  BSP.PROPERTY_ID = BP.ID
			WHERE
				B.ID = ".$IBLOCK_ID."
			ORDER BY
				BP.SORT ASC, BP.ID ASC
		");*/
		$rs = $DB->Query($s = "
			SELECT
                    B.SECTION_PROPERTY,
                    BP.ID PROPERTY_ID,
                    BSP.SECTION_ID LINK_ID,
                    BSP.SMART_FILTER,
                    BSP.DISPLAY_TYPE,
                    BSP.DISPLAY_EXPANDED,
                    BSP.FILTER_HINT,
                    BP.SORT,
                    BS.LEFT_MARGIN,
                    BS.NAME LINK_TITLE,
                    BP.PROPERTY_TYPE,
                    BP.USER_TYPE
                FROM
                    b_iblock B
                    INNER JOIN b_iblock_property BP ON BP.IBLOCK_ID = B.ID
                    INNER JOIN b_iblock_section M ON M.ID = ".$SECTION_ID."
                    INNER JOIN b_iblock_section BS ON BS.IBLOCK_ID = M.IBLOCK_ID
                        AND M.LEFT_MARGIN >= BS.LEFT_MARGIN
                        AND M.RIGHT_MARGIN <= BS.RIGHT_MARGIN
                    INNER JOIN b_iblock_section_property BSP ON BSP.IBLOCK_ID = BS.IBLOCK_ID AND BSP.SECTION_ID = BS.ID AND BSP.PROPERTY_ID = BP.ID
                WHERE
                    B.ID = ".$IBLOCK_ID."
                ORDER BY
                    BP.SORT ASC, BP.ID ASC, BS.LEFT_MARGIN DESC
		");
		while ($ar = $rs->Fetch()){
			$result[$ar["PROPERTY_ID"]] = array(
				"PROPERTY_ID" => $ar["PROPERTY_ID"],
				"SMART_FILTER" => $ar["SMART_FILTER"],
				"DISPLAY_TYPE" => $ar["DISPLAY_TYPE"],
				"DISPLAY_EXPANDED" => $ar["DISPLAY_EXPANDED"],
				"FILTER_HINT" => $ar["FILTER_HINT"],
				"INHERITED_FROM" => $ar["LINK_ID"],
				"SORT" => $ar["SORT"],
				"PROPERTY_TYPE" => $ar["PROPERTY_TYPE"],
			);
		}
		return $result;
	}

	public static function GetSKUPropsArray(&$arSkuProps, $iblock_id=0, $type_view="list", $hide_title_props="N", $group_iblock_id="N", $arItem = array(), $offerShowPreviewPictureProps = array(), $max_count = false){
		$arSkuTemplate = array();
		$class_title=($hide_title_props=="Y" ? "hide_class" : "show_class");
		$class_title.=' bx_item_section_name';
		if($iblock_id){
			$arPropsSku=CIBlockSectionPropertyLink::GetArray($iblock_id);
			if($arPropsSku){
				foreach ($arSkuProps as $key=>$arProp){
					if($arPropsSku[$arProp["ID"]]){
						$arSkuProps[$key]["DISPLAY_TYPE"]=$arPropsSku[$arProp["ID"]]["DISPLAY_TYPE"];
					}
				}
			}
		}?>

		<?
		$bTextViewProp = (Option::get(self::moduleID, "VIEW_TYPE_HIGHLOAD_PROP", "N", SITE_ID) == "Y");
		$arCurrentOffer = $arItem['OFFERS'][$arItem['OFFERS_SELECTED']];
		$j = 0;
		$arFilter = $arShowValues = array();

		/*get correct values*/
		foreach ($arSkuProps as $key => $arProp){
			$strName = 'PROP_'.$arProp['ID'];
			$arShowValues = self::GetRowValues($arFilter, $strName, $arItem);

			if(!is_array($arShowValues))
				$arShowValues = array();

			if(in_array($arCurrentOffer['TREE'][$strName], $arShowValues))
			{
				$arFilter[$strName] = $arCurrentOffer['TREE'][$strName];
			}
			else
			{
				$arFilter[$strName] = $arShowValues[0];
			}

			$arCanBuyValues = $tmpFilter = array();
			$tmpFilter = $arFilter;
			foreach($arShowValues as $value)
			{
				$tmpFilter[$strName] = $value;
				if(self::GetCanBuy($tmpFilter, $arItem))
				{
					$arCanBuyValues[] = $value;
				}
			}

			$arSkuProps[$key] = self::UpdateRow($arFilter[$strName], $arShowValues, $arCanBuyValues, $arProp, $type_view);
		}
		/**/
		

		if($group_iblock_id=="Y"){
			foreach ($arSkuProps as $iblockId => $skuProps){
				$arSkuTemplate[$iblockId] = array();
				$j = 0;
				foreach ($skuProps as $key=>&$arProp){

					if($arProp['VALUES'])
					{
						foreach($arProp['VALUES'] as $arOneValue)
						{
							if($arOneValue['CLASS'] && $arOneValue['CLASS'] == 'active')
							{
								$arProp['VALUE'] = '<span class="val">'.$arOneValue['NAME'].'</span>';
								break;
							}
						}
					}

					$nameWithDelimeters = $arProp['NAME'];

					$templateRow = '';
					$class_title.= (($arProp["HINT"] && $arProp["SHOW_HINTS"] == "Y") ? ' whint char_name' : '');
					$hint_block = (($arProp["HINT"] && $arProp["SHOW_HINTS"]=="Y") ? '<div class="hint"><span class="icon"><i>?</i></span><div class="tooltip">'.$arProp["HINT"].'</div></div>' : '');
					if(($arProp["DISPLAY_TYPE"]=="P" || $arProp["DISPLAY_TYPE"]=="R" ) && $type_view!= 'block' ){
						$templateRow .= '<div class="bx_item_detail_size" '.$arProp['STYLE'].' id="#ITEM#_prop_'.$arProp['ID'].'_cont" data-display_type="SELECT" data-id="'.$arProp['ID'].'">'.
		'<span class="'.$class_title.'"><span>'.htmlspecialcharsex($arProp['NAME']).$hint_block.'<span class="sku_mdash">&mdash;</span>'.$arProp['VALUE'].'</span></span>'.
		'<div class="bx_size_scroller_container form-control bg"><div class="bx_size"><select id="#ITEM#_prop_'.$arProp['ID'].'_list" class="list_values_wrapper">';
						foreach ($arProp['VALUES'] as $arOneValue){
							// if($arOneValue['ID']>0){
								$arOneValue['NAME'] = htmlspecialcharsbx($arOneValue['NAME']);
								$templateRow .= '<option '.$arOneValue['SELECTED'].' '.$arOneValue['DISABLED'].' data-treevalue="'.$arProp['ID'].'_'.$arOneValue['ID'].'" data-showtype="select" data-onevalue="'.$arOneValue['ID'].'" ';
								if($arProp["DISPLAY_TYPE"]=="R"){
									$templateRow .= 'data-img_src="'.$arOneValue["PICT"]["SRC"].'" ';
								}

								$templateRow .= 'title="'.$nameWithDelimeters.$arOneValue['NAME'].'">';
								$templateRow .= '<span class="cnt">'.$arOneValue['NAME'].'</span>';
								$templateRow .= '</option>';
							// }
						}
						$templateRow .= '</select></div>'.
		'</div></div>';
					}elseif ('TEXT' == $arProp['SHOW_MODE']){
						$templateRow .= '<div class="bx_item_detail_size" '.$arProp['STYLE'].' id="#ITEM#_prop_'.$arProp['ID'].'_cont" data-display_type="LI" data-id="'.$arProp['ID'].'">'.
		'<span class="'.$class_title.'"><span>'.htmlspecialcharsex($arProp['NAME']).$hint_block.'<span class="sku_mdash">&mdash;</span>'.$arProp['VALUE'].'</span></span>'.
		'<div class="bx_size_scroller_container scrollblock scrollblock--ob-auto"><div class="bx_size"><ul id="#ITEM#_prop_'.$arProp['ID'].'_list" class="list_values_wrapper">';
						foreach ($arProp['VALUES'] as $arOneValue){
							// if($arOneValue['ID']>0){

								$arOneValue['NAME'] = htmlspecialcharsbx($arOneValue['NAME']);
								$templateRow .= '<li class="item '.$arOneValue['CLASS'].'" '.$arOneValue['STYLE'].' data-treevalue="'.$arProp['ID'].'_'.$arOneValue['ID'].'" data-showtype="li" data-onevalue="'.$arOneValue['ID'].'" title="'.$nameWithDelimeters.$arOneValue['NAME'].'"><i></i><span class="cnt">'.$arOneValue['NAME'].'</span></li>';
							// }
						}
						$templateRow .= '</ul></div>'.
		'</div></div>';
					}elseif ('PICT' == $arProp['SHOW_MODE']){
						$arCurrentTree = array();
						if($offerShowPreviewPictureProps && is_array($offerShowPreviewPictureProps))
						{
							if(in_array($arProp['CODE'], $offerShowPreviewPictureProps))
							{
								if($arCurrentOffer && $arCurrentOffer['TREE']){
									$arCurrentTree['PROP_'.$arProp['ID']] = $arCurrentOffer['TREE']['PROP_'.$arProp['ID']];
								}
							}
						}

						$isHasPicture = true;
						foreach($arProp['VALUES'] as &$arOneValue)
						{
							$boolOneSearch = false;
							if($arCurrentTree && $arOneValue['ID'] != 0)
							{
								$arRowTree = $arCurrentTree;
								$arRowTree['PROP_'.$arProp['ID']] = $arOneValue['ID'];

								foreach($arItem['OFFERS'] as &$arOffer)
								{
									$boolOneSearch = true;
									foreach($arRowTree as $rkey => $rval)
									{
										if($rval !== $arOffer['TREE'][$rkey])
										{
											$boolOneSearch = false;
											break;
										}
									}
									if($boolOneSearch)
									{
										if($arOffer['PREVIEW_PICTURE_FIELD'] && is_array($arOffer['PREVIEW_PICTURE_FIELD']) && $arOffer['PREVIEW_PICTURE_FIELD']['SRC'])
											$arOneValue['NEW_PICT'] = $arOffer['PREVIEW_PICTURE_FIELD'];
										else
											$boolOneSearch = false;
										break;
									}
								}
								unset($arOffer);
							}

							if(!$boolOneSearch)
							{
								//if($arOneValue['ID']>0){
									if(!isset($arOneValue['PICT']['SRC']) || !$arOneValue['PICT']['SRC'])
									{
										if(!$bTextViewProp)
										{
											$arOneValue['PICT']['SRC'] = SITE_TEMPLATE_PATH.'/images/svg/noimage_product.svg';
											$arOneValue['NO_PHOTO'] = 'Y';
										}
										else
										{
											$isHasPicture = false;
										}
									}
								//}
							}
						}
						unset($arOneValue);

						if($isHasPicture)
						{
							$templateRow .= '<div class="bx_item_detail_scu" '.$arProp['STYLE'].' id="#ITEM#_prop_'.$arProp['ID'].'_cont" data-display_type="LI" data-id="'.$arProp['ID'].'">'.
		'<span class="'.$class_title.'"><span>'.htmlspecialcharsex($arProp['NAME']).$hint_block.'<span class="sku_mdash">&mdash;</span>'.$arProp['VALUE'].'</span></span>'.
		'<div class="bx_scu_scroller_container scrollblock scrollblock--ob-auto"><div class="bx_scu"><ul id="#ITEM#_prop_'.$arProp['ID'].'_list" class="list_values_wrapper">';
						}
						else
						{
							$templateRow .= '<div class="bx_item_detail_size" '.$arProp['STYLE'].' id="#ITEM#_prop_'.$arProp['ID'].'_cont" data-display_type="LI" data-id="'.$arProp['ID'].'">'.
		'<span class="'.$class_title.'">'.htmlspecialcharsex($arProp['NAME']).$hint_block.'<span class="sku_mdash">&mdash;</span>'.$arProp['VALUE'].'</span>'.
		'<div class="bx_size_scroller_container scrollblock scrollblock--ob-auto"><div class="bx_size"><ul id="#ITEM#_prop_'.$arProp['ID'].'_list" class="list_values_wrapper">';
						}
						foreach ($arProp['VALUES'] as $arOneValue)
						{
							//if($arOneValue['ID']>0){
								$arOneValue['NAME'] = htmlspecialcharsbx($arOneValue['NAME']);

								if($isHasPicture && ($arOneValue['NEW_PICT'] || (isset($arOneValue['PICT']['SRC']) && $arOneValue['PICT']['SRC'])))
								{
									$str = '<span class="cnt1"><span class="cnt_item'.($arOneValue['NEW_PICT'] ? ' pp' : '').'" style="background-image:url(\''.($arOneValue['NEW_PICT'] ? $arOneValue['NEW_PICT']['SRC'] : $arOneValue['PICT']['SRC']).'\');" data-obgi="url(\''.$arOneValue['PICT']['SRC'].'\')" title="'.$arProp['NAME'].': '.$arOneValue['NAME'].'"></span></span>';
									if(isset($arOneValue['NO_PHOTO']) && $arOneValue['NO_PHOTO'] == 'Y')
										$str = '<span class="cnt1 nf"><span class="cnt_item" title="'.$arProp['NAME'].': '.$arOneValue['NAME'].'"><span class="bg" style="background-image:url(\''.$arOneValue['PICT']['SRC'].'\');"></span></span></span>';
									$templateRow .= '<li class="item '.$arOneValue['CLASS'].'" '.$arOneValue['STYLE'].' data-treevalue="'.$arProp['ID'].'_'.$arOneValue['ID'].'" data-showtype="li" data-onevalue="'.$arOneValue['ID'].'"><i title="'.$nameWithDelimeters.$arOneValue['NAME'].'"></i>'.$str.'</li>';
								}
								else
								{
									$templateRow .= '<li class="item '.$arOneValue['CLASS'].'" '.$arOneValue['STYLE'].' data-treevalue="'.$arProp['ID'].'_'.$arOneValue['ID'].'" data-showtype="li" data-onevalue="'.$arOneValue['ID'].'" title="'.$nameWithDelimeters.$arOneValue['NAME'].'"><i></i><span class="cnt">'.$arOneValue['NAME'].'</span></li>';
								}
							//}
						}
						$templateRow .= '</ul></div>'.
		'</div></div>';
					}
					$arSkuTemplate[$iblockId][$arProp['CODE']] = $templateRow;
				}
			}
		}else{	

			foreach ($arSkuProps as $key=>&$arProp){
				$templateRow = '';
				$class_title.= (($arProp["HINT"] && $arProp["SHOW_HINTS"] == "Y") ? ' whint char_name' : '');
				$hint_block = (($arProp["HINT"] && $arProp["SHOW_HINTS"]=="Y") ? '<span class="hint"><span class="icon"><i>?</i></span><span class="tooltip">'.$arProp["HINT"].'</span></span>' : '');
				$show_more_link = false;
				$count_more = 0;
				$count_visible = 0;

				$nameWithDelimeters = $arProp['NAME'];
				if (strpos($arProp['NAME'], ':') === false) {
					$nameWithDelimeters .= ': ';
				}

				if($arProp['VALUES'])
				{
					foreach($arProp['VALUES'] as $propKey => $arOneValue)
					{
						$arProp['NAME'] = htmlspecialcharsex($arProp['NAME']);

						if($arOneValue['CLASS'] && strpos($arOneValue['CLASS'], 'active') !== false)
						{
							$arProp['VALUE'] = '<span class="val">'.$arOneValue['NAME'].'</span>';

							if(!$max_count)
								break;
						}

						if($max_count && $count_visible >= $max_count && ( !$arOneValue['CLASS'] || strpos($arOneValue['CLASS'], 'active') === false ) ) {
							$arProp['VALUES'][$propKey]['CLASS'] .= ' scu_prop_more';
							$show_more_link = true;
							$count_more++;
						}

						if( !$arOneValue['CLASS'] || strpos($arOneValue['CLASS'], 'missing') === false ) {
							$count_visible++;
						}
					}
				}

				if($show_more_link) {
					$show_more_link_html = '';
					$titles = array(
						Loc::getMessage('SHOW_MORE_SCU_1'),
						Loc::getMessage('SHOW_MORE_SCU_2'),
						Loc::getMessage('SHOW_MORE_SCU_3'),
					);
					$more_scu_mess = Loc::getMessage('SHOW_MORE_SCU_MAIN', array( '#COUNT#' => \Aspro\Functions\CAsproMax::declOfNum($count_more, $titles) ));
					$svgHTML =
					'<svg xmlns="http://www.w3.org/2000/svg" width="4" height="7" viewBox="0 0 4 7" fill="none">'
						.'<path d="M0.5 0.5L3.5 3.5L0.5 6.5" stroke="#333" stroke-linecap="round" stroke-linejoin="round"/>'
					.'</svg>';
					$show_more_link_html = '<div class="show_more_link"><a class="font_sxs colored_theme_n_hover_bg-svg-stroke" href="'.$arItem['DETAIL_PAGE_URL'].'">'.$more_scu_mess.$svgHTML.'</a></div>';
				}

				if(($arProp["DISPLAY_TYPE"]=="P" || $arProp["DISPLAY_TYPE"]=="R" ) && $type_view!= 'block' ){
					$templateRow .= '<div class="bx_item_detail_size" '.$arProp['STYLE'].' id="#ITEM#_prop_'.$arProp['ID'].'_cont" data-display_type="SELECT" data-id="'.$arProp['ID'].'">'.
	'<span class="'.$class_title.'"><span>'.($arProp['TITLE'] ? $arProp['TITLE'] : $arProp['NAME']).$hint_block.'<span class="sku_mdash">&mdash;</span>'.$arProp['VALUE'].'</span></span>'.
	'<div class="bx_size_scroller_container form-control bg"><div class="bx_size"><select id="#ITEM#_prop_'.$arProp['ID'].'_list" class="list_values_wrapper">';
					foreach ($arProp['VALUES'] as $arOneValue){
						// if($arOneValue['ID']>0){
							$arOneValue['NAME'] = htmlspecialcharsbx($arOneValue['NAME']);
							$templateRow .= '<option '.(strpos($arOneValue['CLASS'], "missing") !== false ? 'data-missing="missing"' : "").' '.$arOneValue['SELECTED'].' '.$arOneValue['DISABLED'].' data-treevalue="'.$arProp['ID'].'_'.$arOneValue['ID'].'" data-showtype="select" data-onevalue="'.$arOneValue['ID'].'" ';
							if($arProp["DISPLAY_TYPE"]=="R"){
								$templateRow .= 'data-img_src="'.$arOneValue["PICT"]["SRC"].'" ';
							}

							$templateRow .= 'title="'.$nameWithDelimeters.$arOneValue['NAME'].'">';
							$templateRow .= '<span class="cnt">'.$arOneValue['NAME'].'</span>';
							$templateRow .= '</option>';
						// }
					}
					$templateRow .= '</select></div>'.
	'</div></div>';
				}elseif ('TEXT' == $arProp['SHOW_MODE']){
					$templateRow .= '<div class="bx_item_detail_size" '.$arProp['STYLE'].' id="#ITEM#_prop_'.$arProp['ID'].'_cont" data-display_type="LI" data-id="'.$arProp['ID'].'">'.
	'<span class="'.$class_title.'"><span>'.($arProp['TITLE'] ? $arProp['TITLE'] : $arProp['NAME']).$hint_block.'<span class="sku_mdash">&mdash;</span>'.$arProp['VALUE'].'</span></span>'.
	'<div class="bx_size_scroller_container scrollblock scrollblock--ob-auto"><div class="bx_size"><ul id="#ITEM#_prop_'.$arProp['ID'].'_list" class="list_values_wrapper" '.($max_count ? 'data-max-count="'.$max_count.'"' : '').'>';
					foreach ($arProp['VALUES'] as $arOneValue){
						$arOneValue['NAME'] = htmlspecialcharsbx($arOneValue['NAME']);

						$templateRow .= '<li class="item '.$arOneValue['CLASS'].'" '.$arOneValue['STYLE'].' data-treevalue="'.$arProp['ID'].'_'.$arOneValue['ID'].'" data-showtype="li" data-onevalue="'.$arOneValue['ID'].'" title="'.$nameWithDelimeters.$arOneValue['NAME'].'"><i></i><span class="cnt">'.$arOneValue['NAME'].'</span></li>';
					}
					$templateRow .= '</ul></div>'.
	'</div></div>';
					if($show_more_link) {
						$templateRow .= $show_more_link_html;
					}
				}elseif ('PICT' == $arProp['SHOW_MODE']){

					$arCurrentTree = array();
					$showPreviewPictureProp = false;
					if($offerShowPreviewPictureProps && is_array($offerShowPreviewPictureProps))
					{
						if(in_array($arProp['CODE'], $offerShowPreviewPictureProps))
						{
							if($arCurrentOffer && $arCurrentOffer['TREE']){
								$arCurrentTree['PROP_'.$arProp['ID']] = $arCurrentOffer['TREE']['PROP_'.$arProp['ID']];
								
							}
							$showPreviewPictureProp = true;
						}
					}

					$isHasPicture = true;
					foreach($arProp['VALUES'] as &$arOneValue)
					{
						$boolOneSearch = false;
						if($arCurrentTree && $arOneValue['ID'] != 0)
						{
							$arRowTree = $arCurrentTree;
							$arRowTree['PROP_'.$arProp['ID']] = $arOneValue['ID'];

							foreach($arItem['OFFERS'] as &$arOffer)
							{
								$boolOneSearch = true;
								foreach($arRowTree as $rkey => $rval)
								{
									if($rval !== $arOffer['TREE'][$rkey])
									{
										$boolOneSearch = false;
										break;
									}
								}
								if($boolOneSearch)
								{
									if($arOffer['PREVIEW_PICTURE_FIELD'] && is_array($arOffer['PREVIEW_PICTURE_FIELD']) && $arOffer['PREVIEW_PICTURE_FIELD']['SRC'])
										$arOneValue['NEW_PICT'] = $arOffer['PREVIEW_PICTURE_FIELD'];
									else
										$boolOneSearch = false;
									break;
								}
							}
							unset($arOffer);
						}

						if(!$boolOneSearch)
						{
							//if($arOneValue['ID']>0){
								if(!isset($arOneValue['PICT']['SRC']) || !$arOneValue['PICT']['SRC'])
								{
									if(!$bTextViewProp || $showPreviewPictureProp)
									{
										$arOneValue['PICT']['SRC'] = SITE_TEMPLATE_PATH.'/images/svg/noimage_product.svg';
										$arOneValue['NO_PHOTO'] = 'Y';
									}
									else
									{
										$isHasPicture = false;
									}
								}
							//}
						}

						foreach($arItem['OFFERS'] as &$arOffer)
						{
							if($arRowTree['PROP_'.$arProp['ID']] === $arOffer['TREE']['PROP_'.$arProp['ID']] && !$boolOneSearch)
							{
								if($arOffer['PREVIEW_PICTURE_FIELD'] && is_array($arOffer['PREVIEW_PICTURE_FIELD']) && $arOffer['PREVIEW_PICTURE_FIELD']['SRC'])
									$arOneValue['NEW_PICT'] = $arOffer['PREVIEW_PICTURE_FIELD'];
								break;
							}
						}
					}
					unset($arOneValue);
					
					if($isHasPicture)
					{
						$templateRow .= '<div class="bx_item_detail_scu" '.$arProp['STYLE'].' id="#ITEM#_prop_'.$arProp['ID'].'_cont" data-display_type="LI" data-id="'.$arProp['ID'].'">'.
	'<span class="'.$class_title.'"><span>'.($arProp['TITLE'] ? $arProp['TITLE'] : $arProp['NAME']).$hint_block.'<span class="sku_mdash">&mdash;</span>'.$arProp['VALUE'].'</span></span>'.
	'<div class="bx_scu_scroller_container scrollblock scrollblock--ob-auto"><div class="bx_scu"><ul id="#ITEM#_prop_'.$arProp['ID'].'_list" class="list_values_wrapper" '.($max_count ? 'data-max-count="'.$max_count.'"' : '').'>';
					}
					else
					{
						$templateRow .= '<div class="bx_item_detail_size" '.$arProp['STYLE'].' id="#ITEM#_prop_'.$arProp['ID'].'_cont" data-display_type="LI" data-id="'.$arProp['ID'].'">'.
	'<span class="'.$class_title.'">'.htmlspecialcharsex($arProp['NAME']).$hint_block.'<span class="sku_mdash">&mdash;</span>'.$arProp['VALUE'].'</span>'.
	'<div class="bx_size_scroller_container scrollblock scrollblock--ob-auto"><div class="bx_size"><ul id="#ITEM#_prop_'.$arProp['ID'].'_list" class="list_values_wrapper">';

					}
					foreach ($arProp['VALUES'] as $arOneValue)
					{
						//if($arOneValue['ID']>0){

							$arOneValue['NAME'] = htmlspecialcharsbx($arOneValue['NAME']);
							if($isHasPicture && ($arOneValue['NEW_PICT'] || (isset($arOneValue['PICT']['SRC']) && $arOneValue['PICT']['SRC'])))
							{
								$str = '<span class="cnt1"><span class="cnt_item'.($arOneValue['NEW_PICT'] ? ' pp' : '').'" style="background-image:url(\''.($arOneValue['NEW_PICT'] ? $arOneValue['NEW_PICT']['SRC'] : $arOneValue['PICT']['SRC']).'\');" data-obgi="url(\''.$arOneValue['PICT']['SRC'].'\')" title="'.$nameWithDelimeters.$arOneValue['NAME'].'"></span></span>';
								if(isset($arOneValue['NO_PHOTO']) && $arOneValue['NO_PHOTO'] == 'Y')
									$str = '<span class="cnt1 nf"><span class="cnt_item" title="'.$nameWithDelimeters.$arOneValue['NAME'].'"><span class="bg no-image" style="background-image:url(\''.$arOneValue['PICT']['SRC'].'\');"></span></span></span>';
								$templateRow .= '<li class="item '.$arOneValue['CLASS'].'" '.$arOneValue['STYLE'].' data-treevalue="'.$arProp['ID'].'_'.$arOneValue['ID'].'" data-showtype="li" data-onevalue="'.$arOneValue['ID'].'"><i title="'.$nameWithDelimeters.$arOneValue['NAME'].'"></i>'.$str.'</li>';
							}
							else
							{
								$templateRow .= '<li class="item '.$arOneValue['CLASS'].'" '.$arOneValue['STYLE'].' data-treevalue="'.$arProp['ID'].'_'.$arOneValue['ID'].'" data-showtype="li" data-onevalue="'.$arOneValue['ID'].'" title="'.$nameWithDelimeters.$arOneValue['NAME'].'"><i></i><span class="cnt">'.$arOneValue['NAME'].'</span></li>';
							}
						//}
					}
					$templateRow .= '</ul></div>'.
	'</div></div>';

					if($show_more_link) {
						$templateRow .= $show_more_link_html;
					}
				}

				$arSkuTemplate[$arProp['CODE']] = $templateRow;
			}
		}
		unset($templateRow, $arProp);
		return $arSkuTemplate;
	}

	public static function UpdateRow($arFilter, $arShowValues, $arCanBuyValues, $arProp, $type_view){
		$isCurrent = false;
		$showI = 0;

		if($arProp['VALUES']){
			foreach($arProp['VALUES'] as $key => $arValue)
			{
				$value = $arValue['ID'];
				// $isCurrent = ($value === $arFilter && $value != 0);
				$isCurrent = ($value === $arFilter);
				$selectMode = (($arProp["DISPLAY_TYPE"] == "P" || $arProp["DISPLAY_TYPE"] == "R" ) && $type_view != 'block' );

				if(in_array($value, $arCanBuyValues))
				{
					$arProp['VALUES'][$key]['CLASS'] = ($isCurrent ? 'active' : '');
				}
				else
				{
					$arProp['VALUES'][$key]['CLASS'] = ($isCurrent ? 'active missing' : 'missing');
				}
				if($selectMode)
				{
					$arProp['VALUES'][$key]['DISABLED'] = 'disabled';
					$arProp['VALUES'][$key]['SELECTED'] = ($isCurrent ? 'selected' : '');
				}
				else
				{
					$arProp['VALUES'][$key]['STYLE'] = 'style="display: none"';
				}

				if(in_array($value, $arShowValues))
				{
					if($selectMode)
					{
						$arProp['VALUES'][$key]['DISABLED'] = '';
					}
					else
					{
						$arProp['VALUES'][$key]['STYLE'] = '';
					}

					if($value != 0)
						++$showI;
				}
			}
			if(!$showI)
				$arProp['STYLE'] = 'style="display: none"';
			else
				$arProp['STYLE'] = 'style=""';
		}

		return $arProp;
	}

	public static function GetRowValues($arFilter, $index, $arItem){
		$i = 0;
		$arValues = array();
		$boolSearch = false;
		$boolOneSearch = true;

		if(!$arFilter)
		{
			if($arItem['OFFERS']){
				foreach($arItem['OFFERS'] as $arOffer)
				{
					if(!in_array($arOffer['TREE'][$index], $arValues))
					{
						$arValues[] = $arOffer['TREE'][$index];
					}
				}
			}
			$boolSearch = true;
		}
		else
		{
			if($arItem['OFFERS']){
				foreach($arItem['OFFERS'] as $arOffer)
				{
					$boolOneSearch = true;
					foreach($arFilter as $propName => $filter)
					{
						if ($filter !== $arOffer['TREE'][$propName])
						{
							$boolOneSearch = false;
							break;
						}
					}
					if ($boolOneSearch)
					{
						if(!in_array($arOffer['TREE'][$index], $arValues))
						{
							$arValues[] = $arOffer['TREE'][$index];
						}
						$boolSearch = true;
					}
				}
			}
		}
		return ($boolSearch ? $arValues : false);
	}

	public static function GetCanBuy($arFilter, $arItem){
		$i = 0;
		$boolSearch = false;
		$boolOneSearch = true;

		foreach($arItem['OFFERS'] as $arOffer)
		{
			$boolOneSearch = true;
			foreach($arFilter as $propName => $filter)
			{
				if ($filter !== $arOffer['TREE'][$propName])
				{
					$boolOneSearch = false;
					break;
				}
			}
			if($boolOneSearch)
			{
				if($arOffer['CAN_BUY'])
				{
					$boolSearch = true;
					break;
				}
			}
		}
		return $boolSearch;
	}

	public static function GetItemsIDs($arItem, $detail="N"){
		$arAllIDs=array();
		$arAllIDs["strMainID"] = $arItem['strMainID'];
		$arAllIDs["strObName"] = 'ob'.preg_replace("/[^a-zA-Z0-9_]/", "x", $arAllIDs["strMainID"]);

		if($detail=="Y"){
			$arAllIDs["ALL_ITEM_IDS"] = array(
				'ID' => $arAllIDs["strMainID"],
				'PICT' => $arAllIDs["strMainID"].'_pict',
				'DISCOUNT_PICT_ID' => $arAllIDs["strMainID"].'_dsc_pict',
				'STICKER_ID' => $arAllIDs["strMainID"].'_sticker',
				'BIG_SLIDER_ID' => $arAllIDs["strMainID"].'_big_slider',
				'BIG_IMG_CONT_ID' => $arAllIDs["strMainID"].'_bigimg_cont',
				'SLIDER_CONT_ID' => $arAllIDs["strMainID"].'_slider_cont',
				'SLIDER_LIST' => $arAllIDs["strMainID"].'_slider_list',
				'SLIDER_LEFT' => $arAllIDs["strMainID"].'_slider_left',
				'SLIDER_RIGHT' => $arAllIDs["strMainID"].'_slider_right',
				'OLD_PRICE' => $arAllIDs["strMainID"].'_old_price',
				'PRICE' => $arAllIDs["strMainID"].'_price',
				'DISCOUNT_PRICE' => $arAllIDs["strMainID"].'_price_discount',
				'SLIDER_CONT_OF_ID' => $arAllIDs["strMainID"].'_slider_cont_',
				'SLIDER_LIST_OF_ID' => $arAllIDs["strMainID"].'_slider_list_',
				'SLIDER_LEFT_OF_ID' => $arAllIDs["strMainID"].'_slider_left_',
				'SLIDER_RIGHT_OF_ID' => $arAllIDs["strMainID"].'_slider_right_',
				'SLIDER_CONT_OFM_ID' => $arAllIDs["strMainID"].'_sliderm_cont_',
				'SLIDER_LIST_OFM_ID' => $arAllIDs["strMainID"].'_sliderm_list_',
				'SLIDER_LEFT_OFM_ID' => $arAllIDs["strMainID"].'_sliderm_left_',
				'SLIDER_RIGHT_OFM_ID' => $arAllIDs["strMainID"].'_sliderm_right_',
				'QUANTITY' => $arAllIDs["strMainID"].'_quantity',
				'QUANTITY_DOWN' => $arAllIDs["strMainID"].'_quant_down',
				'QUANTITY_UP' => $arAllIDs["strMainID"].'_quant_up',
				'QUANTITY_MEASURE' => $arAllIDs["strMainID"].'_quant_measure',
				'QUANTITY_LIMIT' => $arAllIDs["strMainID"].'_quant_limit',
				'BASIS_PRICE' => $arAllIDs["strMainID"].'_basis_price',
				'BUY_LINK' => $arAllIDs["strMainID"].'_buy_link',
				'BASKET_LINK' => $arAllIDs["strMainID"].'_basket_link',
				'ADD_BASKET_LINK' => $arAllIDs["strMainID"].'_add_basket_link',
				'BASKET_ACTIONS' => $arAllIDs["strMainID"].'_basket_actions',
				'NOT_AVAILABLE_MESS' => $arAllIDs["strMainID"].'_not_avail',
				'COMPARE_LINK' => $arAllIDs["strMainID"].'_compare_link',
				'PROP' => $arAllIDs["strMainID"].'_prop_',
				'PROP_DIV' => $arAllIDs["strMainID"].'_skudiv',
				'DISPLAY_PROP_DIV' => $arAllIDs["strMainID"].'_sku_prop',
				'DISPLAY_PROP_ARTICLE_DIV' => $arAllIDs["strMainID"].'_sku_article_prop',
				'OFFER_GROUP' => $arAllIDs["strMainID"].'_set_group_',
				'BASKET_PROP_DIV' => $arAllIDs["strMainID"].'_basket_prop',
				'SUBSCRIBE_DIV' => $arAllIDs["strMainID"].'_subscribe_div',
				'SUBSCRIBED_DIV' => $arAllIDs["strMainID"].'_subscribed_div',
				'STORE_QUANTITY' => $arAllIDs["strMainID"].'_store_quantity',
			);
		}else{
			$arAllIDs["ALL_ITEM_IDS"] = array(
				'ID' => $arAllIDs["strMainID"],
				'PICT' => $arAllIDs["strMainID"].'_pict',
				'SECOND_PICT' => $arAllIDs["strMainID"].'_secondpict',
				'STICKER_ID' => $arAllIDs["strMainID"].'_sticker',
				'SECOND_STICKER_ID' => $arAllIDs["strMainID"].'_secondsticker',
				'QUANTITY' => $arAllIDs["strMainID"].'_quantity',
				'QUANTITY_DOWN' => $arAllIDs["strMainID"].'_quant_down',
				'QUANTITY_UP' => $arAllIDs["strMainID"].'_quant_up',
				'QUANTITY_MEASURE' => $arAllIDs["strMainID"].'_quant_measure',
				'BUY_LINK' => $arAllIDs["strMainID"].'_buy_link',
				'BASKET_LINK' => $arAllIDs["strMainID"].'_basket_link',
				'BASKET_ACTIONS' => $arAllIDs["strMainID"].'_basket_actions',
				'NOT_AVAILABLE_MESS' => $arAllIDs["strMainID"].'_not_avail',
				'SUBSCRIBE_LINK' => $arAllIDs["strMainID"].'_subscribe',
				'COMPARE_LINK' => $arAllIDs["strMainID"].'_compare_link',
				'STORE_QUANTITY' => $arAllIDs["strMainID"].'_store_quantity',
				'PRICE' => $arAllIDs["strMainID"].'_price',
				'PRICE_OLD' => $arAllIDs["strMainID"].'_price_old',
				'DSC_PERC' => $arAllIDs["strMainID"].'_dsc_perc',
				'SECOND_DSC_PERC' => $arAllIDs["strMainID"].'_second_dsc_perc',
				'PROP_DIV' => $arAllIDs["strMainID"].'_sku_tree',
				'PROP' => $arAllIDs["strMainID"].'_prop_',
				'DISPLAY_PROP_DIV' => $arAllIDs["strMainID"].'_sku_prop',
				'BASKET_PROP_DIV' => $arAllIDs["strMainID"].'_basket_prop',
				'SUBSCRIBE_DIV' => $arAllIDs["strMainID"].'subscribe_div',
				'SUBSCRIBED_DIV' => $arAllIDs["strMainID"].'subscribed_div',
			);
		}

		$arAllIDs["TITLE_ITEM"] = (
			isset($arItem['IPROPERTY_VALUES']['ELEMENT_PAGE_TITLE'])&& $arItem['IPROPERTY_VALUES']['ELEMENT_PAGE_TITLE'] != ''
			? $arItem['IPROPERTY_VALUES']['ELEMENT_PAGE_TITLE']
			: $arItem['NAME']
		);
		return $arAllIDs;
	}

	public static function GetSKUJSParams($arResult, $arParams, $arItem, $detail="N", $group_iblock_id="N"){
		$arSkuProps = array();

		if($group_iblock_id=="Y"){
			$arResult['SKU_PROPS']=reset($arResult['SKU_PROPS']);
		}

		foreach ($arResult['SKU_PROPS'] as $arOneProp){
			if (!isset($arItem['OFFERS_PROP'][$arOneProp['CODE']]))
				continue;
			if($detail == "Y")
				$display_type = ((($arOneProp['DISPLAY_TYPE'] == "P" || $arOneProp['DISPLAY_TYPE'] == "R") && $arParams["DISPLAY_TYPE"] != 'block' ) ? "SELECT" : "LI" );
			else
				$display_type = ((($arItem['OFFERS_PROPS_JS'][$arOneProp['CODE']]['DISPLAY_TYPE'] == "P" || $arItem['OFFERS_PROPS_JS'][$arOneProp['CODE']]['DISPLAY_TYPE'] == "R") && $arParams["DISPLAY_TYPE"] != 'block' ) ? "SELECT" : "LI" );
			$arSkuProps[] = array(
				'ID' => $arOneProp['ID'],
				'SHOW_MODE' => $arOneProp['SHOW_MODE'],
				'CODE' => $arOneProp['CODE'],
				'VALUES_COUNT' => $arOneProp['VALUES_COUNT'],
				'DISPLAY_TYPE' => $display_type,
			);
		}

		foreach ($arItem['JS_OFFERS'] as &$arOneJs){
			if (0 < $arOneJs['PRICE']['DISCOUNT_DIFF_PERCENT'])
			{
				$arOneJs['PRICE']['DISCOUNT_DIFF_PERCENT'] = '-'.$arOneJs['PRICE']['DISCOUNT_DIFF_PERCENT'].'%';
				$arOneJs['BASIS_PRICE']['DISCOUNT_DIFF_PERCENT'] = '-'.$arOneJs['BASIS_PRICE']['DISCOUNT_DIFF_PERCENT'].'%';
			}
		}
		unset($arOneJs);
		if ($arItem['OFFERS_PROPS_DISPLAY']){
			foreach ($arItem['JS_OFFERS'] as $keyOffer => $arJSOffer){
				$strProps = '';
				$arArticle=array();
				if (!empty($arJSOffer['DISPLAY_PROPERTIES'])){
					foreach ($arJSOffer['DISPLAY_PROPERTIES'] as $arOneProp){
						if($arOneProp['CODE']=='ARTICLE'){
							$arArticle=$arOneProp;
							continue;
						}
						$strProps .= '<tr itemprop="additionalProperty" itemscope itemtype="http://schema.org/PropertyValue"><td><span itemprop="name">'.$arOneProp['NAME'].'</span></td><td><span itemprop="value">'.(
							is_array($arOneProp['VALUE'])
							? implode(' / ', $arOneProp['VALUE'])
							: $arOneProp['VALUE']
						).'</span></td></tr>';

					}
				}
				if($arArticle){
					$strArticle = '';
					$strArticle .= $arArticle['NAME'].': '.(
							is_array($arArticle['VALUE'])
							? implode(' / ', $arArticle['VALUE'])
							: $arArticle['VALUE']
						);
					$arItem['JS_OFFERS'][$keyOffer]['ARTICLE'] = $strArticle;
				}

				$arItem['JS_OFFERS'][$keyOffer]['DISPLAY_PROPERTIES'] = $strProps;

			}
		}
		if ($arItem['SHOW_OFFERS_PROPS']){
			foreach ($arItem['JS_OFFERS'] as $keyOffer => $arJSOffer){
				$strProps = '';
				if (!empty($arJSOffer['DISPLAY_PROPERTIES'])){
					foreach ($arJSOffer['DISPLAY_PROPERTIES'] as $arOneProp){
						if($arOneProp['VALUE']){
							$arOneProp['VALUE_FORMAT']='<span class="block_title" itemprop="name">'.$arOneProp['NAME'].': </span><span class="value" itemprop="value">'.$arOneProp['VALUE'].'</span>';
							if($arOneProp['CODE']!='ARTICLE'){
								$strProps .='<tr itemprop="additionalProperty" itemscope itemtype="http://schema.org/PropertyValue"><td class="char_name"><div class="props_item"><span itemprop="name">'.$arOneProp['NAME'].'</span></div></td><td class="char_value"><span itemprop="value">'.$arOneProp['VALUE'].'</span></td></tr>';
							}
						}
						$arItem['JS_OFFERS'][$keyOffer]['DISPLAY_PROPERTIES_CODE'][$arOneProp["CODE"]] = $arOneProp;
					}
				}
				$arItem['JS_OFFERS'][$keyOffer]['TABLE_PROP']=$strProps;
			}
			foreach ($arItem['JS_OFFERS'] as $keyOffer => $arJSOffer){
				if (!empty($arJSOffer['DISPLAY_PROPERTIES'])){
					foreach ($arJSOffer['DISPLAY_PROPERTIES'] as $keyProp => $arOneProp){
						if($arOneProp['VALUE']){
							if($arOneProp['CODE']=='ARTICLE')
								unset($arItem['JS_OFFERS'][$keyOffer]['DISPLAY_PROPERTIES'][$keyProp]);
						}
					}
				}
			}
		}

		$arItemIDs=self::GetItemsIDs($arItem);
		if($detail=="Y"){
			$arJSParams = array(
				'CONFIG' => array(
					'USE_CATALOG' => $arResult['CATALOG'],
					'SHOW_QUANTITY' => $arParams['USE_PRODUCT_QUANTITY'],
					'SHOW_PRICE' => true,
					'SHOW_DISCOUNT_PERCENT' => ($arParams['SHOW_DISCOUNT_PERCENT'] == 'Y'),
					'SHOW_OLD_PRICE' => ($arParams['SHOW_OLD_PRICE'] == 'Y'),
					'DISPLAY_COMPARE' => $arParams['DISPLAY_COMPARE'],
					'SHOW_SKU_PROPS' => $arItem['SHOW_OFFERS_PROPS'],
					'OFFER_GROUP' => $arItem['OFFER_GROUP'],
					'MAIN_PICTURE_MODE' => $arParams['DETAIL_PICTURE_MODE'],
					'SHOW_BASIS_PRICE' => ($arParams['SHOW_BASIS_PRICE'] == 'Y'),
					'ADD_TO_BASKET_ACTION' => $arParams['ADD_TO_BASKET_ACTION'],
					'SHOW_CLOSE_POPUP' => ($arParams['SHOW_CLOSE_POPUP'] == 'Y')
				),
				'SHOW_UNABLE_SKU_PROPS' => $arParams['SHOW_UNABLE_SKU_PROPS'],
				'PRODUCT_TYPE' => $arResult['CATALOG_TYPE'],
				'VISUAL' => array(
					'ID' => $arItemIDs["ALL_ITEM_IDS"]['ID'],
				),
				'DEFAULT_COUNT' => $arParams['DEFAULT_COUNT'],
				'DEFAULT_PICTURE' => array(
					'PREVIEW_PICTURE' => $arResult['DEFAULT_PICTURE'],
					'DETAIL_PICTURE' => $arResult['DEFAULT_PICTURE']
				),
				'STORE_QUANTITY' => $arItemIDs["ALL_ITEM_IDS"]['STORE_QUANTITY'],
				'PRODUCT' => array(
					'ID' => $arResult['ID'],
					'NAME' => $arResult['~NAME'],
				),
				'BASKET' => array(
					'QUANTITY' => $arParams['PRODUCT_QUANTITY_VARIABLE'],
					'BASKET_URL' => $arParams['BASKET_URL'],
					'SKU_PROPS' => $arItem['OFFERS_PROP_CODES'],
					'ADD_URL_TEMPLATE' => $arResult['~ADD_URL_TEMPLATE'],
					'BUY_URL_TEMPLATE' => $arResult['~BUY_URL_TEMPLATE']
				),
				'OFFERS' => $arItem['JS_OFFERS'],
				'OFFER_SELECTED' => $arItem['OFFERS_SELECTED'],
				'SKU_DETAIL_ID' => $arParams['SKU_DETAIL_ID'],
				'TREE_PROPS' => $arSkuProps
			);
		}else{
			$arJSParams = array(
				'SHOW_UNABLE_SKU_PROPS' => $arParams['SHOW_UNABLE_SKU_PROPS'],
				'PRODUCT_TYPE' => $arItem['CATALOG_TYPE'],
				'SHOW_QUANTITY' => ($arParams['USE_PRODUCT_QUANTITY'] == 'Y'),
				'DEFAULT_COUNT' => $arParams['DEFAULT_COUNT'],
				'SHOW_ADD_BASKET_BTN' => false,
				'SHOW_BUY_BTN' => true,
				'SHOW_ABSENT' => true,
				'SHOW_SKU_PROPS' => $arItem['OFFERS_PROPS_DISPLAY'],
				'SECOND_PICT' => $arItem['SECOND_PICT'],
				'SHOW_OLD_PRICE' => ('Y' == $arParams['SHOW_OLD_PRICE']),
				'SHOW_DISCOUNT_PERCENT' => ('Y' == $arParams['SHOW_DISCOUNT_PERCENT']),
				'ADD_TO_BASKET_ACTION' => $arParams['ADD_TO_BASKET_ACTION'],
				'SHOW_CLOSE_POPUP' => ($arParams['SHOW_CLOSE_POPUP'] == 'Y'),
				'DISPLAY_COMPARE' => $arParams['DISPLAY_COMPARE'],
				'BASKET_URL' => $arParams['BASKET_URL'],
				'DEFAULT_PICTURE' => array(
					'PICTURE' => $arItem['PRODUCT_PREVIEW'],
					'PICTURE_SECOND' => $arItem['PRODUCT_PREVIEW_SECOND']
				),
				'VISUAL' => array(
					'ID' => $arItemIDs["ALL_ITEM_IDS"]['ID'],
					'PICT_ID' => $arItemIDs["ALL_ITEM_IDS"]['PICT'],
					'SECOND_PICT_ID' => $arItemIDs["ALL_ITEM_IDS"]['SECOND_PICT'],
					'QUANTITY_ID' => $arItemIDs["ALL_ITEM_IDS"]['QUANTITY'],
					'QUANTITY_UP_ID' => $arItemIDs["ALL_ITEM_IDS"]['QUANTITY_UP'],
					'QUANTITY_DOWN_ID' => $arItemIDs["ALL_ITEM_IDS"]['QUANTITY_DOWN'],
					'QUANTITY_MEASURE' => $arItemIDs["ALL_ITEM_IDS"]['QUANTITY_MEASURE'],
					'STORE_QUANTITY' => $arItemIDs["ALL_ITEM_IDS"]['STORE_QUANTITY'],
					'PRICE_ID' => $arItemIDs["ALL_ITEM_IDS"]['PRICE'],
					'PRICE_OLD_ID' => $arItemIDs["ALL_ITEM_IDS"]['PRICE_OLD'],
					'TREE_ID' => $arItemIDs["ALL_ITEM_IDS"]['PROP_DIV'],
					'TREE_ITEM_ID' => $arItemIDs["ALL_ITEM_IDS"]['PROP'],
					'BUY_ID' => $arItemIDs["ALL_ITEM_IDS"]['BUY_LINK'],
					'BASKET_LINK' => $arItemIDs["ALL_ITEM_IDS"]['BASKET_LINK'],
					'ADD_BASKET_ID' => $arItemIDs["ALL_ITEM_IDS"]['ADD_BASKET_ID'],
					'DSC_PERC' => $arItemIDs["ALL_ITEM_IDS"]['DSC_PERC'],
					'SECOND_DSC_PERC' => $arItemIDs["ALL_ITEM_IDS"]['SECOND_DSC_PERC'],
					'DISPLAY_PROP_DIV' => $arItemIDs["ALL_ITEM_IDS"]['DISPLAY_PROP_DIV'],
					'BASKET_ACTIONS_ID' => $arItemIDs["ALL_ITEM_IDS"]['BASKET_ACTIONS'],
					'NOT_AVAILABLE_MESS' => $arItemIDs["ALL_ITEM_IDS"]['NOT_AVAILABLE_MESS'],
					'COMPARE_LINK_ID' => $arItemIDs["ALL_ITEM_IDS"]['COMPARE_LINK'],
					'SUBSCRIBE_ID' => $arItemIDs["ALL_ITEM_IDS"]['SUBSCRIBE_DIV'],
					'SUBSCRIBED_ID' => $arItemIDs["ALL_ITEM_IDS"]['SUBSCRIBED_DIV'],
				),
				'BASKET' => array(
					'QUANTITY' => $arParams['PRODUCT_QUANTITY_VARIABLE'],
					'PROPS' => $arParams['PRODUCT_PROPS_VARIABLE'],
					'SKU_PROPS' => $arItem['OFFERS_PROP_CODES'],
					'ADD_URL_TEMPLATE' => $arResult['~ADD_URL_TEMPLATE'],
					'BUY_URL_TEMPLATE' => $arResult['~BUY_URL_TEMPLATE']
				),
				'PRODUCT' => array(
					'ID' => $arItem['ID'],
					'NAME' => $arItemIDs["TITLE_ITEM"]
				),
				'OFFERS' => $arItem['JS_OFFERS'],
				'OFFER_SELECTED' => $arItem['OFFERS_SELECTED'],
				'TREE_PROPS' => $arSkuProps,
				'LAST_ELEMENT' => $arItem['LAST_ELEMENT']
			);
		}
		$arJSParams['SHOW_DISCOUNT_PERCENT_NUMBER'] = $arParams['SHOW_DISCOUNT_PERCENT_NUMBER'];
		$arJSParams['OFFER_SHOW_PREVIEW_PICTURE_PROPS'] = $arParams['OFFER_SHOW_PREVIEW_PICTURE_PROPS'];
		if ($arParams['DISPLAY_COMPARE']){
			$arJSParams['COMPARE'] = array(
				'COMPARE_URL_TEMPLATE' => $arResult['~COMPARE_URL_TEMPLATE'],
				'COMPARE_URL_TEMPLATE_DEL' => str_replace("ADD_TO_COMPARE_LIST", "DELETE_FROM_COMPARE_LIST", $arResult['~COMPARE_URL_TEMPLATE']),
				'COMPARE_PATH' => $arParams['COMPARE_PATH']
			);
		}
		return $arJSParams;
	}

	public static function GetAddToBasketArray(&$arItem, $totalCount = 0, $defaultCount = 1, $basketUrl = '', $bDetail = false, $arItemIDs = array(), $class_btn = "small", $arParams=array()){
		static $arAddToBasketOptions, $bUserAuthorized;

		$arItem["OFFERS_MORE"] = $arItem["OFFERS_MORE"] ?? 'N';
		$arItemIDs['BUY_LINK'] = $arItemIDs['BUY_LINK'] ?? '';
		$arItemIDs['BASKET_LINK'] = $arItemIDs['BASKET_LINK'] ?? '';
		$arParams['USE_REGION'] = $arParams['USE_REGION'] ?? 'N';

		if($arAddToBasketOptions === NULL){
			$arAddToBasketOptions = array(
				"SHOW_BASKET_ONADDTOCART" => Option::get(self::moduleID, "SHOW_BASKET_ONADDTOCART", "Y", SITE_ID) == "Y",
				"USE_PRODUCT_QUANTITY_LIST" => Option::get(self::moduleID, "USE_PRODUCT_QUANTITY_LIST", "Y", SITE_ID) == "Y",
				"USE_PRODUCT_QUANTITY_DETAIL" => Option::get(self::moduleID, "USE_PRODUCT_QUANTITY_DETAIL", "Y", SITE_ID) == "Y",
				"BUYNOPRICEGGOODS" => Option::get(self::moduleID, "BUYNOPRICEGGOODS", "NOTHING", SITE_ID),
				"BUYMISSINGGOODS" => Option::get(self::moduleID, "BUYMISSINGGOODS", "ADD", SITE_ID),
				"EXPRESSION_ORDER_BUTTON" => Option::get(self::moduleID, "EXPRESSION_ORDER_BUTTON", GetMessage("EXPRESSION_ORDER_BUTTON_DEFAULT"), SITE_ID),
				"EXPRESSION_ORDER_TEXT" => Option::get(self::moduleID, "EXPRESSION_ORDER_TEXT", GetMessage("EXPRESSION_ORDER_TEXT_DEFAULT"), SITE_ID),
				"EXPRESSION_SUBSCRIBE_BUTTON" => Option::get(self::moduleID, "EXPRESSION_SUBSCRIBE_BUTTON", GetMessage("EXPRESSION_SUBSCRIBE_BUTTON_DEFAULT"), SITE_ID),
				"EXPRESSION_SUBSCRIBED_BUTTON" => Option::get(self::moduleID, "EXPRESSION_SUBSCRIBED_BUTTON", GetMessage("EXPRESSION_SUBSCRIBED_BUTTON_DEFAULT"), SITE_ID),
				"EXPRESSION_ADDTOBASKET_BUTTON_DEFAULT" => Option::get(self::moduleID, "EXPRESSION_ADDTOBASKET_BUTTON_DEFAULT", GetMessage("EXPRESSION_ADDTOBASKET_BUTTON_DEFAULT"), SITE_ID),
				"EXPRESSION_ADDEDTOBASKET_BUTTON_DEFAULT" => Option::get(self::moduleID, "EXPRESSION_ADDEDTOBASKET_BUTTON_DEFAULT", GetMessage("EXPRESSION_ADDEDTOBASKET_BUTTON_DEFAULT"), SITE_ID),
				"EXPRESSION_READ_MORE_OFFERS_DEFAULT" => Option::get(self::moduleID, "EXPRESSION_READ_MORE_OFFERS_DEFAULT", GetMessage("EXPRESSION_READ_MORE_OFFERS_DEFAULT"), SITE_ID),
			);

			global $USER;
			$bUserAuthorized = $USER->IsAuthorized();
		}

		$buttonText = $buttonHTML = $buttonACTION = '';
		$quantity=$ratio=1;
		$max_quantity=0;
		$float_ratio=is_double($arItem["CATALOG_MEASURE_RATIO"]);		

		$minPriceRangeQty = 0;
		// if (isset($arItem['PRICE_MATRIX']) && $arItem['PRICE_MATRIX']) {
		if (isset($arItem['ITEM_PRICE_MODE']) && $arItem['ITEM_PRICE_MODE'] === 'Q') {
			$priceSelected = $arItem['ITEM_PRICE_SELECTED'];
			if (isset($arItem['FIX_PRICE_MATRIX']) && $arItem['FIX_PRICE_MATRIX']) {
				$priceSelected = $arItem['FIX_PRICE_MATRIX']['PRICE_SELECT'];
			}
			if (isset($arItem['ITEM_PRICES']) && $arItem['ITEM_PRICES'][$priceSelected]['MIN_QUANTITY'] != 1) {
				$minPriceRangeQty = $arItem['ITEM_PRICES'][$priceSelected]['MIN_QUANTITY'];
			}
		}	
		
		$setMinQty = false;
		if ($arItem["CATALOG_MEASURE_RATIO"] || $minPriceRangeQty) {
			if ($minPriceRangeQty && ($minPriceRangeQty > $arItem["CATALOG_MEASURE_RATIO"])) {
				$quantity=$minPriceRangeQty;
				$setMinQty = true;
			} else {
				$quantity=$arItem["CATALOG_MEASURE_RATIO"];
			}
			if ($arItem["CATALOG_MEASURE_RATIO"]) {
				$ratio=$arItem["CATALOG_MEASURE_RATIO"];
			}
		} else {
			$quantity=$defaultCount;
		}

		if($arItem["CATALOG_QUANTITY_TRACE"]=="Y"){
			if($totalCount < $quantity){
				$quantity=($totalCount>$arItem["CATALOG_MEASURE_RATIO"] ? $totalCount : $arItem["CATALOG_MEASURE_RATIO"] );
			}
			if ($arItem["CATALOG_CAN_BUY_ZERO"] !== "Y") {
				$max_quantity=$totalCount;
			}
		}
		
		$canBuy = isset($arItem["CAN_BUY"]) && $arItem["CAN_BUY"];
		if($arParams['USE_REGION'] == 'Y' && $arParams['STORES'])
		{
			$canBuy = ( ($totalCount && ($arItem["OFFERS"] || $arItem["CAN_BUY"])) || ((!$totalCount && $arItem["CATALOG_QUANTITY_TRACE"] == "N") || (!$totalCount && $arItem["CATALOG_QUANTITY_TRACE"] == "Y" && $arItem["CATALOG_CAN_BUY_ZERO"] == "Y")) );
		}
		$arItem["CAN_BUY"] = $canBuy;

		//for buy_services in basket_fly
		if( isset($arParams["EXACT_QUANTITY"]) && $arParams["EXACT_QUANTITY"] > 0 )
			$quantity=$arParams["EXACT_QUANTITY"];
		

		$isOffer = isset($arItem['IS_OFFER']) && $arItem['IS_OFFER'] === 'Y' ? 'Y' : 'N';
		$arItemProps = $isOffer === 'Y' ? ($arParams['OFFERS_CART_PROPERTIES'] ? implode(';', (array)$arParams['OFFERS_CART_PROPERTIES']) : "") : ($arParams['PRODUCT_PROPERTIES'] ? implode(';', (array)$arParams['PRODUCT_PROPERTIES']) : "");
		$partProp=($arParams["PARTIAL_PRODUCT_PROPERTIES"] ? $arParams["PARTIAL_PRODUCT_PROPERTIES"] : "" );
		$addProp=($arParams["ADD_PROPERTIES_TO_BASKET"] ? $arParams["ADD_PROPERTIES_TO_BASKET"] : "" );
		$emptyProp=$arItem["EMPTY_PROPS_JS"] ?? '';
		$bShowOutOfProduction = isset($arItem['PROPERTIES']['OUT_OF_PRODUCTION']) && $arItem['PROPERTIES']['OUT_OF_PRODUCTION']['VALUE'] === 'Y';
		$frontCatalog = isset($arItem["FRONT_CATALOG"]) && $arItem["FRONT_CATALOG"] === 'Y' ? 'Y' : 'N';

		if ($bShowOutOfProduction) {
			$buttonACTION = "OUT_OF_PRODUCTION";
			if (isset($arItem['PROPERTIES']['PRODUCT_ANALOG_FILTER']) && $arItem['PROPERTIES']['PRODUCT_ANALOG_FILTER']['VALUE']) {
				$buttonTEXT = GetMessage('EXPRESSION_OUT_OF_PRODUCTION_TEXT');
				$buttonHTML = "<a href='".$arItem['PROPERTIES']['PRODUCT_ANALOG_FILTER']['VALUE']."' class='btn btn-default btn-wide transition_bg ".$class_btn." has-ripple btn--wrap-text fill-use-999' title='".$buttonTEXT."'>".(!$bDetail ? self::showSpriteIconSvg(SITE_TEMPLATE_PATH."/images/svg/catalog/catalog_icons.svg#similar-19-16", "", ['WIDTH' => 19,'HEIGHT' => 16]) : '')."<span>".$buttonTEXT."</span></a>";
			}elseif (!$bDetail) {
				$buttonText = array($arAddToBasketOptions['EXPRESSION_READ_MORE_OFFERS_DEFAULT']);
				$buttonHTML = '<a class="btn btn-default basket read_more '.$class_btn.'" rel="nofollow" href="'.$arItem["DETAIL_PAGE_URL"].'" data-item="'.$arItem["ID"].'">'.self::showIconSvg("fw ncolor colored", SITE_TEMPLATE_PATH."/images/svg/more_c.svg", $buttonText[0]).'<span>'.$buttonText[0].'</span></a>';
			}
		} elseif ($arItem["OFFERS"]) {
			global $arTheme;
			$type_sku = is_array($arTheme) ? (isset($arTheme["TYPE_SKU"]["VALUE"]) ? $arTheme["TYPE_SKU"]["VALUE"] : $arTheme["TYPE_SKU"]) : 'TYPE_1';
			if(!$bDetail && $arItem["OFFERS_MORE"] != "Y" && $type_sku != "TYPE_2"){
				$buttonACTION = 'ADD';
				$buttonText = array($arAddToBasketOptions['EXPRESSION_ADDTOBASKET_BUTTON_DEFAULT'], $arAddToBasketOptions['EXPRESSION_ADDEDTOBASKET_BUTTON_DEFAULT']);
				$buttonHTML = '<span class="btn btn-default transition_bg '.$class_btn.' read_more1 to-cart animate-load" id="'.$arItemIDs['BUY_LINK'].'" data-offers="N" data-iblockID="'.$arItem["IBLOCK_ID"].'" data-item="'.$arItem["ID"].'">'.self::showIconSvg("fw ncolor colored", SITE_TEMPLATE_PATH."/images/svg/basket.svg", $buttonText[0]).'<span>'.$buttonText[0].'</span></span><a rel="nofollow" href="'.$basketUrl.'" id="'.$arItemIDs['BASKET_LINK'].'" class="'.$class_btn.' in-cart btn btn-default transition_bg" data-item="'.$arItem["ID"].'"  style="display:none;">'.self::showIconSvg("fw ncolor colored", SITE_TEMPLATE_PATH."/images/svg/inbasket.svg", $buttonText[1]).'<span>'.$buttonText[1].'</span></a>';
			}
			elseif(($bDetail && $frontCatalog == "Y") || $arItem["OFFERS_MORE"]=="Y" || $type_sku == "TYPE_2"){
				$buttonACTION = 'MORE';
				$buttonText = array($arAddToBasketOptions['EXPRESSION_READ_MORE_OFFERS_DEFAULT']);
				$buttonHTML = '<a class="btn btn-default basket read_more '.$class_btn.'" rel="nofollow" href="'.$arItem["DETAIL_PAGE_URL"].'" data-item="'.$arItem["ID"].'">'.self::showIconSvg("fw ncolor colored", SITE_TEMPLATE_PATH."/images/svg/more_c.svg", $buttonText[0]).'<span>'.$buttonText[0].'</span></a>';
			}
		} elseif (isset($arItem["SHOW_MORE_BUTTON"]) && $arItem["SHOW_MORE_BUTTON"] == "Y") {
			$buttonACTION = 'MORE';
			$buttonText = array($arAddToBasketOptions['EXPRESSION_READ_MORE_OFFERS_DEFAULT']);
			$buttonHTML = '<a class="btn btn-default basket read_more '.$class_btn.'" rel="nofollow" href="'.$arItem["DETAIL_PAGE_URL"].'" data-item="'.$arItem["ID"].'">'.self::showIconSvg("fw ncolor colored", SITE_TEMPLATE_PATH."/images/svg/more_c.svg", $buttonText[0]).'<span>'.$buttonText[0].'</span></a>';
		}
		else{
			if($bPriceExists = isset($arItem["MIN_PRICE"]) && $arItem["MIN_PRICE"]["VALUE"] > 0){
				// price exists
				if($totalCount > 0 && (isset($arItem["CAN_BUY"]) && $arItem["CAN_BUY"])){
					// rest exists
					if((isset($arItem["CAN_BUY"]) && $arItem["CAN_BUY"]) || (isset($arItem["MIN_PRICE"]) && $arItem["MIN_PRICE"]["CAN_BUY"] == "Y")){
						if($bDetail && $frontCatalog == "Y"){
							$buttonACTION = 'MORE';
							$buttonText = array($arAddToBasketOptions['EXPRESSION_READ_MORE_OFFERS_DEFAULT']);
							$rid=($arItem["RID"] ? "?RID=".$arItem["RID"] : "");
							$buttonHTML = '<a class="btn btn-default transition_bg basket read_more '.$class_btn.'" rel="nofollow" href="'.$arItem["DETAIL_PAGE_URL"].$rid.'" data-item="'.$arItem["ID"].'">'.self::showIconSvg("fw ncolor colored", SITE_TEMPLATE_PATH."/images/svg/more_c.svg", $buttonText[0]).'<span>'.$buttonText[0].'</span></a>';
						}
						else{

							$arItem["CAN_BUY"] = 1;
							$buttonACTION = 'ADD';
							$buttonText = array($arAddToBasketOptions['EXPRESSION_ADDTOBASKET_BUTTON_DEFAULT'], $arAddToBasketOptions['EXPRESSION_ADDEDTOBASKET_BUTTON_DEFAULT']);
							$buttonHTML = '<span data-value="'.$arItem["MIN_PRICE"]["DISCOUNT_VALUE"].'" data-currency="'.$arItem["MIN_PRICE"]["CURRENCY"].'" class="'.$class_btn.' to-cart btn btn-default transition_bg animate-load" data-item="'.$arItem["ID"].'" data-float_ratio="'.$float_ratio.'" data-ratio="'.$ratio.'" data-bakset_div="bx_basket_div_'.$arItem["ID"].(isset($arItemIDs['DOP_ID']) && $arItemIDs['DOP_ID'] ? '_'.$arItemIDs['DOP_ID'] : '').'" data-props="'.$arItemProps.'" data-part_props="'.$partProp.'" data-add_props="'.$addProp.'"  data-empty_props="'.$emptyProp.'" data-offers="'.$isOffer.'" data-iblockID="'.$arItem["IBLOCK_ID"].'"  data-quantity="'.$quantity.'">'.self::showIconSvg("fw ncolor colored", SITE_TEMPLATE_PATH."/images/svg/basket.svg", $buttonText[0]).'<span>'.$buttonText[0].'</span></span><a rel="nofollow" href="'.$basketUrl.'" class="'.$class_btn.' in-cart btn btn-default transition_bg" data-item="'.$arItem["ID"].'"  style="display:none;">'.self::showIconSvg("fw ncolor colored", SITE_TEMPLATE_PATH."/images/svg/inbasket.svg", $buttonText[1]).'<span>'.$buttonText[1].'</span></a>';
						}
					}
					elseif($arItem["CATALOG_SUBSCRIBE"] == "Y"){
						$buttonACTION = 'SUBSCRIBE';
						$buttonText = array($arAddToBasketOptions['EXPRESSION_SUBSCRIBE_BUTTON'], $arAddToBasketOptions['EXPRESSION_SUBSCRIBED_BUTTON']);
						$buttonHTML = '<span class="'.$class_btn.' ss to-subscribe'.(!$bUserAuthorized ? ' auth' : '').(self::checkVersionModule('16.5.3', 'catalog') ? ' nsubsc' : '').' btn btn-default transition_bg" rel="nofollow" data-param-form_id="subscribe" data-name="subscribe" data-param-id="'.$arItem["ID"].'" data-item="'.$arItem["ID"].'">'.self::showIconSvg("fw ncolor colored", SITE_TEMPLATE_PATH."/images/svg/subsribe_c.svg", $buttonText[0]).'<span>'.$buttonText[0].'</span></span><span class="'.$class_btn.' ss in-subscribe btn btn-default transition_bg" rel="nofollow" style="display:none;" data-item="'.$arItem["ID"].'">'.self::showIconSvg("fw ncolor colored", SITE_TEMPLATE_PATH."/images/svg/subsribe_c.svg", $buttonText[0]).'<span>'.$buttonText[1].'</span></span>';
					}
				}
				else{
					if(!strlen($arAddToBasketOptions['EXPRESSION_ORDER_BUTTON'])){
						$arAddToBasketOptions['EXPRESSION_ORDER_BUTTON']=GetMessage("EXPRESSION_ORDER_BUTTON_DEFAULT");
					}
					if(!strlen($arAddToBasketOptions['EXPRESSION_SUBSCRIBE_BUTTON'])){
						$arAddToBasketOptions['EXPRESSION_SUBSCRIBE_BUTTON']=GetMessage("EXPRESSION_SUBSCRIBE_BUTTON_DEFAULT");
					}
					if(!strlen($arAddToBasketOptions['EXPRESSION_SUBSCRIBED_BUTTON'])){
						$arAddToBasketOptions['EXPRESSION_SUBSCRIBED_BUTTON']=GetMessage("EXPRESSION_SUBSCRIBED_BUTTON_DEFAULT");
					}
					// no rest
					if($bDetail && $frontCatalog == "Y"){
						$buttonACTION = 'MORE';
						$buttonText = array($arAddToBasketOptions['EXPRESSION_READ_MORE_OFFERS_DEFAULT']);
						$rid=($arItem["RID"] ? "?RID=".$arItem["RID"] : "");
						$buttonHTML = '<a class="btn btn-default basket read_more '.$class_btn.'" rel="nofollow" href="'.$arItem["DETAIL_PAGE_URL"].$rid.'" data-item="'.$arItem["ID"].'">'.self::showIconSvg("fw ncolor colored", SITE_TEMPLATE_PATH."/images/svg/more_c.svg", $buttonText[0]).'<span>'.$buttonText[0].'</span></a>';
					}
					else{
						$buttonACTION = $arAddToBasketOptions["BUYMISSINGGOODS"];
						if($arAddToBasketOptions["BUYMISSINGGOODS"] == "ADD" /*|| $arItem["CAN_BUY"]*/){
							if($arItem["CAN_BUY"]){
								$buttonText = array($arAddToBasketOptions['EXPRESSION_ADDTOBASKET_BUTTON_DEFAULT'], $arAddToBasketOptions['EXPRESSION_ADDEDTOBASKET_BUTTON_DEFAULT']);
								$buttonHTML = '<span data-value="'.$arItem["MIN_PRICE"]["DISCOUNT_VALUE"].'" data-currency="'.$arItem["MIN_PRICE"]["CURRENCY"].'" class="'.$class_btn.' to-cart btn btn-default transition_bg animate-load" data-item="'.$arItem["ID"].'" data-float_ratio="'.$float_ratio.'" data-ratio="'.$ratio.'" data-bakset_div="bx_basket_div_'.$arItem["ID"].'" data-props="'.$arItemProps.'" data-part_props="'.$partProp.'" data-add_props="'.$addProp.'"  data-empty_props="'.$emptyProp.'" data-offers="'.$arItem["IS_OFFER"].'" data-iblockID="'.$arItem["IBLOCK_ID"].'" data-quantity="'.$quantity.'">'.self::showIconSvg("fw ncolor colored", SITE_TEMPLATE_PATH."/images/svg/basket.svg", $buttonText[0]).'<span>'.$buttonText[0].'</span></span><a rel="nofollow" href="'.$basketUrl.'" class="'.$class_btn.' in-cart btn btn-default transition_bg" data-item="'.$arItem["ID"].'"  style="display:none;">'.self::showIconSvg("fw ncolor colored", SITE_TEMPLATE_PATH."/images/svg/inbasket.svg", $buttonText[1]).'<span>'.$buttonText[1].'</span></a>';
							}else{
								if($arAddToBasketOptions["BUYMISSINGGOODS"] == "SUBSCRIBE" && $arItem["CATALOG_SUBSCRIBE"] == "Y"){
									$buttonText = array($arAddToBasketOptions['EXPRESSION_SUBSCRIBE_BUTTON'], $arAddToBasketOptions['EXPRESSION_SUBSCRIBED_BUTTON']);
									$buttonHTML = '<span class="'.$class_btn.' ss to-subscribe'.(!$bUserAuthorized ? ' auth' : '').(self::checkVersionModule('16.5.3', 'catalog') ? ' nsubsc' : '').' btn btn-default transition_bg" rel="nofollow" data-name="subscribe" data-param-form_id="subscribe" data-param-id="'.$arItem["ID"].'"  data-item="'.$arItem["ID"].'">'.self::showIconSvg("fw ncolor colored", SITE_TEMPLATE_PATH."/images/svg/subsribe_c.svg", $buttonText[0]).'<span>'.$buttonText[0].'</span></span><span class="'.$class_btn.' ss in-subscribe btn btn-default transition_bg" rel="nofollow" style="display:none;" data-item="'.$arItem["ID"].'">'.self::showIconSvg("fw ncolor colored", SITE_TEMPLATE_PATH."/images/svg/subsribe_c.svg", $buttonText[0]).'<span>'.$buttonText[1].'</span></span>';
								}else{
									$buttonText = array($arAddToBasketOptions['EXPRESSION_ORDER_BUTTON']);
									$buttonHTML = '<span class="'.$class_btn.' to-order btn btn-default animate-load" data-event="jqm" data-param-form_id="TOORDER" data-name="toorder" data-autoload-product_name="'.self::formatJsName($arItem["NAME"]).'" data-autoload-product_id="'.$arItem["ID"].'">'.self::showIconSvg("fw ncolor colored", SITE_TEMPLATE_PATH."/images/svg/mail_c.svg", $buttonText[0]).'<span>'.$buttonText[0].'</span></span>';
									if($arAddToBasketOptions['EXPRESSION_ORDER_TEXT']){
										$buttonHTML .='<div class="more_text">'.$arAddToBasketOptions['EXPRESSION_ORDER_TEXT'].'</div>';
									}
								}
							}

						}
						elseif($arAddToBasketOptions["BUYMISSINGGOODS"] == "SUBSCRIBE" && $arItem["CATALOG_SUBSCRIBE"] == "Y"){

							$buttonText = array($arAddToBasketOptions['EXPRESSION_SUBSCRIBE_BUTTON'], $arAddToBasketOptions['EXPRESSION_SUBSCRIBED_BUTTON']);
							$buttonHTML = '<span class="'.$class_btn.' ss to-subscribe '.(!$bUserAuthorized ? ' auth' : '').(self::checkVersionModule('16.5.3', 'catalog') ? ' nsubsc' : '').' btn btn-default transition_bg" data-name="subscribe" data-param-form_id="subscribe" data-param-id="'.$arItem["ID"].'"  rel="nofollow" data-item="'.$arItem["ID"].'">'.self::showIconSvg("fw ncolor colored", SITE_TEMPLATE_PATH."/images/svg/mail_c.svg", $buttonText[0]).'<span>'.$buttonText[0].'</span></span><span class="'.$class_btn.' ss in-subscribe btn btn-default transition_bg" rel="nofollow" style="display:none;" data-item="'.$arItem["ID"].'">'.self::showIconSvg("fw ncolor colored", SITE_TEMPLATE_PATH."/images/svg/mail_c.svg", $buttonText[0]).'<span>'.$buttonText[1].'</span></span>';
						}
						elseif($arAddToBasketOptions["BUYMISSINGGOODS"] == "ORDER"){
							$buttonText = array($arAddToBasketOptions['EXPRESSION_ORDER_BUTTON']);
							$buttonHTML = '<span class="'.$class_btn.' to-order btn btn-default animate-load" data-event="jqm" data-param-form_id="TOORDER" data-name="toorder" data-autoload-product_name="'.self::formatJsName($arItem["NAME"]).'" data-autoload-product_id="'.$arItem["ID"].'">'.self::showIconSvg("fw ncolor colored", SITE_TEMPLATE_PATH."/images/svg/mail_c.svg", $buttonText[0]).'<span>'.$buttonText[0].'</span></span>';
							if($arAddToBasketOptions['EXPRESSION_ORDER_TEXT']){
								$buttonHTML .='<div class="more_text">'.$arAddToBasketOptions['EXPRESSION_ORDER_TEXT'].'</div>';
							}
						}
					}
				}
			}
			else{
				// no price or price <= 0
				if($bDetail && $frontCatalog == "Y"){
					$buttonACTION = 'MORE';
					$buttonText = array($arAddToBasketOptions['EXPRESSION_READ_MORE_OFFERS_DEFAULT']);
					$buttonHTML = '<a class="btn btn-default transition_bg basket read_more '.$class_btn.'" rel="nofollow" href="'.$arItem["DETAIL_PAGE_URL"].'" data-item="'.$arItem["ID"].'">'.self::showIconSvg("fw ncolor colored", SITE_TEMPLATE_PATH."/images/svg/more_c.svg", $buttonText[0]).'<span>'.$buttonText[0].'</span></a>';
				}
				else{
					$buttonACTION = $arAddToBasketOptions["BUYNOPRICEGGOODS"];
					if($arAddToBasketOptions["BUYNOPRICEGGOODS"] == "ORDER"){
						$buttonText = $arAddToBasketOptions['EXPRESSION_ORDER_BUTTON'] ? array($arAddToBasketOptions['EXPRESSION_ORDER_BUTTON']) : array(Loc::getMessage('EXPRESSION_ORDER_BUTTON_DEFAULT'));
						$buttonHTML = '<span class="'.$class_btn.' to-order btn btn-default animate-load" data-event="jqm" data-param-form_id="TOORDER" data-name="toorder" data-autoload-product_name="'.self::formatJsName($arItem["NAME"]).'" data-autoload-product_id="'.$arItem["ID"].'">'.self::showIconSvg("fw ncolor colored", SITE_TEMPLATE_PATH."/images/svg/mail_c.svg", $buttonText[0]).'<span>'.$buttonText[0].'</span></span>';
						if($arAddToBasketOptions['EXPRESSION_ORDER_TEXT']){
							$buttonHTML .='<div class="more_text">'.$arAddToBasketOptions['EXPRESSION_ORDER_TEXT'].'</div>';
						}
					}
				}
			}
		}
		
		//add name atr for js notice
		$buttonHTML .= '<span class="hidden" data-js-item-name="'.self::formatJsName($arItem['IPROPERTY_VALUES']["ELEMENT_PAGE_TITLE"] ?? $arItem['NAME']).'"></span>';

		$arOptions = array("OPTIONS" => $arAddToBasketOptions, "TEXT" => $buttonText, "HTML" => $buttonHTML, "ACTION" => $buttonACTION, "RATIO_ITEM" => $ratio, "MIN_QUANTITY_BUY" => $quantity, "MAX_QUANTITY_BUY" => $max_quantity, "CAN_BUY" => $canBuy);

		if ($setMinQty) {
			$arOptions["SET_MIN_QUANTITY_BUY"] = true;
		}

		foreach(GetModuleEvents(ASPRO_MAX_MODULE_ID, 'OnAsproGetBuyBlockElement', true) as $arEvent) // event for manipulation with buy block element
			ExecuteModuleEventEx($arEvent, array($arItem, $totalCount, $arParams, &$arOptions));

		return $arOptions;
	}

	public static function checkShowDelay($arParams, $quantity, $arItem = array()){
		$bShowBlock = true;
		if($arParams['USE_REGION'] == 'Y')
		{
			if($arItem)
			{
				$canBuy = $arItem["CAN_BUY"];
				if($arParams['STORES'])
					$canBuy = ( ($quantity && $arItem["CAN_BUY"]) || ((!$quantity && $arItem["CATALOG_QUANTITY_TRACE"] == "N") || (!$quantity && $arItem["CATALOG_QUANTITY_TRACE"] == "Y" && $arItem["CATALOG_CAN_BUY_ZERO"] == "Y")));
				if(!$canBuy)
					$bShowBlock = false;
			}
			elseif(!$quantity)
				$bShowBlock = false;
		}
		return $bShowBlock;
	}

	public static function checkVersionExt($template="main", $module="catalog"){
		if($info = CModule::CreateModuleObject($module)){
			$testVersion = '16.0.14';
			if(CheckVersion($testVersion, $info->MODULE_VERSION)){
				$templateInclude=$template;
			}
			else{
				$templateInclude=$template."_new";
			}
		}
		return $templateInclude;
	}

	public static function checkVersionModule($version = '1.0.0', $module="catalog"){
		if($info = CModule::CreateModuleObject($module))
		{
			if(!CheckVersion($version, $info->MODULE_VERSION))
				return true;
		}
		return false;
	}

	public static function GetFileInfo($arItem){
		$arTmpItem = CFile::GetFileArray($arItem);
		switch($arTmpItem["CONTENT_TYPE"]){
			case 'application/pdf': $type="pdf"; break;
			case 'application/vnd.ms-excel': $type="excel"; break;
			case 'application/vnd.ms-office': $type="excel"; break;
			case 'application/xls': $type="excel"; break;
			case 'application/octet-stream': $type="word"; break;
			case 'application/msword': $type="word"; break;
			case 'image/jpeg': $type="jpg"; break;
			case 'image/tiff': $type="tiff"; break;
			case 'image/png': $type="png"; break;
			default: $type="default"; break;
		}
		if($type == "default")
		{
			$frm = explode('.', $arTmpItem['FILE_NAME']);
			$frm = $frm[1];
			if($frm == 'doc' || $frm == 'docx')
				$type = 'doc';
			elseif($frm == 'xls' || $frm == 'xlsx')
				$type = 'xls';
			elseif($frm == 'jpg' || $frm == 'jpeg')
				$type = 'jpg';
			elseif($frm == 'png')
				$type = 'png';
			elseif($frm == 'ppt' || $frm == 'pptx')
				$type = 'ppt';
			elseif($frm == 'tif')
				$type = 'tif';
			elseif($frm == 'pdf')
				$type = 'pdf';
			elseif($frm == 'txt')
				$type = 'txt';
			elseif($frm == 'gif')
				$type = 'gif';
			elseif($frm == 'bmp')
				$type = 'bmp';
			else
				$type = 'file';
		}

		$filesize = $arTmpItem["FILE_SIZE"];
		if($filesize > 1024){
			$filesize = ($filesize / 1024);
			if($filesize > 1024){
				$filesize = ($filesize / 1024);
				if($filesize > 1024){
					$filesize = ($filesize / 1024);
					$filesize = round($filesize, 1);
					$filesize_format=str_replace(".", ",", $filesize).GetMessage('CT_NAME_GB');
				}
				else{
					$filesize = round($filesize, 1);
					$filesize_format=str_replace(".", ",", $filesize).GetMessage('CT_NAME_MB');
				}
			}
			else{
				$filesize = round($filesize, 1);
				$filesize_format=str_replace(".", ",", $filesize).GetMessage('CT_NAME_KB');
			}
		}
		else{
			$filesize = round($filesize, 1);
			$filesize_format=str_replace(".", ",", $filesize).GetMessage('CT_NAME_b');
		}
		$fileName = substr($arTmpItem["ORIGINAL_NAME"], 0, strrpos($arTmpItem["ORIGINAL_NAME"], '.'));
		return array("TYPE" => $type, "FILE_SIZE" => $filesize, "FILE_SIZE_FORMAT" => $filesize_format, "DESCRIPTION" => ( $arTmpItem["DESCRIPTION"] ? $arTmpItem["DESCRIPTION"] : $fileName), "SRC" => $arTmpItem["SRC"]);
	}

	public static function get_file_info($fileID){
		$file = CFile::GetFileArray($fileID);
		$pos = strrpos($file['FILE_NAME'], '.');
		$file['FILE_NAME'] = substr($file['FILE_NAME'], $pos);
		if(!$file['FILE_SIZE']){
			// bx bug in some version
			$file['FILE_SIZE'] = filesize($_SERVER['DOCUMENT_ROOT'].$file['SRC']);
		}
		$frm = explode('.', $file['FILE_NAME']);
		$frm = $frm[1];
		if($frm == 'doc' || $frm == 'docx'){
			$type = 'doc';
		}
		elseif($frm == 'xls' || $frm == 'xlsx'){
			$type = 'xls';
		}
		elseif($frm == 'jpg' || $frm == 'jpeg'){
			$type = 'jpg';
		}
		elseif($frm == 'png'){
			$type = 'png';
		}
		elseif($frm == 'ppt'){
			$type = 'ppt';
		}
		elseif($frm == 'tif'){
			$type = 'tif';
		}
		elseif($frm == 'txt'){
			$type = 'txt';
		}
		else{
			$type = 'pdf';
		}
		return $arr = array('TYPE' => $type, 'FILE_SIZE' => $file['FILE_SIZE'], 'SRC' => $file['SRC'], 'DESCRIPTION' => $file['DESCRIPTION'], 'ORIGINAL_NAME' => $file['ORIGINAL_NAME']);
	}

	public static function filesize_format($filesize){
		$formats = array(GetMessage('CT_NAME_b'), GetMessage('CT_NAME_KB'), GetMessage('CT_NAME_MB'), GetMessage('CT_NAME_GB'), GetMessage('CT_NAME_TB'));
		$format = 0;
		while($filesize > 1024 && count($formats) != ++$format){
			$filesize = round($filesize / 1024, 1);
		}
		$formats[] = GetMessage('CT_NAME_TB');
		return $filesize.' '.$formats[$format];
	}

	public static function getMinPriceFromOffersExt(&$offers, $currency, $replaceMinPrice = true){
		$replaceMinPrice = ($replaceMinPrice === true);
		$result = false;
		$minPrice = 0;
		$bCheckCanBuy = Option::get(self::moduleID, "CHECK_CAN_BUY_SKU2", "Y", SITE_ID) == "Y";
		if (!empty($offers) && is_array($offers))
		{
			$doubles = array();
			foreach ($offers as $oneOffer)
			{
				if(!$oneOffer["MIN_PRICE"])
					continue;
				$oneOffer['ID'] = (int)$oneOffer['ID'];
				if (isset($doubles[$oneOffer['ID']]))
					continue;
				if ($bCheckCanBuy && !$oneOffer['CAN_BUY'])
					continue;

				/*if ($oneOffer['PRICES']) {
					print_r($oneOffer['MIN_PRICE']);
					foreach ($oneOffer['PRICES'] as $arPrice) {
						# code...
					}
				}*/

				CIBlockPriceTools::setRatioMinPrice($oneOffer, $replaceMinPrice);

				$oneOffer['MIN_PRICE']['CATALOG_MEASURE_RATIO'] = $oneOffer['CATALOG_MEASURE_RATIO'];
				$oneOffer['MIN_PRICE']['CATALOG_MEASURE'] = $oneOffer['CATALOG_MEASURE'];
				$oneOffer['MIN_PRICE']['CATALOG_MEASURE_NAME'] = $oneOffer['CATALOG_MEASURE_NAME'];
				$oneOffer['MIN_PRICE']['~CATALOG_MEASURE_NAME'] = $oneOffer['~CATALOG_MEASURE_NAME'];
				if (empty($result))
				{
					$minPrice = ($oneOffer['MIN_PRICE']['CURRENCY'] == $currency
						? $oneOffer['MIN_PRICE']['DISCOUNT_VALUE']
						: CCurrencyRates::ConvertCurrency($oneOffer['MIN_PRICE']['DISCOUNT_VALUE'], $oneOffer['MIN_PRICE']['CURRENCY'], $currency)
					);

					$result = $oneOffer['MIN_PRICE'];
				}
				else
				{
					$comparePrice = ($oneOffer['MIN_PRICE']['CURRENCY'] == $currency
						? $oneOffer['MIN_PRICE']['DISCOUNT_VALUE']
						: CCurrencyRates::ConvertCurrency($oneOffer['MIN_PRICE']['DISCOUNT_VALUE'], $oneOffer['MIN_PRICE']['CURRENCY'], $currency)
					);
					if ($minPrice > $comparePrice/* && $oneOffer['MIN_PRICE']['CAN_BUY'] == 'Y'*/)
					{
						$minPrice = $comparePrice;
						$result = $oneOffer['MIN_PRICE'];
					}
				}
				// echo $minPrice.' - '.$comparePrice;
				$doubles[$oneOffer['ID']] = true;
			}
		}
		// print_r($result);
		return $result;
	}

	public static function getMaxPriceFromOffersExt(&$offers, $currency, $replaceMaxPrice = true){
		$replaceMaxPrice = ($replaceMaxPrice === true);
		$result = false;
		$maxPrice = 0;
		if (!empty($offers) && is_array($offers))
		{
			$doubles = array();
			foreach ($offers as $oneOffer)
			{
				if(!$oneOffer["MIN_PRICE"])
					continue;
				$oneOffer['ID'] = (int)$oneOffer['ID'];
				if (isset($doubles[$oneOffer['ID']]))
					continue;
				/*if (!$oneOffer['CAN_BUY'])
					continue;*/

				CIBlockPriceTools::setRatioMinPrice($oneOffer, $replaceMaxPrice);

				$oneOffer['MIN_PRICE']['CATALOG_MEASURE_RATIO'] = $oneOffer['CATALOG_MEASURE_RATIO'];
				$oneOffer['MIN_PRICE']['CATALOG_MEASURE'] = $oneOffer['CATALOG_MEASURE'];
				$oneOffer['MIN_PRICE']['CATALOG_MEASURE_NAME'] = $oneOffer['CATALOG_MEASURE_NAME'];
				$oneOffer['MIN_PRICE']['~CATALOG_MEASURE_NAME'] = $oneOffer['~CATALOG_MEASURE_NAME'];

				if (empty($result))
				{
					$maxPrice = ($oneOffer['MIN_PRICE']['CURRENCY'] == $currency
						? $oneOffer['MIN_PRICE']['DISCOUNT_VALUE']
						: CCurrencyRates::ConvertCurrency($oneOffer['MIN_PRICE']['DISCOUNT_VALUE'], $oneOffer['MIN_PRICE']['CURRENCY'], $currency)
					);
					$result = $oneOffer['MIN_PRICE'];
				}
				else
				{
					$comparePrice = ($oneOffer['MIN_PRICE']['CURRENCY'] == $currency
						? $oneOffer['MIN_PRICE']['DISCOUNT_VALUE']
						: CCurrencyRates::ConvertCurrency($oneOffer['MIN_PRICE']['DISCOUNT_VALUE'], $oneOffer['MIN_PRICE']['CURRENCY'], $currency)
					);
					if ($maxPrice < $comparePrice)
					{
						$maxPrice = $comparePrice;
						$result = $oneOffer['MIN_PRICE'];
					}
				}
				$doubles[$oneOffer['ID']] = true;
			}
		}
		return $result;
	}

	public static function getSliderForItemExt(&$item, $propertyCode, $addDetailToSlider, $encode = true)
    {
        $encode = ($encode === true);
        $result = array();

        if (!empty($item) && is_array($item))
        {

            if (
                '' != $propertyCode &&
                isset($item['PROPERTIES'][$propertyCode]) &&
                'F' == $item['PROPERTIES'][$propertyCode]['PROPERTY_TYPE']
            )
            {
                if ('MORE_PHOTO' == $propertyCode && isset($item['MORE_PHOTO']) && !empty($item['MORE_PHOTO']))
                {

                    foreach ($item['MORE_PHOTO'] as &$onePhoto)
                    {
						if(is_array($onePhoto) && isset($onePhoto['ID'])){
							$alt = ($onePhoto["DESCRIPTION"] ? $onePhoto["DESCRIPTION"] : ($item['IPROPERTY_VALUES']['ELEMENT_DETAIL_PICTURE_FILE_ALT'] ? $item['IPROPERTY_VALUES']['ELEMENT_DETAIL_PICTURE_FILE_ALT'] : $item["NAME"]));
							$title = ($onePhoto["DESCRIPTION"] ? $onePhoto["DESCRIPTION"] : ($item['IPROPERTY_VALUES']['ELEMENT_DETAIL_PICTURE_FILE_TITLE'] ? $item['IPROPERTY_VALUES']['ELEMENT_DETAIL_PICTURE_FILE_TITLE'] : $item["NAME"]));
							if(isset($item['ALT_TITLE_GET']) && $item['ALT_TITLE_GET'] == 'SEO')
							{
								$alt = ($item['IPROPERTY_VALUES']['ELEMENT_DETAIL_PICTURE_FILE_ALT'] ? $item['IPROPERTY_VALUES']['ELEMENT_DETAIL_PICTURE_FILE_ALT'] : $item["NAME"]);
								$title = ($item['IPROPERTY_VALUES']['ELEMENT_DETAIL_PICTURE_FILE_TITLE'] ? $item['IPROPERTY_VALUES']['ELEMENT_DETAIL_PICTURE_FILE_TITLE'] : $item["NAME"]);
							}
							$result[] = array(
								'ID' => (int)$onePhoto['ID'],
								'SRC' => ($encode ? CHTTP::urnEncode($onePhoto['SRC'], 'utf-8') : $onePhoto['SRC']),
								'WIDTH' => (int)$onePhoto['WIDTH'],
								'HEIGHT' => (int)$onePhoto['HEIGHT'],
								'ALT' => $alt,
								'TITLE' => $title
							);
						}
                    }
                    unset($onePhoto);
                }
                else
                {
                    if (
                        isset($item['DISPLAY_PROPERTIES'][$propertyCode]['FILE_VALUE']) &&
                        !empty($item['DISPLAY_PROPERTIES'][$propertyCode]['FILE_VALUE'])
                    )
                    {
                        $fileValues = (
                        isset($item['DISPLAY_PROPERTIES'][$propertyCode]['FILE_VALUE']['ID']) ?
                            array(0 => $item['DISPLAY_PROPERTIES'][$propertyCode]['FILE_VALUE']) :
                            $item['DISPLAY_PROPERTIES'][$propertyCode]['FILE_VALUE']
                        );
                        foreach ($fileValues as &$oneFileValue)
                        {
							if(is_array($oneFileValue) && isset($oneFileValue['ID'])){
								$alt = ($oneFileValue["DESCRIPTION"] ? $oneFileValue["DESCRIPTION"] : ($item['IPROPERTY_VALUES']['ELEMENT_DETAIL_PICTURE_FILE_ALT'] ? $item['IPROPERTY_VALUES']['ELEMENT_DETAIL_PICTURE_FILE_ALT'] : $item["NAME"]));
								$title = ($oneFileValue["DESCRIPTION"] ? $oneFileValue["DESCRIPTION"] : ($item['IPROPERTY_VALUES']['ELEMENT_DETAIL_PICTURE_FILE_TITLE'] ? $item['IPROPERTY_VALUES']['ELEMENT_DETAIL_PICTURE_FILE_TITLE'] : $item["NAME"]));
								if(isset($item['ALT_TITLE_GET']) && $item['ALT_TITLE_GET'] == 'SEO')
								{
									$alt = ($item['IPROPERTY_VALUES']['ELEMENT_DETAIL_PICTURE_FILE_ALT'] ? $item['IPROPERTY_VALUES']['ELEMENT_DETAIL_PICTURE_FILE_ALT'] : $item["NAME"]);
									$title = ($item['IPROPERTY_VALUES']['ELEMENT_DETAIL_PICTURE_FILE_TITLE'] ? $item['IPROPERTY_VALUES']['ELEMENT_DETAIL_PICTURE_FILE_TITLE'] : $item["NAME"]);
								}
								$result[] = array(
									'ID' => (int)$oneFileValue['ID'],
									'SRC' => ($encode ? CHTTP::urnEncode($oneFileValue['SRC'], 'utf-8') : $oneFileValue['SRC']),
									'WIDTH' => (int)$oneFileValue['WIDTH'],
									'HEIGHT' => (int)$oneFileValue['HEIGHT'],
									'ALT' => $alt,
									'TITLE' => $title
								);
							}
                        } 
                        if (isset($oneFileValue))
                            unset($oneFileValue);
                    }
                    else
                    {

                        $propValues = $item['PROPERTIES'][$propertyCode]['VALUE'];
                        if (!is_array($propValues))
                            $propValues = array($propValues);

                        foreach ($propValues as &$oneValue)
                        {
                            $oneFileValue = CFile::GetFileArray($oneValue);
                            if (isset($oneFileValue['ID']))
                            {
                            	$alt = ($oneFileValue["DESCRIPTION"] ? $oneFileValue["DESCRIPTION"] : ($item['IPROPERTY_VALUES']['ELEMENT_DETAIL_PICTURE_FILE_ALT'] ? $item['IPROPERTY_VALUES']['ELEMENT_DETAIL_PICTURE_FILE_ALT'] : $item["NAME"]));
		                    	$title = ($oneFileValue["DESCRIPTION"] ? $oneFileValue["DESCRIPTION"] : ($item['IPROPERTY_VALUES']['ELEMENT_DETAIL_PICTURE_FILE_TITLE'] ? $item['IPROPERTY_VALUES']['ELEMENT_DETAIL_PICTURE_FILE_TITLE'] : $item["NAME"]));
		                    	if(isset($item['ALT_TITLE_GET']) && $item['ALT_TITLE_GET'] == 'SEO')
		                    	{
		                    		$alt = ($item['IPROPERTY_VALUES']['ELEMENT_DETAIL_PICTURE_FILE_ALT'] ? $item['IPROPERTY_VALUES']['ELEMENT_DETAIL_PICTURE_FILE_ALT'] : $item["NAME"]);
		                    		$title = ($item['IPROPERTY_VALUES']['ELEMENT_DETAIL_PICTURE_FILE_TITLE'] ? $item['IPROPERTY_VALUES']['ELEMENT_DETAIL_PICTURE_FILE_TITLE'] : $item["NAME"]);
		                    	}
                                $result[] = array(
                                    'ID' => (int)$oneFileValue['ID'],
                                    'SRC' => ($encode ? CHTTP::urnEncode($oneFileValue['SRC'], 'utf-8') : $oneFileValue['SRC']),
                                    'WIDTH' => (int)$oneFileValue['WIDTH'],
                                    'HEIGHT' => (int)$oneFileValue['HEIGHT'],
                                    'ALT' => $alt,
                          			'TITLE' => $title
                                );
                            }
                        }
                        if (isset($oneValue))
                            unset($oneValue);
                    }
                }
            }
            if(isset($item['OFFERS']) && $item['OFFERS'] && !$addDetailToSlider){
            	if(empty($result))
            		unset($item['DETAIL_PICTURE']);
            }
            if ($addDetailToSlider || empty($result))
            {

                if (!empty($item['DETAIL_PICTURE']))
                {
                    if (!is_array($item['DETAIL_PICTURE']))
                        $item['DETAIL_PICTURE'] = CFile::GetFileArray($item['DETAIL_PICTURE']);

                    if (isset($item['DETAIL_PICTURE']['ID']))
                    {
                    	$alt = ($item['DETAIL_PICTURE']['DESCRIPTION'] ? $item['DETAIL_PICTURE']['DESCRIPTION'] : ($item['DETAIL_PICTURE']['ALT'] ? $item['DETAIL_PICTURE']['ALT'] : $item['NAME'] ));
                    	$title = ($item['DETAIL_PICTURE']['DESCRIPTION'] ? $item['DETAIL_PICTURE']['DESCRIPTION'] : ($item['DETAIL_PICTURE']['TITLE'] ? $item['DETAIL_PICTURE']['TITLE'] : $item['NAME'] ));
                    	if(isset($item['ALT_TITLE_GET']) && $item['ALT_TITLE_GET'] == 'SEO')
                    	{
                    		$alt = ($item['DETAIL_PICTURE']['ALT'] ? $item['DETAIL_PICTURE']['ALT'] : $item['NAME'] );
                    		$title = ($item['DETAIL_PICTURE']['TITLE'] ? $item['DETAIL_PICTURE']['TITLE'] : $item['NAME'] );
                    	}
                    	$detailPictIds = array_column($result, 'ID');
                    	if(!in_array((int)$item['DETAIL_PICTURE']['ID'], $detailPictIds)){                    	
	                        array_unshift(
	                            $result,
	                            array(
	                                'ID' => (int)$item['DETAIL_PICTURE']['ID'],
	                                'SRC' => ($encode ? CHTTP::urnEncode($item['DETAIL_PICTURE']['SRC'], 'utf-8') : $item['DETAIL_PICTURE']['SRC']),
	                                'WIDTH' => (int)$item['DETAIL_PICTURE']['WIDTH'],
	                                'HEIGHT' => (int)$item['DETAIL_PICTURE']['HEIGHT'],
	                                'ALT' => $alt,
	                                'TITLE' => $title
	                            )
	                        );
                    	}
                    }
                    elseif($item['PICTURE'])
                    {
                    	array_unshift(
                            $result,
                            array(
                                'SRC' => $item['PICTURE'],
                                'ALT' => $item['NAME'],
                                'TITLE' => $item['NAME']
                            )
                        );
                    }
                }
            }
        }
        return $result;
    }

	public static function checkBreadcrumbsChain(&$arParams, $arSection = array(), $arElement = array()){
		global $APPLICATION;

		if(Option::get(self::moduleID, "SHOW_BREADCRUMBS_CATALOG_CHAIN", "H1", SITE_ID) == "NAME"){
			$APPLICATION->arAdditionalChain = false;
			if($arParams['INCLUDE_IBLOCK_INTO_CHAIN'] == 'Y' && isset(CMaxCache::$arIBlocksInfo[$arParams['IBLOCK_ID']]['NAME'])){
				$APPLICATION->AddChainItem(CMaxCache::$arIBlocksInfo[$arParams['IBLOCK_ID']]['NAME'], $arElement['~LIST_PAGE_URL']);
			}
			if($arParams['ADD_SECTIONS_CHAIN'] == 'Y' && $arSection){
				$rsPath = CIBlockSection::GetNavChain($arParams['IBLOCK_ID'], $arSection['ID']);
				$rsPath->SetUrlTemplates('', $arParams['SECTION_URL']);
				while($arPath = $rsPath->GetNext()){
					$APPLICATION->AddChainItem($arPath['NAME'], $arPath['~SECTION_PAGE_URL']);
				}
			}
			if($arParams['ADD_ELEMENT_CHAIN'] == 'Y' && $arElement){
				$APPLICATION->AddChainItem($arElement['NAME']);
			}
		}
	}

	public static function getShowBasket(){
		static $bShowBasket;
		if($bShowBasket === NULL)
		{
			$arFrontParametrs = self::GetFrontParametrsValues(SITE_ID);
			$bShowBasket = ($arFrontParametrs['SHOW_BASKET_ON_PAGES'] == 'Y' || ($arFrontParametrs['SHOW_BASKET_ON_PAGES'] == 'N' && (!self::IsBasketPage() && !self::IsOrderPage())));
		}
		return $bShowBasket;
	}

	public static function SetJSOptions(){
		global $APPLICATION, $STARTTIME, $arSite, $arTheme;

		$MESS['MIN_ORDER_PRICE_TEXT']=Option::get(self::moduleID, 'MIN_ORDER_PRICE_TEXT', GetMessage('MIN_ORDER_PRICE_TEXT_EXAMPLE'), SITE_ID);

		list($bPhoneAuthSupported, $bPhoneAuthShow, $bPhoneAuthRequired, $bPhoneAuthUse) = Aspro\Max\PhoneAuth::getOptions();

		$arFrontParametrs = self::GetFrontParametrsValues(SITE_ID);
		?>
		<?Bitrix\Main\Page\Frame::getInstance()->startDynamicWithID('basketitems-component-block');?>
			<?if(self::getShowBasket()):?>
				<?if($arFrontParametrs['USE_REGIONALITY'] == 'Y')
					CSaleBasket::UpdateBasketPrices(CSaleBasket::GetBasketUserID(), SITE_ID);
				?>
				<?$APPLICATION->IncludeComponent( "bitrix:sale.basket.basket.line", "actual", Array(
					"PATH_TO_BASKET" => SITE_DIR."basket/",
					"PATH_TO_ORDER" => SITE_DIR."order/",
					"SHOW_DELAY" => "Y",
					"SHOW_PRODUCTS"=>"Y",
					"SHOW_EMPTY_VALUES" => "Y",
					"SHOW_NOTAVAIL" => "N",
					"SHOW_SUBSCRIBE" => "N",
					"SHOW_IMAGE" => "Y",
					"SHOW_PRICE" => "Y",
					"SHOW_SUMMARY" => "Y",
					"SHOW_NUM_PRODUCTS" => "Y",
					"SHOW_TOTAL_PRICE" => "Y",
					"HIDE_ON_BASKET_PAGES" => "N"
				) );?>
			<?endif;?>
		<?Bitrix\Main\Page\Frame::getInstance()->finishDynamicWithID('basketitems-component-block', '');?>
		<?if($arFrontParametrs['SHOW_LICENCE'] == 'Y')
		{
			if(function_exists('file_get_contents'))
			{
				$license_text = file_get_contents(str_replace('//', '/', $_SERVER['DOCUMENT_ROOT'].SITE_DIR.'include/licenses_text.php'));
			}
			else
			{
				ob_start();
					include_once(str_replace('//', '/', $_SERVER['DOCUMENT_ROOT'].SITE_DIR.'include/licenses_text.php'));
				$license_text = ob_get_contents();
				ob_end_clean();
			}
			$MESS['LICENSES_TEXT'] = $license_text;
		}?>
		<?if($arFrontParametrs['SHOW_OFFER'] == 'Y')
		{
			if(function_exists('file_get_contents'))
			{
				$license_text = file_get_contents(str_replace('//', '/', $_SERVER['DOCUMENT_ROOT'].SITE_DIR.'include/offer_text.php'));
			}
			else
			{
				ob_start();
					include_once(str_replace('//', '/', $_SERVER['DOCUMENT_ROOT'].SITE_DIR.'include/offer_text.php'));
				$license_text = ob_get_contents();
				ob_end_clean();
			}
			$MESS['OFFER_TEXT'] = $license_text;
		}?>
		<?
		if (self::IsOrderPage()) {
			if (function_exists('file_get_contents')) {
				$required_text = file_get_contents(str_replace('//', '/', $_SERVER['DOCUMENT_ROOT'].SITE_DIR.'include/required_message.php'));
			} else {
				ob_start();
					include_once(str_replace('//', '/', $_SERVER['DOCUMENT_ROOT'].SITE_DIR.'include/required_message.php'));
				$required_text = ob_get_contents();
				ob_end_clean();
			}
			$MESS['REQUIRED_TEXT'] = $required_text;
		}
		?>
		<div class="cd-modal-bg"></div>
		<script data-skip-moving="true">var solutionName = 'arMaxOptions';</script>
		<script src="<?=SITE_TEMPLATE_PATH.'/js/setTheme.php?site_id='.SITE_ID.'&site_dir='.SITE_DIR?>" data-skip-moving="true"></script>
		<script type="text/javascript">window.onload=function(){window.basketJSParams = window.basketJSParams || [];<?if($arFrontParametrs['YANDEX_ECOMERCE'] == 'Y' || $arFrontParametrs['GOOGLE_ECOMERCE'] == 'Y'):?>window.dataLayer = window.dataLayer || [];<?endif;?>}
		BX.message(<?=CUtil::PhpToJSObject( $MESS, false )?>);
		arAsproOptions.PAGES.FRONT_PAGE = window[solutionName].PAGES.FRONT_PAGE = "<?=self::IsMainPage()?>";arAsproOptions.PAGES.BASKET_PAGE = window[solutionName].PAGES.BASKET_PAGE = "<?=self::IsBasketPage()?>";arAsproOptions.PAGES.ORDER_PAGE = window[solutionName].PAGES.ORDER_PAGE = "<?=self::IsOrderPage()?>";arAsproOptions.PAGES.PERSONAL_PAGE = window[solutionName].PAGES.PERSONAL_PAGE = "<?=self::IsPersonalPage()?>";arAsproOptions.PAGES.CATALOG_PAGE = window[solutionName].PAGES.CATALOG_PAGE = "<?=self::IsCatalogPage()?>";</script>
		<?/*fix reset POST*/
		if($_SERVER['REQUEST_METHOD'] == 'POST' && $_POST['color_theme']){
			LocalRedirect($_SERVER['HTTP_REFERER']);
		}?>
	<?}

	public static function showAddress($class = ''){
		global $arRegion, $APPLICATION;
		static $addr_call;
		$iCalledID = ++$addr_call;
		$regionID = ($arRegion ? $arRegion['ID'] : '');?>

		<?if($arRegion):?>
		<?$frame = new \Bitrix\Main\Page\FrameHelper('address-block'.$iCalledID);?>
		<?$frame->begin();?>
		<?endif;?>

			<?if($arRegion):?>
				<?if($arRegion['PROPERTY_ADDRESS_VALUE']['TEXT'] ?? ''):?>
					<div <?=($class ? 'class="'.$class.'"' : '')?>>
						<?=static::showIconSvg("addr", SITE_TEMPLATE_PATH."/images/svg/address.svg");?>
						<?=$arRegion['PROPERTY_ADDRESS_VALUE']['TEXT'];?>
					</div>
				<?endif;?>
			<?else:?>
				<div <?=($class ? 'class="'.$class.'"' : '')?>>
					<?=static::showIconSvg("addr", SITE_TEMPLATE_PATH."/images/svg/address.svg");?>
					<?$APPLICATION->IncludeFile(SITE_DIR."include/top_page/site-address.php", array(), array(
							"MODE" => "html",
							"NAME" => "Address",
							"TEMPLATE" => "include_area.php",
						)
					);?>
				</div>
			<?endif;?>

		<?if($arRegion):?>
		<?$frame->end();?>
		<?endif;?>

	<?}

	public static function showEmail($class = ''){
		global $arRegion, $APPLICATION;
		static $email_call;
		$iCalledID = ++$email_call;
		$regionID = ($arRegion ? $arRegion['ID'] : '');?>

		<?if($arRegion):?>
		<?$frame = new \Bitrix\Main\Page\FrameHelper('email-block'.$iCalledID);?>
		<?$frame->begin();?>
		<?endif;?>

			<?if($arRegion):?>
				<?if($arRegion['PROPERTY_EMAIL_VALUE']):?>
					<div <?=($class ? 'class="'.$class.'"' : '')?>>
						<?=static::showIconSvg("email", SITE_TEMPLATE_PATH."/images/svg/email_footer.svg");?>
						<?foreach($arRegion['PROPERTY_EMAIL_VALUE'] as $value):?>
							<a href="mailto:<?=$value;?>" target="_blank"><?=$value;?></a>
						<?endforeach;?>
					</div>
				<?endif;?>
			<?else:?>
				<div <?=($class ? 'class="'.$class.'"' : '')?>>
					<?=static::showIconSvg("email", SITE_TEMPLATE_PATH."/images/svg/email_footer.svg");?>
					<?$APPLICATION->IncludeFile(SITE_DIR."include/footer/site-email.php", array(), array(
							"MODE" => "html",
							"NAME" => "Address",
							"TEMPLATE" => "include_area.php",
						)
					);?>
				</div>
			<?endif;?>

		<?if($arRegion):?>
		<?$frame->end();?>
		<?endif;?>

	<?}

	public static function ShowHeaderMobilePhones($class = ''){
		static $hphones_call_m;
		global $arTheme;

		$iCalledID = ++$hphones_call_m;
		$arBackParametrs = self::GetBackParametrsValues(SITE_ID);

		if(self::GetFrontParametrValue('USE_REGIONALITY', SITE_ID, true) == 'Y'){
			global $arRegion;
		}
		else{
			$arRegion = array();
		}

		$iCountPhones = ($arRegion ? count($arRegion['PHONES']) : $arBackParametrs['HEADER_PHONES']);
		$regionId = ($arRegion ? $arRegion['ID'] : '');

		if($arRegion){
//			$frame = new \Bitrix\Main\Page\FrameHelper('header-allphones-block'.$iCalledID);
//			$frame->begin();
		}
		?>
		<?if($iCountPhones):?>
			<?

			global $USER;

			$bHaveIcons = false;
			for($i = 0; $i < $iCountPhones; ++$i){
				if($bHaveIcons = strlen($arRegion ? ($arRegion['PHONES'][$i]['ICON'] ?? '') : ($arBackParametrs['HEADER_PHONES_array_PHONE_ICON_'.$i] ?? ''))){
					break;
				}
			}
			?>
			<!-- noindex -->
			<button class="top-btn inline-phone-show">
				<?=self::showIconSvg('phone', SITE_TEMPLATE_PATH.'/images/svg/Phone.svg');?>
			</button>
			<div id="mobilePhone" class="dropdown-mobile-phone <?=($bHaveIcons ? 'with_icons' : '')?>">
				<div class="wrap">
					<div class="more_phone title"><span class="no-decript dark-color "><?=Loc::getMessage('MAX_T_MENU_CALLBACK')?> <?=static::showIconSvg("close dark dark-i", SITE_TEMPLATE_PATH."/images/svg/Close.svg");?></span></div>
					<?for($i = 0; $i < $iCountPhones; ++$i):?>
						<?

						$phone = ($arRegion ? ($arRegion['PHONES'][$i]['PHONE'] ?? '') : ($arBackParametrs['HEADER_PHONES_array_PHONE_VALUE_'.$i] ?? ''));
						$href = ($arRegion ? ($arRegion['PHONES'][$i]['HREF'] ?? '') : ($arBackParametrs['HEADER_PHONES_array_PHONE_HREF_'.$i] ?? ''));
						if(!strlen($href)){
							$href = 'javascript:;';
						}

						$icon = ($arRegion ? ($arRegion['PHONES'][$i]['ICON'] ?? '') : ($arBackParametrs['HEADER_PHONES_array_PHONE_ICON_'.$i] ?? ''));
						$icon = (strlen($icon) ? '<span class="icon">'.\Aspro\Max\Iconset::showIcon($icon, false, false).'</span>' : '');

						$description = ($arRegion ? $arRegion['PROPERTY_PHONES_DESCRIPTION'][$i] : $arBackParametrs['HEADER_PHONES_array_PHONE_DESCRIPTION_'.$i]);
						$description = (strlen($description) ? '<span class="descr">'.$description.'</span>' : '');
						?>
						<div class="more_phone">
						    <a class="dark-color <?=(empty($description)?'no-decript':'')?>" rel="nofollow" href="<?=$href?>"><?=$icon?><?=$phone?><?=$description?></a>
						</div>
					<?endfor;?>
					<?$callbackExploded = explode(',', $arTheme['SHOW_CALLBACK']['VALUE']);
					if(in_array('HEADER', $callbackExploded)):?>
						<div class="more_phone"><span class="dark-color no-decript callback animate-load" data-event="jqm" data-param-form_id="CALLBACK" data-name="callback"><?=Loc::getMessage('CALLBACK')?></span></div>
				</div>
				<?endif;?>

			</div>
			<!-- /noindex -->
		<?endif;?>
		<?
		if($arRegion){
//			$frame->end();
		}
	}

	public static function ShowHeaderPhones($class = '', $bFooter = false){
		static $hphones_call;
		global $arTheme;

		$iCalledID = ++$hphones_call;
		$arBackParametrs = self::GetBackParametrsValues(SITE_ID);

		if(self::GetFrontParametrValue('USE_REGIONALITY', SITE_ID, true) == 'Y'){
			global $arRegion;
		}
		else{
			$arRegion = array();
		}

		$iCountPhones = ($arRegion ? (isset($arRegion['PHONES'][0]['PHONE']) && $arRegion['PHONES'][0]['PHONE'] ? count($arRegion['PHONES']) : 0) : $arBackParametrs['HEADER_PHONES']);
		$regionID = ($arRegion ? $arRegion['ID'] : '');

		if($arRegion){
//			$frame = new \Bitrix\Main\Page\FrameHelper('header-allphones-block'.$iCalledID);
//			$frame->begin();
		}
		?>
		<?if($iCountPhones):?>
			<?

			$phone = ($arRegion ? $arRegion['PHONES'][0]['PHONE'] : $arBackParametrs['HEADER_PHONES_array_PHONE_VALUE_0']);
			$href = ($arRegion ? $arRegion['PHONES'][0]['HREF'] : $arBackParametrs['HEADER_PHONES_array_PHONE_HREF_0']);
			if(!strlen($href)){
				$href = 'javascript:;';
			}

			$bHaveIcons = $bHaveDescription = false;

			for($i = 0; $i < $iCountPhones; ++$i){
				if(
					( $bHaveIcons = strlen($arRegion ? $arRegion['PHONES'][$i]['ICON'] : $arBackParametrs['HEADER_PHONES_array_PHONE_ICON_'.$i]))
					|| ( $bHaveDescription = strlen($arRegion ? $arRegion['PROPERTY_PHONES_DESCRIPTION'][$i] : $arBackParametrs['HEADER_PHONES_array_PHONE_DESCRIPTION_'.$i]))
				){
					break;
				}
			}
			?>
			<!-- noindex -->
			<div class="phone with_dropdown<?=($class ? ' '.$class : '')?>">
				<?if($bFooter):?>
					<div class="wrap">
						<div>
				<?endif;?>
					<?=static::showSpriteIconSvg(SITE_TEMPLATE_PATH."/images/svg/header_icons_srite.svg".($bFooter ? "#phone_footer" : "#phone_black"), "svg-inline-phone ", ['WIDTH' => 5,'HEIGHT' => 13]);?><a rel="nofollow" href="<?=$href?>"><?=$phone?></a>
				<?if($bFooter):?>
						</div>
					</div>
				<?endif;?>
				<? if( $iCountPhones > 1 || $bHaveIcons || $bHaveDescription ): ?>
					<div class="dropdown <?=($bHaveIcons ? 'with_icons' : '')?>">
						<div class="wrap scrollblock">
							<?for($i = 0; $i < $iCountPhones; ++$i):?>
								<?
								$phone = ($arRegion ? $arRegion['PHONES'][$i]['PHONE'] : $arBackParametrs['HEADER_PHONES_array_PHONE_VALUE_'.$i]);
								$href = ($arRegion ? $arRegion['PHONES'][$i]['HREF'] : $arBackParametrs['HEADER_PHONES_array_PHONE_HREF_'.$i]);
								if(!strlen($href)){
									$href = 'javascript:;';
								}

								$icon = ($arRegion ? $arRegion['PHONES'][$i]['ICON'] : $arBackParametrs['HEADER_PHONES_array_PHONE_ICON_'.$i]);
								$icon = (strlen($icon) ? '<span class="icon">'.\Aspro\Max\Iconset::showIcon($icon, false, false).'</span>' : '');
								$description = ($arRegion ? $arRegion['PROPERTY_PHONES_DESCRIPTION'][$i] : $arBackParametrs['HEADER_PHONES_array_PHONE_DESCRIPTION_'.$i]);
								$description = (strlen($description) ? '<span class="descr">'.$description.'</span>' : '');
								?>
								<div class="more_phone"><a class="more_phone_a" rel="nofollow" <?=(strlen($description) ? '' : 'class="no-decript"')?> href="<?=$href?>"><?=$icon?><?=$phone?><?=$description?></a></div>
							<?endfor;?>
						</div>
					</div>
					<?= static::showSpriteIconSvg(SITE_TEMPLATE_PATH."/images/svg/header_icons_srite.svg#Triangle_down", "svg-inline-down ", ['WIDTH' => 5,'HEIGHT' => 3]);?>
					
				<? endif; ?>
			</div>
			<!-- /noindex -->
		<?endif;?>
		<?
		if($arRegion){
//			$frame->end();
		}
	}

	public static function showFooterPhone(){
		global $arRegion;
		static $fphones_call;

		$iCalledID = ++$fphones_call;
		$arBackParametrs = self::GetBackParametrsValues(SITE_ID);
		$iCountPhones = ($arRegion ? count($arRegion['PHONES']) : $arBackParametrs['HEADER_PHONES']);
		$regionID = ($arRegion ? $arRegion['ID'] : '');

		if($arRegion){
			$frame = new \Bitrix\Main\Page\FrameHelper('footer-allphones-block'.$iCalledID);
			$frame->begin();
		}
		?>
		<?if($iCountPhones):?>
			<?
			$phone = ($arRegion ? $arRegion['PHONES'][0]['PHONE'] : $arBackParametrs['HEADER_PHONES_array_PHONE_VALUE_0']);
			$href = ($arRegion ? $arRegion['PHONES'][0]['HREF'] : $arBackParametrs['HEADER_PHONES_array_PHONE_HREF_0']);
			if(!strlen($href)){
				$href = 'javascript:;';
			}
			?>
			<span class="phone_wrap">
				<span>
					<a href="<?=$href?>" rel="nofollow"><?=$phone?></a>
				</span>
			</span>
		<?endif;?>
		<?
		if($arRegion){
			$frame->end();
		}
	}

	public static function goto404Page(){
		global $APPLICATION;

		if($_SESSION['SESS_INCLUDE_AREAS']){
			echo '</div>';
		}
		echo '</div>';
		$APPLICATION->IncludeFile(SITE_DIR.'404.php', array(), array('MODE' => 'html'));
		die();
	}

	public static function checkAjaxRequest(){
		return (
			(
				isset($_SERVER['HTTP_X_REQUESTED_WITH']) &&
				strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest'
			) || 
			(
				isset($_REQUEST['ajax']) &&
				strtolower($_REQUEST['ajax']) == 'y'
			)
		);
	}

	public static function checkAjaxRequest2(){
		return (
			(
				isset($_SERVER['HTTP_X_REQUESTED_WITH']) &&
				strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest'
			) ||
			(
				isset($_REQUEST['ajax_get']) &&
				strtolower($_REQUEST['ajax_get']) == 'y'
			)
		);
	}

	public static function checkRequestBlock($block = ''){
		$cacheID = false;
		if ($block) {
			$context=\Bitrix\Main\Context::getCurrent();
			$request=$context->getRequest();

			if ($request->getQuery('BLOCK') == $block && $request->getQuery('PAGEN_1')) {
				$cacheID = $block.$request->getQuery('PAGEN_1');
			}
		}
		return $cacheID;
	}

	public static function checkRestartBuffer($bFront = false, $param = '', $reset = false){
		global $APPLICATION, $isIndex;
		static $bRestarted, $bFrontRestarted;

		if(!$bFront)
		{
			if($bRestarted)
				die();
		}
		else
		{
			if($bFrontRestarted && !$reset)
				die();
		}


		if(self::checkAjaxRequest())
		{
			$APPLICATION->RestartBuffer();
			if(!$bFront)
			{
				if(!$isIndex)
				{
					$bRestarted = true;
					$bFrontRestarted = false;
				}
			}
			else
			{
				if($param)
				{
					$context=\Bitrix\Main\Context::getCurrent();
					$request=$context->getRequest();

					if($request->getQuery('BLOCK') == $param)
					{
						$bRestarted = false;
						$bFrontRestarted = true;
					}
				}
				else
				{
					$bRestarted = false;
					$bFrontRestarted = true;
				}
			}
		}
	}

	public static function checkAllowDelivery($summ, $currency){
		$ERROR = false;
		$min_price = \Bitrix\Main\Config\Option::get(self::moduleID, 'MIN_ORDER_PRICE', 1000, SITE_ID);
		$error_text = '';
		if( $summ < $min_price ){
			$ERROR = true;
			$error_text = \Bitrix\Main\Config\Option::get(self::moduleID, 'MIN_ORDER_PRICE_TEXT', GetMessage('MIN_ORDER_PRICE_TEXT_EXAMPLE'));
			$error_text = str_replace( '#PRICE#', SaleFormatCurrency($min_price,$currency), $error_text );
			if($currency)
				$error_text = str_replace( '#PRICE#', SaleFormatCurrency($min_price,$currency), $error_text );
			else
				$error_text = str_replace( '#PRICE#', $min_price, $error_text );
		}
		return $arError=array('ERROR' => $ERROR, 'TEXT' => $error_text);
	}

	public static function showMoreText($text){
		$arText = explode('#MORE_TEXT#', $text);
		if($arText[1])
		{
			$str = $arText[0];
			$str .= '<div class="wrap_more_item">';
				$str .= '<div class="more_text_item">';
				$str .= $arText[1];
				$str .= '</div>';
				$str .= '<div class="open_more"><span class="text"><i class="arrow"></i><span class="pseudo">'.GetMessage("EXPAND_BLOCK").'</span></span></div>';
			$str .= '</div>';
		}
		else
		{
			$str = $text;
		}
		return $str;
	}

	public static function GetCompositeOptions(){
		if(class_exists('CHTMLPagesCache')){
			if(method_exists('CHTMLPagesCache', 'GetOptions')){
				return CHTMLPagesCache::GetOptions();
			}
		}
	
		return array();
	}

	public static function IsCompositeEnabled(){
		if($arHTMLCacheOptions = self::GetCompositeOptions()){
			if(method_exists('CHTMLPagesCache', 'isOn')){
				if (CHTMLPagesCache::isOn()){
					if(isset($arHTMLCacheOptions['AUTO_COMPOSITE']) && $arHTMLCacheOptions['AUTO_COMPOSITE'] === 'Y'){
						return 'AUTO_COMPOSITE';
					}
					else{
						return 'COMPOSITE';
					}
				}
			}
			else{
				if($arHTMLCacheOptions['COMPOSITE'] === 'Y'){
					return 'COMPOSITE';
				}
			}
		}
	
		return false;
	}
	
	public static function EnableComposite($auto = false, $arHTMLCacheOptions = array()){
		if(class_exists('CHTMLPagesCache')){
			if(method_exists('CHTMLPagesCache', 'GetOptions')){
				$arHTMLCacheOptions = is_array($arHTMLCacheOptions) ? $arHTMLCacheOptions : array();
				$arHTMLCacheOptions = array_merge(CHTMLPagesCache::GetOptions(), $arHTMLCacheOptions);
	
				$arHTMLCacheOptions['COMPOSITE'] = $arHTMLCacheOptions['COMPOSITE'] ?? 'Y';
				$arHTMLCacheOptions['AUTO_UPDATE'] = $arHTMLCacheOptions['AUTO_UPDATE'] ?? 'Y'; // standart mode
				$arHTMLCacheOptions['AUTO_UPDATE_TTL'] = $arHTMLCacheOptions['AUTO_UPDATE_TTL'] ?? '0'; // no ttl delay
				$arHTMLCacheOptions['AUTO_COMPOSITE'] = ($auto ? 'Y' : 'N'); // auto composite mode
	
				CHTMLPagesCache::SetEnabled(true);
				CHTMLPagesCache::SetOptions($arHTMLCacheOptions);
				bx_accelerator_reset();
			}
		}
	}

	public static function CopyFaviconToSiteDir($arValue, $siteID = ''){
		if(($siteID)){
			if(is_string($arValue) && $arValue) {
				$arValue = unserialize($arValue);
			}

			if(isset($arValue[0]) && $arValue[0]){
				$imageSrc = $_SERVER['DOCUMENT_ROOT'].CFile::GetPath($arValue[0]);
			}
			else{
				if($arTemplate = self::GetSiteTemplate($siteID)){
					$imageSrc = preg_replace('@/+@', '/', $arTemplate['PATH'].'/images/favicon.ico');
				}
			}

			$arSite = CSite::GetByID($siteID)->Fetch();

			
			if(!file_exists($imageSrc)){
				$imageSrc = preg_replace('@/+@', '/', $arSite['ABS_DOC_ROOT'].'/'.$arSite['DIR'].'/include/favicon.ico');
			}

			if(file_exists($imageSrc)){
				
				$file_ext = pathinfo($imageSrc, PATHINFO_EXTENSION);
				$fav_ext = $file_ext ? $file_ext : 'ico';				

				$imageDest = preg_replace('@/+@', '/', $arSite['ABS_DOC_ROOT'].'/'.$arSite['DIR'].'/favicon.'.$fav_ext);

				if(file_exists($imageDest)){
					if(sha1_file($imageSrc) == sha1_file($imageDest)){
						return;
					}
				}

				$arFavExtFiles = array('ico', 'png', 'gif', 'bmp', 'jpg', 'svg');
				foreach( $arFavExtFiles as $unlinkExt){
					$imageUnlink = preg_replace('@/+@', '/', $arSite['ABS_DOC_ROOT'].'/'.$arSite['DIR'].'/favicon.'.$unlinkExt);
					if(file_exists($imageUnlink)){
						@unlink($imageUnlink);
					}
				}

				//@unlink($imageDest);
				@copy($imageSrc, $imageDest);
			}
		}
	}

	public static function GetSiteTemplate($siteID = ''){
		static $arCache;
		$arTemplate = array();

		if(strlen($siteID)){
			if(!isset($arCache)){
				$arCache = array();
			}

			if(!isset($arCache[$siteID])){
				$dbRes = CSite::GetTemplateList($siteID);
				while($arTemplate = $dbRes->Fetch()){
					if(!strlen($arTemplate['CONDITION'])){
						if(file_exists(($arTemplate['PATH'] = $_SERVER['DOCUMENT_ROOT'].'/bitrix/templates/'.$arTemplate['TEMPLATE']))){
							$arTemplate['DIR'] = '/bitrix/templates/'.$arTemplate['TEMPLATE'];
							break;
						}
						elseif(file_exists(($arTemplate['PATH'] = $_SERVER['DOCUMENT_ROOT'].'/local/templates/'.$arTemplate['TEMPLATE']))){
							$arTemplate['DIR'] = '/local/templates/'.$arTemplate['TEMPLATE'];
							break;
						}
					}
				}

				if($arTemplate){
					$arCache[$siteID] = $arTemplate;
				}
			}
			else{
				$arTemplate = $arCache[$siteID];
			}
		}

		return $arTemplate;
	}

	public static function __AdmSettingsDrawCustomRow($html){
		echo '<tr><td colspan="2">'.$html.'</td></tr>';
	}

	public static function __ShowFilePropertyField($name, $arOption, $values){
		global $bCopy, $historyId;

		CModule::IncludeModule('fileman');

		if(!is_array($values)){
			$values = array($values);
		}

		if($bCopy || empty($values)){
			$values = array('n0' => 0);
		}

		$optionWidth = isset($arOption['WIDTH']) && $arOption['WIDTH'] ? $arOption['WIDTH'] : 200;
		$optionHeight = isset($arOption['HEIGHT']) && $arOption['HEIGHT'] ? $arOption['HEIGHT'] : 100;
		$arOption['WITH_DESCRIPTION'] = $arOption['WITH_DESCRIPTION'] ?? 'N';

		if($arOption['MULTIPLE'] == 'N'){
			foreach($values as $key => $val){
				if(is_array($val)){
					$file_id = $val['VALUE'];
				}
				else{
					$file_id = $val;
				}
				if($historyId > 0){
					echo CFileInput::Show($name.'['.$key.']', $file_id,
						array(
							'IMAGE' => $arOption['IMAGE'] ?? false,
							'PATH' => 'Y',
							'FILE_SIZE' => 'Y',
							'DIMENSIONS' => 'Y',
							'IMAGE_POPUP' => 'Y',
							'MAX_SIZE' => array(
								'W' => $optionWidth,
								'H' => $optionHeight,
							),
						)
					);
				}
				else{
					echo CFileInput::Show($name.'['.$key.']', $file_id,
						array(
							'IMAGE' => $arOption['IMAGE'] ?? false,
							'PATH' => 'Y',
							'FILE_SIZE' => 'Y',
							'DIMENSIONS' => 'Y',
							'IMAGE_POPUP' => 'Y',
							'MAX_SIZE' => array(
							'W' => $optionWidth,
							'H' => $optionHeight,
							),
						),
						array(
							'upload' => true,
							'medialib' => true,
							'file_dialog' => true,
							'cloud' => true,
							'del' => true,
							'description' => $arOption['WITH_DESCRIPTION'] == 'Y',
						)
					);
				}
				break;
			}
		}
		else{
			$inputName = array();
			foreach($values as $key => $val){
				if(is_array($val)){
					$inputName[$name.'['.$key.']'] = $val['VALUE'];
				}
				else{
					$inputName[$name.'['.$key.']'] = $val;
				}
			}
			if($historyId > 0){
				echo CFileInput::ShowMultiple($inputName, $name.'[n#IND#]',
					array(
						'IMAGE' => $arOption['IMAGE'],
						'PATH' => 'Y',
						'FILE_SIZE' => 'Y',
						'DIMENSIONS' => 'Y',
						'IMAGE_POPUP' => 'Y',
						'MAX_SIZE' => array(
							'W' => $optionWidth,
							'H' => $optionHeight,
						),
					),
				false);
			}
			else{
				echo CFileInput::ShowMultiple($inputName, $name.'[n#IND#]',
					array(
						'IMAGE' => $arOption['IMAGE'],
						'PATH' => 'Y',
						'FILE_SIZE' => 'Y',
						'DIMENSIONS' => 'Y',
						'IMAGE_POPUP' => 'Y',
						'MAX_SIZE' => array(
							'W' => $optionWidth,
							'H' => $optionHeight,
						),
					),
				false,
					array(
						'upload' => true,
						'medialib' => true,
						'file_dialog' => true,
						'cloud' => true,
						'del' => true,
						'description' => $arOption['WITH_DESCRIPTION'] == 'Y',
					)
				);
			}
		}
	}

	public static function GetItemsYear($arParams){
    	$arResult = array();
    	$arItems = CMaxCache::CIBLockElement_GetList(array('SORT' => 'ASC', 'NAME' => 'ASC', 'CACHE' => array('TAG' => CMaxCache::GetIBlockCacheTag($arParams['IBLOCK_ID']))), array('IBLOCK_ID' => $arParams['IBLOCK_ID'], 'ACTIVE' => 'Y'), false, false, array('ID', 'NAME', 'ACTIVE_FROM'));
		if($arItems)
		{
			foreach($arItems as $arItem)
			{
				if($arItem['ACTIVE_FROM'])
				{
					if($arDateTime = ParseDateTime($arItem['ACTIVE_FROM'], FORMAT_DATETIME))
						$arResult[$arDateTime['YYYY']] = $arDateTime['YYYY'];
				}
			}
		}
		return $arResult;
    }

	public static function GetYearsItems($iblock_id){
		$arYears=array();
		$rsItems=CIBlockElement::GetList(array(), array('IBLOCK_ID' => $iblock_id, 'ACTIVE' => 'Y', 'GLOBAL_ACTIVE' => 'Y'), false, false, array('ID', 'DATE_ACTIVE_FROM'));
		while($arItem=$rsItems->Fetch()){
			if($arItem['DATE_ACTIVE_FROM']){
				$date = explode(' ', $arItem['DATE_ACTIVE_FROM']);
				$date = $date[0];
				$date = explode('.', $date);
				$arYears[$date[2]] = $date[2];
			}
		}
		return $arYears;
	}

	public static function GetItemStickers($arItemStickerProperty = array(), $siteID = false){
		static $type;

		if(!isset($type)){
			$type = Option::get(self::moduleID, 'ITEM_STICKER_CLASS_SOURCE', 'PROPERTY_VALUE', ($siteID ? $siteID : SITE_ID));
		}

		$arStickers = array();
		if($arItemStickerProperty && is_array($arItemStickerProperty) && array_key_exists('VALUE', $arItemStickerProperty) && $arItemStickerProperty['VALUE']){
			if(!is_array($arItemStickerProperty['VALUE'])){
				$arItemStickerProperty['VALUE'] = array($arItemStickerProperty['VALUE']);
				$arItemStickerProperty['VALUE_XML_ID'] = array($arItemStickerProperty['VALUE_XML_ID']);
			}

			foreach($arItemStickerProperty['VALUE'] as $i => $value){
				$arStickers[] = array(
					'VALUE' => $value,
					'CLASS' => 'sticker_'.($type === 'PROPERTY_VALUE' ? CUtil::translit($value, 'ru') : strtolower($arItemStickerProperty['VALUE_XML_ID'][$i])),
				);
			}
		}

		return $arStickers;
	}

	public static function removeDirectory($dir){
		if($objs = glob($dir."/*")){
			foreach($objs as $obj){
				if(is_dir($obj)){
					self::removeDirectory($obj);
				}
				else{
					if(!unlink($obj)){
						if(chmod($obj, 0777)){
							unlink($obj);
						}
					}
				}
			}
		}
		if(!rmdir($dir)){
			if(chmod($dir, 0777)){
				rmdir($dir);
			}
		}
	}

    public static function inputClean($input, $sql = false){
        return $input;
    }

    public static function getBasketCounters(){
    	global $USER, $arTheme;
    	$USER_ID = ($USER_ID = self::GetUserID()) ? $USER_ID : 0;
    	$arResult = false;

    	if(isset($_SESSION['ASPRO_BASKET_COUNTERS']))
    	{
    		if(!is_array($_SESSION['ASPRO_BASKET_COUNTERS']) || (is_array($_SESSION['ASPRO_BASKET_COUNTERS']) && count($_SESSION['ASPRO_BASKET_COUNTERS']) && !isset($_SESSION['ASPRO_BASKET_COUNTERS'][$USER_ID])))
    		{
    			unset($_SESSION['ASPRO_BASKET_COUNTERS']);
    		}
    		else
    		{
		    	$arResult = $_SESSION['ASPRO_BASKET_COUNTERS'][$USER_ID];
    		}
    	}

    	if(!$arResult || !is_array($arResult))
    	{
    		// set default value
    		$arResult = array('READY' => array('COUNT' => 0, 'TITLE' => '', 'HREF' => $arTheme['BASKET_PAGE_URL']), 'DELAY' => array('COUNT' => 0, 'TITLE' => '', 'HREF' => $arTheme['BASKET_PAGE_URL']), 'COMPARE' => array('COUNT' => 0, 'TITLE' => Loc::getMessage('COMPARE_BLOCK'), 'HREF' => $arTheme['COMPARE_PAGE_URL']), 'DEFAULT' => true);
    	}

    	$_SESSION['ASPRO_BASKET_COUNTERS'] = array($USER_ID => $arResult);
    	return $arResult;
    }

    public static function clearFormatPrice($price){
    	$strPrice = '';
    	if($price)
    	{
    		$arPrice = array();
	    	preg_match('/<span class=\'price_value\'>(.+?)<\/span>/is', $price, $arVals);
			if($arVals[1])
				$arPrice[] = $arVals[1];
			preg_match('/<span class=\'price_currency\'>(.+?)<\/span>/is', $price, $arVals);

			if($arVals[1])
				$arPrice[] = $arVals[1];
			if($arPrice)
				$strPrice = implode('', $arPrice);
			else
				$strPrice = $price;
    	}
    	return $strPrice;
    }

    public static function updateBasketCounters($arValue){
    	global $USER;
    	$USER_ID = ($USER_ID = self::GetUserID()) ? $USER_ID : 0;

    	$arResult = self::getBasketCounters();
    	if($arValue && is_array($arValue)){
    		$arResult = array_replace_recursive($arResult, $arValue);
    	}
    	$arResult['DEFAULT'] = false;

    	$_SESSION['ASPRO_BASKET_COUNTERS'] = array($USER_ID => $arResult);
    	return $arResult;
    }

    public static function clearBasketCounters(){
    	unset($_SESSION['ASPRO_BASKET_COUNTERS']);
    }

	public static function newAction($action = "unknown"){
	}

	public static function AddMeta($arParams = array()){
		self::$arMetaParams = array_merge((array)self::$arMetaParams, (array)$arParams);
	}

	public static function SetMeta(){
		global $APPLICATION, $arSite, $arRegion;

		$PageH1 = $APPLICATION->GetTitle();
		$PageMetaTitleBrowser = $APPLICATION->GetPageProperty('title');
		$DirMetaTitleBrowser = $APPLICATION->GetDirProperty('title');
		$PageMetaDescription = $APPLICATION->GetPageProperty('description');
		$DirMetaDescription = $APPLICATION->GetDirProperty('description');

		if(self::isSmartSeoInstalled() && class_exists('\Aspro\Smartseo\General\Smartseo')) {
			if($smartseoSeoProperties = \Aspro\Smartseo\General\Smartseo::getCurrentSeoProperty()) {
			  self::$arMetaParams['og:title'] = $smartseoSeoProperties['META_TITLE'];
			  self::$arMetaParams['og:description'] = $smartseoSeoProperties['META_DESCRIPTION'];
			  self::$arMetaParams['og:url'] = \Aspro\Smartseo\General\Smartseo::getCurrentData('URL_NEW');
			}
	  
			if($smartseoCurrentData = \Aspro\Smartseo\General\Smartseo::getCurrentData()) {
			  self::$arMetaParams['og:url'] = $smartseoCurrentData['URL_NEW'];
			}
		}
		  
		// set title
		if(!static::IsMainPage())
		{
			if(!strlen($PageMetaTitleBrowser))
			{
				if(!strlen($DirMetaTitleBrowser))
					$PageMetaTitleBrowser = $PageH1.((strlen($PageH1) && strlen($arSite['SITE_NAME'])) ? ' - ' : '' ).$arSite['SITE_NAME'];
			}
		}
		else
		{
			if(!strlen($PageMetaTitleBrowser))
			{
				if(!strlen($DirMetaTitleBrowser))
					$PageMetaTitleBrowser = $arSite['SITE_NAME'].((strlen($arSite['SITE_NAME']) && strlen($PageH1)) ? ' - ' : '' ).$PageH1;
			}
		}

		// check Open Graph required meta properties
		$addr = (CMain::IsHTTPS() ? 'https' : 'http').'://'.$_SERVER['HTTP_HOST'];
		if(!strlen(self::$arMetaParams['og:title'] ?? ''))
			self::$arMetaParams['og:title'] = $PageMetaTitleBrowser;
		if(!strlen(self::$arMetaParams['og:type'] ?? ''))
			self::$arMetaParams['og:type'] = 'website';
		if(!strlen(self::$arMetaParams['og:image'] ?? ''))
		{
			$logo = self::GetFrontParametrValue("LOGO_IMAGE", SITE_ID, false);

			if($logo)
				self::$arMetaParams['og:image'] = $logo;
			elseif(file_exists(str_replace('//', '/', $_SERVER['DOCUMENT_ROOT'].SITE_DIR.'logo.svg')))
				self::$arMetaParams['og:image'] = SITE_DIR.'logo.svg';// site logo
			else
				self::$arMetaParams['og:image'] = SITE_DIR.'logo.png'; // site logo
		}
		if(!strlen(self::$arMetaParams['og:url'] ?? ''))
			self::$arMetaParams['og:url'] = $_SERVER['REQUEST_URI'];
		if(!strlen(self::$arMetaParams['og:description'] ?? ''))
			self::$arMetaParams['og:description'] = (strlen($PageMetaDescription) ? $PageMetaDescription : $DirMetaDescription);

		if(self::$arMetaParams['og:description'])
		{
			$obParser = new CTextParser;
			self::$arMetaParams['og:description'] = $obParser->html_cut(self::$arMetaParams['og:description'], 300);
		}

		foreach(self::$arMetaParams as $metaName => $metaValue)
		{
			if(strlen($metaValue = strip_tags($metaValue)))
			{
				$metaValue = str_replace('//', '/', $metaValue);
				if($metaName === 'og:image' || $metaName === 'og:url'){
					$metaValue = $addr.$metaValue;
				}
				$metaValue = str_replace('#REGION_TAG_', '#REGION_STRIP_TAG_', $metaValue);
				$APPLICATION->AddHeadString('<meta property="'.$metaName.'" content="'.$metaValue.'" />', true);
				if($metaName === 'og:image'){
					$APPLICATION->AddHeadString('<link rel="image_src" href="'.$metaValue.'"  />', true);
				}
			}
		}
	}

	public static function setRegionSeoMarks(){
		global $arRegion;
		if ($arRegion) {
			$arTagSeoMarks = array();
			foreach ($arRegion as $key => $value) {
				if (strpos($key, 'PROPERTY_REGION_TAG') !== false && strpos($key, '_VALUE_ID') === false) {
					$tag_name = str_replace(array('PROPERTY_', '_VALUE'), '', $key);
					$arTagSeoMarks['#'.$tag_name.'#'] = $key;
				}
			}

			if ($arTagSeoMarks) {
				CMaxRegionality::addSeoMarks($arTagSeoMarks);
			}
		}
	}

	public static function getViewedProducts($userID=false, $siteID=false){
		global $arTheme, $STARTTIME;
		$arResult = array();
		$siteID = $siteID ? $siteID : SITE_ID;
		$localKey = 'MAX_VIEWED_ITEMS_'.$siteID;

		if($IsViewedTypeLocal = ($arTheme['VIEWED_TYPE']['VALUE'] === 'LOCAL'))
		{
			$arViewed = (isset($_COOKIE[$localKey]) && strlen($_COOKIE[$localKey])) ? json_decode($_COOKIE[$localKey], true) : array();

			if($arViewed && is_array($arViewed))
			{
				$viewedDays = Option::get("sale", "viewed_time", "5");
				$viewedCntMax = Option::get("sale", "viewed_count", "10");
				$DIETIME = $STARTTIME - $viewedDays * 86400000;

				// delete old items
				foreach($arViewed as $ID => $arItem)
				{
					if($arItem[0] < $DIETIME)
					{
						unset($arViewed[$ID]);
						continue;
					}

					$arResult[$ID] = $arItem[0];
				}

				// sort by ACTIVE_FROM
				arsort($arResult);

				// make IDs array
				$arResult = array_keys($arResult);

				// only $viewedCntMax items
				$arResult = array_slice($arResult, 0, $viewedCntMax);
			}
		}
		else
		{
			\Bitrix\Main\Loader::includeModule('sale');
			\Bitrix\Main\Loader::includeModule('catalog');
			$userID = $userID ? $userID : (int)CSaleBasket::GetBasketUserID(false);

			$viewedIterator = \Bitrix\Catalog\CatalogViewedProductTable::GetList(array(
				'select' => array('PRODUCT_ID', 'ELEMENT_ID'),
				'filter' => array('=FUSER_ID' => $userID, '=SITE_ID' => $siteID),
				'order' => array('DATE_VISIT' => 'DESC'),
				'limit' => 8
			));
			while($viewedProduct = $viewedIterator->fetch())
			{
				$viewedProduct['ELEMENT_ID'] = (int)$viewedProduct['ELEMENT_ID'];
				$viewedProduct['PRODUCT_ID'] = (int)$viewedProduct['PRODUCT_ID'];
				$arResult[$viewedProduct['PRODUCT_ID']] = $viewedProduct['ELEMENT_ID'];
			}
		}

		return $arResult;
	}

	public static function setFooterTitle(){
		global $APPLICATION, $arSite, $arRegion;

		$bShowSiteName = (\Bitrix\Main\Config\Option::get(self::moduleID, "HIDE_SITE_NAME_TITLE", "N") == "N");
		$sPostfix = ($bShowSiteName ? ' - '.$arSite['SITE_NAME'] : '');

		if($arRegion)
		{
			CMaxRegionality::addSeoMarks(array('#REGION_ID#' => 'ID'));
			CMaxRegionality::replaceSeoMarks();
		}
		else
		{
			if(strlen($APPLICATION->GetPageProperty('title')) > 1)
				$title = $APPLICATION->GetPageProperty('title');
			else
				$title = $APPLICATION->GetTitle();

			if(!static::IsMainPage())
			{
				$APPLICATION->SetPageProperty("title", $title.$sPostfix);
			}
			else
			{
				if(!empty($title))
					$APPLICATION->SetPageProperty("title", $title);
				else
					$APPLICATION->SetPageProperty("title", $arSite['SITE_NAME']);
			}
		}
		self::SetMeta();

		if(!defined('ADMIN_SECTION') && isset($_REQUEST['auth_service_id']) && $_REQUEST['auth_service_id'])
		{
			if($_REQUEST['auth_service_id']):
				global $APPLICATION, $CACHE_MANAGER;?>
				<?$APPLICATION->IncludeComponent(
					"bitrix:system.auth.form",
					"popup",
					array(
						"PROFILE_URL" => "",
						"SHOW_ERRORS" => "Y",
						"POPUP_AUTH" => "Y"
					)
				);?>
			<?endif;?>
		<?}
	}

	public static function deleteBasketServices($arParentId){
		//services delete
		$arAllBasketItems=array();
		
		$dbRes = CSaleBasket::GetList(array("NAME" => "ASC", "ID" => "ASC"), array("FUSER_ID" => CSaleBasket::GetBasketUserID(), "LID" => SITE_ID, "ORDER_ID" => NULL), false, false, array("ID", "PRODUCT_ID", "DELAY"));
		while($item = $dbRes->Fetch())
			$arAllBasketItems[] = $item;

		//buy_services
		$basketItemsById = array_column($arAllBasketItems, NULL, 'ID');
		if(count($basketItemsById) > 0){
			$db_res_props = CSaleBasket::GetPropsList( array("SORT" => "ASC",  "NAME" => "ASC"), array("BASKET_ID" => array_keys($basketItemsById), "CODE" => "ASPRO_BUY_PRODUCT_ID"));
			while($itemServ = $db_res_props->Fetch()){
				if( in_array($itemServ["VALUE"], $arParentId) ){
					CSaleBasket::Delete($itemServ["BASKET_ID"]);	
				}
			}
		}
	}

	public static function getBasketItems($iblockID=0, $field="PRODUCT_ID", $bCount = false){
		$basket_items = $delay_items = $subscribe_items = $not_available_items = array();
		$arItems = array();
		// static $arItems;
		if(self::IsMainPage())
			$arSubscribeList = false;
		// if($arItems === NULL)
		// {
			$bUseSubscribeManager = ($arSubscribeList = self::getUserSubscribeList()) !== false;
			if(\Bitrix\Main\Loader::includeModule("sale"))
			{
				$arBasketItems=array();
				$dbRes = CSaleBasket::GetList(array("NAME" => "ASC", "ID" => "ASC"), array("FUSER_ID" => CSaleBasket::GetBasketUserID(), "LID" => SITE_ID, "ORDER_ID" => NULL), false, false, array("ID", "PRODUCT_ID", "DELAY", "SUBSCRIBE", "CAN_BUY", "TYPE", "SET_PARENT_ID", 'QUANTITY'));
				while($item = $dbRes->Fetch())
					$arBasketItems[] = $item;

				//buy_services
				$basketItemsById = array_column($arBasketItems, NULL, 'ID');
				$arBasketServices = $arExcludeServivcesIds = array();
				if(count($basketItemsById) > 0){
					$db_res_props = CSaleBasket::GetPropsList( array("SORT" => "ASC",  "NAME" => "ASC"), array("BASKET_ID" => array_keys($basketItemsById), "CODE" => "ASPRO_BUY_PRODUCT_ID"));
					while($itemServ = $db_res_props->Fetch()){
						$arBasketServices[ $basketItemsById[ $itemServ["BASKET_ID"] ]["PRODUCT_ID"].'_'.$itemServ["VALUE"] ] = array(
							'item_id' => $basketItemsById[ $itemServ["BASKET_ID"] ][$field],
							'link_id' => $itemServ["VALUE"],
							'quantity' => $basketItemsById[ $itemServ["BASKET_ID"] ]['QUANTITY'],
							'basket_id' => $itemServ["BASKET_ID"],
						);
						$arExcludeServivcesIds[] = $itemServ["BASKET_ID"];
					}
				}

				global $compare_items;
				if(!is_array($compare_items))
				{
					$compare_items = array();
					$iblockID=((isset($iblockID) && $iblockID) ? $iblockID : \Bitrix\Main\Config\Option::get(self::moduleID, "CATALOG_IBLOCK_ID", CMaxCache::$arIBlocks[SITE_ID]['aspro_max_catalog']['aspro_max_catalog'][0], SITE_ID ));
					if($iblockID && isset($_SESSION["CATALOG_COMPARE_LIST"][$iblockID]["ITEMS"]))
						$compare_items = array_keys($_SESSION["CATALOG_COMPARE_LIST"][$iblockID]["ITEMS"]);

				}

				if($arBasketItems && !$bCount)
				{
					foreach($arBasketItems as $arBasketItem)
					{
						if(CSaleBasketHelper::isSetItem($arBasketItem)) // set item
							continue;
							
						if(in_array($arBasketItem['ID'] ,$arExcludeServivcesIds))
							continue;

						if($arBasketItem["DELAY"]=="N" && $arBasketItem["CAN_BUY"] == "Y" && $arBasketItem["SUBSCRIBE"] == "N")
							$basket_items[] = $arBasketItem[$field];
						elseif($arBasketItem["DELAY"]=="Y" && $arBasketItem["CAN_BUY"] == "Y" && $arBasketItem["SUBSCRIBE"] == "N")
							$delay_items[] = $arBasketItem[$field];
						elseif($arBasketItem["SUBSCRIBE"]=="Y")
							$subscribe_items[] = $arBasketItem[$field];
						else
							$not_available_items[] = $arBasketItem[$field];
					}
				}

				if($arBasketItems && $bCount) {
					foreach($arBasketItems as $arBasketItem)
					{
						if(CSaleBasketHelper::isSetItem($arBasketItem)) // set item
							continue;
						
						if(in_array($arBasketItem['ID'] ,$arExcludeServivcesIds))
							continue;

						if($arBasketItem["DELAY"]=="N" && $arBasketItem["CAN_BUY"] == "Y" && $arBasketItem["SUBSCRIBE"] == "N")
							$basket_items[] = array(
								$field => $arBasketItem[$field],
								'QUANTITY' => $arBasketItem['QUANTITY'],
							);
						elseif($arBasketItem["DELAY"]=="Y" && $arBasketItem["CAN_BUY"] == "Y" && $arBasketItem["SUBSCRIBE"] == "N")
							$delay_items[] = $arBasketItem[$field];
						elseif($arBasketItem["SUBSCRIBE"]=="Y")
							$subscribe_items[] = $arBasketItem[$field];
						else
							$not_available_items[] = $arBasketItem[$field];
					}
				}

				$arItems["BASKET"]=array_combine($basket_items, $basket_items);
				$arItems["DELAY"]=array_combine($delay_items, $delay_items);
				$arItems["SUBSCRIBE"]=array_combine($subscribe_items, $subscribe_items);
				$arItems["NOT_AVAILABLE"]=array_combine($not_available_items, $not_available_items);
				$arItems["COMPARE"]=array_combine($compare_items, $compare_items);
				$arItems["SERVICES"]=$arBasketServices;
			}

			if($bUseSubscribeManager && $arSubscribeList)
			{
				foreach($arSubscribeList as $PRODUCT_ID => $arIDs)
					$arItems['SUBSCRIBE'][$PRODUCT_ID] = $PRODUCT_ID;
			}
		// }

		return $arItems;
	}

	public static function getUserSubscribeList($userId = false){
		if(\Bitrix\Main\Loader::includeModule('catalog'))
		{
			if(class_exists('\Bitrix\Catalog\Product\SubscribeManager'))
			{
				global $USER, $DB;
				$userId = $userId ? intval($userId) : (($USER && is_object($USER) && $USER->isAuthorized()) ? self::GetUserID() : false);
				$bSubscribeProducts = (isset($_SESSION['SUBSCRIBE_PRODUCT']['LIST_PRODUCT_ID']) && $_SESSION['SUBSCRIBE_PRODUCT']['LIST_PRODUCT_ID']);

				if($userId || $bSubscribeProducts)
				{
					$arSubscribeList = array();
					$subscribeManager = new \Bitrix\Catalog\Product\SubscribeManager;

					$filter = array(
						'USER_ID' => $userId,
						'=SITE_ID' => SITE_ID,
						array(
							'LOGIC' => 'OR',
							array('=DATE_TO' => false),
							array('>DATE_TO' => date($DB->dateFormatToPHP(\CLang::getDateFormat('FULL')), time()))
						),
					);

					$resultObject = \Bitrix\Catalog\SubscribeTable::getList(
						array(
							'select' => array(
								'ID',
								'ITEM_ID',
							),
							'filter' => $filter,
						)
					);

					while($arItem = $resultObject->fetch())
					{
						$arSubscribeList[$arItem['ITEM_ID']][] = $arItem['ID'];
					}
					if(!$userId && $bSubscribeProducts)
					{
						foreach($arSubscribeList as $key => $id)
						{
							if(!$_SESSION['SUBSCRIBE_PRODUCT']['LIST_PRODUCT_ID'][$key])
								unset($arSubscribeList[$key]);
						}
					}

					return $arSubscribeList;
				}
			}
		}

		return false;
	}

	public static function showFooterBasket(){
		global $arTheme, $APPLICATION, $arRegion;
		if($arRegion)
		{
			CSaleBasket::UpdateBasketPrices(CSaleBasket::GetBasketUserID(), SITE_ID);
		}
		Bitrix\Main\Page\Frame::getInstance()->startDynamicWithID('basketitems-block');

		$arItems=self::getBasketItems();?>

		<?//if(self::IsBasketPage()):?>
			<?if(\Bitrix\Main\Loader::includeModule('currency'))
			{
				CJSCore::Init(array('currency'));
				$currencyFormat = CCurrencyLang::GetFormatDescription(CSaleLang::GetLangCurrency(SITE_ID));
			}
			?>
			<script type="text/javascript">
				<?if(is_array($currencyFormat)):?>
					function jsPriceFormat(_number){
						BX.Currency.setCurrencyFormat('<?=CSaleLang::GetLangCurrency(SITE_ID);?>', <? echo CUtil::PhpToJSObject($currencyFormat, false, true); ?>);
						return BX.Currency.currencyFormat(_number, '<?=CSaleLang::GetLangCurrency(SITE_ID);?>', true);
					}
				<?endif;?>
			</script>
		<?//endif;?>
		<script type="text/javascript">
			var arBasketAspro = <? echo CUtil::PhpToJSObject($arItems, false, true); ?>;
			$(document).ready(function(){
				setBasketStatusBtn();
			});
		</script>
		<?Bitrix\Main\Page\Frame::getInstance()->finishDynamicWithID('basketitems-block', '');?>
		<?$basketType = (isset($arTheme['ORDER_BASKET_VIEW']['VALUE']) ? $arTheme['ORDER_BASKET_VIEW']['VALUE'] : $arTheme['ORDER_BASKET_VIEW']);?>
		<?if($basketType == 'BOTTOM'):?>
			<?if(self::getShowBasket()):?>
				<div class="basket_bottom_block basket_fill_<?=$arTheme['ORDER_BASKET_COLOR']['VALUE'];?>">
					<?$APPLICATION->IncludeComponent("bitrix:main.include", ".default",
						array(
							"COMPONENT_TEMPLATE" => ".default",
							"PATH" => SITE_DIR."include/footer/comp_basket_bottom.php",
							"AREA_FILE_SHOW" => "file",
							"AREA_FILE_SUFFIX" => "",
							"AREA_FILE_RECURSIVE" => "Y",
							"EDIT_TEMPLATE" => "standard.php"
						),
						false
					);?>
				</div>
			<?endif;?>
			<?=\Aspro\Functions\CAsproMax::showSideFormLinkIcons(true)?>
		<?elseif($basketType != 'NORMAL'):?>
			<div class="basket_wrapp <?=(self::IsBasketPage() ? 'basket_page' : '');?> <?=strtolower($basketType);?> basket_fill_<?=$arTheme['ORDER_BASKET_COLOR']['VALUE'];?>">
				<div class="header-cart fly" id="basket_line">
					<?$APPLICATION->IncludeComponent("bitrix:main.include", ".default",
						array(
							"COMPONENT_TEMPLATE" => ".default",
							"PATH" => SITE_DIR."include/top_page/comp_basket_top.php",
							"AREA_FILE_SHOW" => "file",
							"AREA_FILE_SUFFIX" => "",
							"AREA_FILE_RECURSIVE" => "Y",
							"EDIT_TEMPLATE" => "standard.php"
						),
						false
					);?>
				</div>
			</div>
		<?else:?>
			<?=\Aspro\Functions\CAsproMax::showSideFormLinkIcons(true)?>
		<?endif;?>

		<?//bottom panel icons?>
		<?Bitrix\Main\Page\Frame::getInstance()->startDynamicWithID('bottom-panel-block');?>
		<?$APPLICATION->AddHeadString('<script src="'.SITE_TEMPLATE_PATH.'/js/fetch/bottom_panel.js" data-skip-moving="true" defer=""></script>');?>
		<?Bitrix\Main\Page\Frame::getInstance()->finishDynamicWithID('bottom-panel-block', '');?>

		<?//appAspro?>
		<?if (self::IsOrderPage()):?>
			<?$arScripts = ['phone_input']?>
			<?if (self::GetFrontParametrValue('USE_INTL_PHONE') === 'Y'):?>
				<?$arScripts[] = 'intl_phone_input'?>
			<?elseif (self::GetFrontParametrValue('PHONE_MASK')):?>
				<?$arScripts[] = 'phone_mask'?>
			<?endif;?>

			<?\Aspro\Max\Functions\Extensions::init($arScripts);?>
			<script type="text/javascript">
				if (typeof appAspro === 'object' && appAspro && appAspro.phone && !appAspro.phone.config) {
					appAspro.phone.config = {
						useValidate: false,
						coutriesData: '<?=CMax::$arParametrsList['FORMS']['OPTIONS']['USE_INTL_PHONE']['DEPENDENT_PARAMS']['PHONE_CITIES']['TYPE_SELECT']['SRC']?>',
						mask: arAsproOptions['THEME']['PHONE_MASK'],
						onlyCountries: '<?=CMax::GetFrontParametrValue('PHONE_CITIES');?>',
						preferredCountries: '<?=CMax::GetFrontParametrValue('PHONE_CITIES_FAVORITE');?>'
					}
				}
				
			</script>
		<?endif;?>
	<?}

	public static function GetCurrentSectionSubSectionFilter(&$arVariables, &$arParams, $CurrentSectionID = false){
		$arFilter = array('IBLOCK_ID' => $arParams['IBLOCK_ID']);
		if($arParams['CHECK_DATES'] == 'Y')
		{
			$arFilter = array_merge($arFilter, array('ACTIVE' => 'Y', 'GLOBAL_ACTIVE' => 'Y', 'ACTIVE_DATE' => 'Y'));
		}
		if(!$arFilter['SECTION_ID'] = ($CurrentSectionID !== false ? $CurrentSectionID : ($arVariables['SECTION_ID'] ? $arVariables['SECTION_ID'] : false)))
		{
			$arFilter['INCLUDE_SUBSECTIONS'] = 'N';array_merge($arFilter, array('INCLUDE_SUBSECTIONS' => 'N', 'DEPTH_LEVEL' => '1'));
			$arFilter['DEPTH_LEVEL'] = '1';
			unset($arFilter['GLOBAL_ACTIVE']);
		}
		return $arFilter;
	}

	public static function GetIBlockAllElementsFilter(&$arParams){
		global $arRegion;
		$arFilter = array('IBLOCK_ID' => $arParams['IBLOCK_ID'], 'INCLUDE_SUBSECTIONS' => 'Y');
		if(isset($arParams['CHECK_DATES']) && $arParams['CHECK_DATES'] == 'Y')
		{
			$arFilter = array_merge($arFilter, array('ACTIVE' => 'Y', 'ACTIVE_DATE' => 'Y'));
		}
		if(isset($arParams['SHOW_DEACTIVATED']) && $arParams['SHOW_DEACTIVATED'] === 'N')
		{ // for catalog component
			$arFilter = array_merge($arFilter, array('ACTIVE' => 'Y'));
		}
		if(strlen($arParams['FILTER_NAME']) && (array)$GLOBALS[$arParams['FILTER_NAME']])
		{
			$arFilter = array_merge($arFilter, (array)$GLOBALS[$arParams['FILTER_NAME']]);
		}
		/*if($arRegion)
		{
			if(!isset($arFilter['PROPERTY_LINK_REGION']))
				$arFilter['PROPERTY_LINK_REGION'] = $arRegion['ID'];
		}*/
		return $arFilter;
	}

	public static function prepareItemMapHtml($arShop, $bStore = "N", $arParams = array(), $bBigBlock = "N"){
		$html = '<div class="map_info_store">';

		if($arParams['TITLE_BLOCK_DETAIL_NAME'])
			$html .= '<div class="subtitle font_upper muted">'.$arParams['TITLE_BLOCK_DETAIL_NAME'].'</div>';

		$html .= '<div class="title option-font-bold '.($bBigBlock != 'Y' ? 'font_mlg' : 'font_exlg').'">'.(strlen($arShop["URL"]) ? '<a class="dark_link" href="'.$arShop["URL"].'">' : '').$arShop["ADDRESS"].(strlen($arShop["URL"]) ? '</a>' : '').'</div>';
		if($arShop['METRO'] || $arShop['SCHEDULE'] || $arShop['EMAIL'] || $arShop['PHONE'] || isset($arShop['QUANTITY'])){
			if(isset($arShop['QUANTITY']))
			{
				$html .= $arShop['QUANTITY'];
			}
			$html .= '<div class="properties">';


				$html .= ($arShop['METRO'] ? '<div class="property schedule"><div class="title-prop font_upper muted">'.($bStore == 'Y' ? Loc::getMessage('CONTACT_METRO') : $arShop['DISPLAY_PROPERTIES']['METRO']['NAME']).'</div><div class="value">'.$arShop['METRO_PLACEMARK_HTML'].'</div></div>' : '');
				$html .= (strlen($arShop['SCHEDULE']) ? '<div class="property schedule"><div class="title-prop font_upper muted">'.($bStore == 'Y' ? Loc::getMessage('CONTACT_SCHEDULE') : $arShop['DISPLAY_PROPERTIES']['SCHEDULE']['NAME']).'</div><div class="value">'.$arShop['SCHEDULE'].'</div></div>' : '');

				if($arShop['PHONE']){
					$phone = '';
					if(is_array($arShop['PHONE'])){
						foreach($arShop['PHONE'] as $value){
							$phone .= '<div class="value"><a class="dark_link" rel= "nofollow" href="tel:'.str_replace(array(' ', ',', '-', '(', ')'), '', $value).'">'.$value.'</a></div>';
						}
					}
					else{
						$phone = '<div class="value"><a class="dark_link" rel= "nofollow" href="tel:'.str_replace(array(' ', ',', '-', '(', ')'), '', $arShop['PHONE']).'">'.$arShop['PHONE'].'</a></div>';


					}
					$html .= '<div class="property phone"><div class="title-prop font_upper muted">'.($bStore == 'Y' ? Loc::getMessage('CONTACT_PHONE') : $arShop['DISPLAY_PROPERTIES']['PHONE']['NAME']).'</div>'.$phone.'</div>';
				}

				$html .= (strlen($arShop['EMAIL']) ? '<div class="property email"><div class="title-prop font_upper muted">'.($bStore == 'Y' ? Loc::getMessage('CONTACT_EMAIL') : $arShop['DISPLAY_PROPERTIES']['EMAIL']['NAME']).'</div><div class="value"><a class="dark_link" href="mailto:'.$arShop['EMAIL'].'">'.$arShop['EMAIL'].'</a></div></div>' : '');
			$html .= '</div>';
		}
		$html .= '</div>';
		return $html;
	}

	public static function prepareShopListArray($arShops){
		$arFormatShops=array();

		$arPlacemarks = array();

		if(is_array($arShops)){
			foreach($arShops as $i => $arShop){
				if(isset($arShop['IBLOCK_ID'])){
					$arShop['TITLE'] = ($arShop['FIELDS']['NAME'] ? $arShop['NAME'] : '');
					$imageID = (($arShop['FIELDS']['PREVIEW_PICTURE'] && $arShop["PREVIEW_PICTURE"]['ID']) ? $arShop["PREVIEW_PICTURE"]['ID'] : (($arShop['FIELD_CODE']['DETAIL_PICTURE'] && $arShop["DETAIL_PICTURE"]['ID']) ? $arShop["DETAIL_PICTURE"]['ID'] : false));
					$arShop['IMAGE'] = ($imageID ? CFile::ResizeImageGet($imageID, array('width' => 100, 'height' => 69), BX_RESIZE_IMAGE_EXACT) : []);
					$arShop['ADDRESS'] = $arShop['DISPLAY_PROPERTIES']['ADDRESS']['VALUE'];
					$arShop['ADDRESS'] = $arShop['TITLE'].((strlen($arShop['TITLE']) && strlen($arShop['ADDRESS'])) ? ', ' : '').$arShop['ADDRESS'];
					$arShop['PHONE'] = $arShop['DISPLAY_PROPERTIES']['PHONE']['VALUE'];
					$arShop['EMAIL'] = $arShop['DISPLAY_PROPERTIES']['EMAIL']['VALUE'];
					if(
						isset($arShop['DISPLAY_PROPERTIES']['SCHEDULE']['VALUE']['TYPE']) &&
						$arShop['DISPLAY_PROPERTIES']['SCHEDULE']['VALUE']['TYPE'] == 'html'
					){
						$arShop['SCHEDULE'] = htmlspecialchars_decode($arShop['DISPLAY_PROPERTIES']['SCHEDULE']['~VALUE']['TEXT']);
					}
					else{
						$arShop['SCHEDULE'] = nl2br($arShop['DISPLAY_PROPERTIES']['SCHEDULE']['~VALUE']['TEXT'] ?? '');
					}
					$arShop['URL'] = $arShop['DETAIL_PAGE_URL'];
					$arShop['METRO_PLACEMARK_HTML'] = '';
					if($arShop['METRO'] = $arShop['DISPLAY_PROPERTIES']['METRO']['VALUE']){
						if(!is_array($arShop['METRO'])){
							$arShop['METRO'] = array($arShop['METRO']);
						}
						foreach($arShop['METRO'] as $metro){
							$arShop['METRO_PLACEMARK_HTML'] .= '<div class="metro"><i></i>'.$metro.'</div>';
						}
					}

					$arShop['GPS_S'] = 0;
					$arShop['GPS_N'] = 0;

					if(
						isset($arShop['DISPLAY_PROPERTIES']['MAP']['VALUE']) &&
						strlen($arShop['DISPLAY_PROPERTIES']['MAP']['VALUE']) &&
						$arStoreMap = explode(',', $arShop['DISPLAY_PROPERTIES']['MAP']['VALUE'])
					){
						$arShop['GPS_S'] = $arStoreMap[0];
						$arShop['GPS_N'] = $arStoreMap[1];
					}

					if($arShop['GPS_S'] && $arShop['GPS_N']){
						$mapLAT += $arShop['GPS_S'];
						$mapLON += $arShop['GPS_N'];

						$html = self::prepareItemMapHtml($arShop);

						$arPlacemarks[] = array(
							"ID" => $arShop["ID"],
							"LAT" => $arShop['GPS_S'],
							"LON" => $arShop['GPS_N'],
							"TEXT" => $html,
							"HTML" => '<div class="title">'.(strlen($arShop["URL"]) ? '<a href="'.$arShop["URL"].'">' : '').$arShop["ADDRESS"].(strlen($arShop["URL"]) ? '</a>' : '').'</div><div class="info-content">'.($arShop['METRO'] ? $arShop['METRO_PLACEMARK_HTML'] : '').(strlen($arShop['SCHEDULE']) ? '<div class="schedule">'.$arShop['SCHEDULE'].'</div>' : '').$str_phones.(strlen($arShop['EMAIL']) ? '<div class="email"><a rel="nofollow" href="mailto:'.$arShop['EMAIL'].'">'.$arShop['EMAIL'].'</a></div>' : '').'</div>'.(strlen($arShop['URL']) ? '<a rel="nofollow" class="button" href="'.$arShop["URL"].'"><span>'.GetMessage('DETAIL').'</span></a>' : '')
						);
					}
				}
				else{
					$str_phones = '';
					if($arShop['PHONE'])
					{
						$arShop['PHONE'] = explode(",", $arShop['PHONE']);
						$str_phones .= '<div class="phone">';
						foreach($arShop['PHONE'] as $phone)
						{
							$str_phones .= '<br><a rel="nofollow" href="tel:'.str_replace(array(' ', ',', '-', '(', ')'), '', $phone).'">'.$phone.'</a>';
						}
						$str_phones .= '</div>';
					}
					if($arShop['GPS_S'] && $arShop['GPS_N']){
						$mapLAT += $arShop['GPS_N'];
						$mapLON += $arShop['GPS_S'];

						$html = self::prepareItemMapHtml($arShop, "Y");

						$arPlacemarks[] = array(
							"ID" => $arShop["ID"],
							"LON" => $arShop['GPS_S'],
							"LAT" => $arShop['GPS_N'],
							"TEXT" => $html,
							"HTML" => $html
						);
					}
				}
				$arShops[$i] = $arShop;
			}
		}
		$arFormatShops["SHOPS"]=$arShops;
		$arFormatShops["PLACEMARKS"]=$arPlacemarks;
		$arFormatShops["POINTS"]=array(
			"LAT" => $mapLAT,
			"LON" => $mapLON,
		);

		return $arFormatShops;
	}

	public static function prepareShopDetailArray($arShop, $arParams){
		$mapLAT = $mapLON = 0;
		$arPlacemarks = array();
		$arPhotos = array();
		$arFormatShops=array();

		if(is_array($arShop)){
			if(isset($arShop['IBLOCK_ID'])){
				$arShop['LIST_URL'] = $arShop['LIST_PAGE_URL'];
				$arShop['TITLE'] = (in_array('NAME', $arParams['FIELD_CODE']) ? $arShop['NAME'] : '');
				$arShop['ADDRESS'] = $arShop['DISPLAY_PROPERTIES']['ADDRESS']['VALUE'];
				$arShop['ADDRESS'] = $arShop['TITLE'].((strlen($arShop['TITLE']) && strlen($arShop['ADDRESS'])) ? ', ' : '').$arShop['ADDRESS'];
				$arShop['PHONE'] = $arShop['DISPLAY_PROPERTIES']['PHONE']['VALUE'];
				$arShop['EMAIL'] = $arShop['DISPLAY_PROPERTIES']['EMAIL']['VALUE'];
				if(
					isset($arShop['DISPLAY_PROPERTIES']['SCHEDULE']['VALUE']['TYPE']) && 
					$arShop['DISPLAY_PROPERTIES']['SCHEDULE']['VALUE']['TYPE'] == 'html'
				){
					$arShop['SCHEDULE'] = htmlspecialchars_decode($arShop['DISPLAY_PROPERTIES']['SCHEDULE']['~VALUE']['TEXT']);
				}
				else{
					$arShop['SCHEDULE'] = nl2br($arShop['DISPLAY_PROPERTIES']['SCHEDULE']['~VALUE']['TEXT'] ?? '');
				}
				$arShop['URL'] = $arShop['DETAIL_PAGE_URL'];
				$arShop['METRO_PLACEMARK_HTML'] = '';
				if($arShop['METRO'] = $arShop['DISPLAY_PROPERTIES']['METRO']['VALUE']){
					if(!is_array($arShop['METRO'])){
						$arShop['METRO'] = array($arShop['METRO']);
					}
					foreach($arShop['METRO'] as $metro){
						$arShop['METRO_PLACEMARK_HTML'] .= '<div class="metro"><i></i>'.$metro.'</div>';
					}
				}

				$arShop['GPS_S'] = 0;
				$arShop['GPS_N'] = 0;

				if(
					isset($arShop['DISPLAY_PROPERTIES']['MAP']['VALUE']) &&
					strlen($arShop['DISPLAY_PROPERTIES']['MAP']['VALUE']) &&
					$arStoreMap = explode(',', $arShop['DISPLAY_PROPERTIES']['MAP']['VALUE'])
				){
					$arShop['GPS_S'] = $arStoreMap[0];
					$arShop['GPS_N'] = $arStoreMap[1];
				}

				if($arShop['GPS_S'] && $arShop['GPS_N']){
					$mapLAT += $arShop['GPS_S'];
					$mapLON += $arShop['GPS_N'];
					$str_phones = '';
					if($arShop['PHONE'])
					{
						$str_phones .= '<div class="phone">';
						foreach($arShop['PHONE'] as $phone)
						{
							$str_phones .= '<br><a rel="nofollow" href="tel:'.str_replace(array(' ', ',', '-', '(', ')'), '', $phone).'">'.$phone.'</a>';
						}
						$str_phones .= '</div>';
					}

					$html = self::prepareItemMapHtml($arShop);

					$arPlacemarks[] = array(
						"ID" => $arShop["ID"],
						"LAT" => $arShop['GPS_S'],
						"LON" => $arShop['GPS_N'],
						"TEXT" => $html,
						"HTML" => '<div class="title">'.(strlen($arShop["URL"]) ? '<a href="'.$arShop["URL"].'">' : '').$arShop["ADDRESS"].(strlen($arShop["URL"]) ? '</a>' : '').'</div><div class="info-content">'.($arShop['METRO'] ? $arShop['METRO_PLACEMARK_HTML'] : '').(strlen($arShop['SCHEDULE']) ? '<div class="schedule">'.$arShop['SCHEDULE'].'</div>' : '').$str_phones.(strlen($arShop['EMAIL']) ? '<div class="email"><a rel="nofollow" href="mailto:'.$arShop['EMAIL'].'">'.$arShop['EMAIL'].'</a></div>' : '').'</div>'.(strlen($arShop['URL']) ? '<a rel="nofollow" class="button" href="'.$arShop["URL"].'"><span>'.GetMessage('DETAIL').'</span></a>' : '')
					);
				}
			}
			else{
				$arShop["TITLE"] = htmlspecialchars_decode($arShop["TITLE"]);
				$arShop["ADDRESS"] = htmlspecialchars_decode($arShop["ADDRESS"]);
				$arShop["ADDRESS"] = (strlen($arShop["TITLE"]) ? $arShop["TITLE"].', ' : '').$arShop["ADDRESS"];
				$arShop["DESCRIPTION"] = htmlspecialchars_decode($arShop['DESCRIPTION']);
				$arShop['SCHEDULE'] = htmlspecialchars_decode($arShop['SCHEDULE']);

				$str_phones = '';
				if($arShop['PHONE'])
				{
					$arShop['PHONE'] = explode(",", $arShop['PHONE']);
					$str_phones .= '<div class="phone">';
					foreach($arShop['PHONE'] as $phone)
					{
						$str_phones .= '<br><a rel="nofollow" href="tel:'.str_replace(array(' ', ',', '-', '(', ')'), '', $phone).'">'.$phone.'</a>';
					}
					$str_phones .= '</div>';
				}
				if($arShop['GPS_S'] && $arShop['GPS_N']){
					$mapLAT += $arShop['GPS_N'];
					$mapLON += $arShop['GPS_S'];

					$html = self::prepareItemMapHtml($arShop, "Y");

					$arPlacemarks[] = array(
						"ID" => $arShop["ID"],
						"LON" => $arShop['GPS_S'],
						"LAT" => $arShop['GPS_N'],
						"TEXT" => $html,
						"HTML" => $html
					);
				}
			}
		}
		$arFormatShops["SHOP"]=$arShop;
		$arFormatShops["PLACEMARKS"]=$arPlacemarks;
		$arFormatShops["POINTS"]=array(
			"LAT" => $mapLAT,
			"LON" => $mapLON,
		);

		return $arFormatShops;

	}

	public static function prepareRegionItemMapHtml($arRegion){
		$html = '<div class="map_info_store">';


		$html .= '<div class="title option-font-bold font_mlg">'.($arRegion['PROPERTY_ADDRESS_VALUE']['TEXT'] ?? '').'</div>';
		if($arRegion['PROPERTY_REGION_TAG_METRO_VALUE'] || $arRegion['PROPERTY_REGION_TAG_SHEDULLE_VALUE'] || $arRegion['PHONES'] || $arRegion['PROPERTY_EMAIL_VALUE'] ){
			$html .= '<div class="properties">';

				$html .= ($arRegion['PROPERTY_REGION_TAG_METRO_VALUE'] ? '<div class="property metro"><div class="title-prop font_upper muted">'.(Loc::getMessage('CONTACT_METRO')).'</div><div class="value">'.$arRegion['PROPERTY_REGION_TAG_METRO_VALUE'].'</div></div>' : '');

				if(strlen($arRegion['PROPERTY_REGION_TAG_SHEDULLE_VALUE']['TEXT'] ?? '') && $arRegion['PROPERTY_REGION_TAG_SHEDULLE_VALUE']["TYPE"] == 'HTML'){
					$shedule = str_replace(array("\r\n", "\r", "\n"), "", $arRegion['PROPERTY_REGION_TAG_SHEDULLE_VALUE']['TEXT']);
				} else {
					$shedule = $arRegion['PROPERTY_REGION_TAG_SHEDULLE_VALUE']['TEXT'] ?? '';
				}

				$html .= (strlen($shedule) ? '<div class="property schedule"><div class="title-prop font_upper muted">'.(Loc::getMessage('CONTACT_SCHEDULE')).'</div><div class="value">'.$shedule.'</div></div>' : '');

				if($arRegion['PHONES']){
					$phone = '';
					if(is_array($arRegion['PHONES'])){
						foreach($arRegion['PHONES'] as $value){
							if(is_array($value)){
								$phone .= '<div class="value"><a class="dark_link" rel= "nofollow" href="'.(strlen($value['HREF']) ? $value['HREF'] : 'javascript:;').'">'.$value['PHONE'].'</a></div>';
							}
							else{
								$phone .= '<div class="value"><a class="dark_link" rel= "nofollow" href="tel:'.str_replace(array(' ', ',', '-', '(', ')'), '', $value).'">'.$value.'</a></div>';
							}
						}
					}
					else{
						$phone = '<div class="value"><a class="dark_link" rel= "nofollow" href="tel:'.str_replace(array(' ', ',', '-', '(', ')'), '', $arRegion['PHONES']).'">'.$arRegion['PHONES'].'</a></div>';
					}
					$html .= '<div class="property phone"><div class="title-prop font_upper muted">'.(Loc::getMessage('CONTACT_PHONE')).'</div>'.$phone.'</div>';
				}

				if($arRegion['PROPERTY_EMAIL_VALUE']){
					$phone = '';
					if(is_array($arRegion['PROPERTY_EMAIL_VALUE'])){
						foreach($arRegion['PROPERTY_EMAIL_VALUE'] as $value){
							$email .= '<div class="value"><a class="dark_link" href="mailto:'.$value.'">'.$value.'</a></div>';
						}
					}
					else{
						$email = '<div class="value"><a class="dark_link" href="mailto:'.$arRegion['PROPERTY_EMAIL_VALUE'].'">'.$arRegion['PROPERTY_EMAIL_VALUE'].'</a></div>';
					}
					$html .= '<div class="property email"><div class="title-prop font_upper muted">'.(Loc::getMessage('CONTACT_EMAIL')).'</div>'.$email.'</div>';
				}

			$html .= '</div>';
		}
		$html .= '</div>';
		return $html;
	}

	public static function drawShopsList($arShops, $arParams, $showMap="Y"){
		global $APPLICATION;
		$mapLAT = $mapLON = 0;
		$arPlacemarks = array();

		if(is_array($arShops)){
			foreach($arShops as $i => $arShop){
				if(isset($arShop['IBLOCK_ID'])){
					$arShop['TITLE'] = (in_array('NAME', $arParams['FIELD_CODE']) ? strip_tags($arShop['~NAME']) : '');

					$imageID = ((in_array('PREVIEW_PICTURE', $arParams['FIELD_CODE']) && $arShop["PREVIEW_PICTURE"]['ID']) ? $arShop["PREVIEW_PICTURE"]['ID'] : ((in_array('DETAIL_PICTURE', $arParams['FIELD_CODE']) && $arShop["DETAIL_PICTURE"]['ID']) ? $arShop["DETAIL_PICTURE"]['ID'] : false));
					$arShop['IMAGE'] = ($imageID ? CFile::ResizeImageGet($imageID, array('width' => 450, 'height' => 300), BX_RESIZE_IMAGE_PROPORTIONAL) : []);
					$arShop['ADDRESS'] = $arShop['DISPLAY_PROPERTIES']['ADDRESS']['VALUE'];
					$arShop['ADDRESS'] = $arShop['TITLE'].((strlen($arShop['TITLE']) && strlen($arShop['ADDRESS'])) ? ', ' : '').$arShop['ADDRESS'];
					$arShop['PHONE'] =  $arShop['DISPLAY_PROPERTIES']['PHONE']['VALUE'];
					$arShop['EMAIL'] = $arShop['DISPLAY_PROPERTIES']['EMAIL']['VALUE'];

					if(
						isset($arShop['DISPLAY_PROPERTIES']['SCHEDULE']['VALUE']['TYPE']) &&
						strToLower($arShop['DISPLAY_PROPERTIES']['SCHEDULE']['VALUE']['TYPE']) == 'html'
					){
						$arShop['SCHEDULE'] = htmlspecialchars_decode($arShop['DISPLAY_PROPERTIES']['SCHEDULE']['~VALUE']['TEXT']);
					}
					else{
						$arShop['SCHEDULE'] = nl2br($arShop['DISPLAY_PROPERTIES']['SCHEDULE']['~VALUE']['TEXT'] ?? '');
					}
					$arShop['URL'] = $arShop['DETAIL_PAGE_URL'];
					$arShop['METRO_PLACEMARK_HTML'] = '';
					if($arShop['METRO'] = $arShop['DISPLAY_PROPERTIES']['METRO']['VALUE']){
						if(!is_array($arShop['METRO'])){
							$arShop['METRO'] = array($arShop['METRO']);
						}
						foreach($arShop['METRO'] as $metro){
							$arShop['METRO_PLACEMARK_HTML'] .= '<div class="metro"><i></i>'.$metro.'</div>';
						}
					}
					$arShop['DESCRIPTION'] = $arShop['DETAIL_TEXT'];

					$arShop['GPS_S'] = 0;
					$arShop['GPS_N'] = 0;

					if(
						isset($arShop['DISPLAY_PROPERTIES']['MAP']['VALUE']) &&
						strlen($arShop['DISPLAY_PROPERTIES']['MAP']['VALUE']) &&
						$arStoreMap = explode(',', $arShop['DISPLAY_PROPERTIES']['MAP']['VALUE'])
					){
						$arShop['GPS_S'] = $arStoreMap[0];
						$arShop['GPS_N'] = $arStoreMap[1];
					}

					if($arShop['GPS_S'] && $arShop['GPS_N']){
						$mapLAT += $arShop['GPS_S'];
						$mapLON += $arShop['GPS_N'];
						$str_phones = '';
						if($arShop['PHONE'])
						{
							foreach($arShop['PHONE'] as $phone)
							{
								$str_phones .= '<div class="phone"><a rel="nofollow" href="tel:'.str_replace(array(' ', ',', '-', '(', ')'), '', $phone).'">'.$phone.'</a></div>';
							}
						}

						$html = self::prepareItemMapHtml($arShop);

						$arPlacemarks[] = array(
							"ID" => $arShop["ID"],
							"LAT" => $arShop['GPS_S'],
							"LON" => $arShop['GPS_N'],
							"TEXT" => $html,
							"HTML" => '<div class="title">'.(strlen($arShop["URL"]) ? '<a href="'.$arShop["URL"].'">' : '').$arShop["ADDRESS"].(strlen($arShop["URL"]) ? '</a>' : '').'</div><div class="info-content">'.($arShop['METRO'] ? $arShop['METRO_PLACEMARK_HTML'] : '').(strlen($arShop['SCHEDULE']) ? '<div class="schedule">'.$arShop['SCHEDULE'].'</div>' : '').$str_phones.(strlen($arShop['EMAIL']) ? '<div class="email"><a rel="nofollow" href="mailto:'.$arShop['EMAIL'].'">'.$arShop['EMAIL'].'</a></div>' : '').'</div>'.(strlen($arShop['URL']) ? '<a rel="nofollow" class="button" href="'.$arShop["URL"].'"><span>'.GetMessage('DETAIL').'</span></a>' : '')
						);
					}
				}
				else{
					$str_phones = '';
					if($arShop['PHONE'])
					{
						foreach($arShop['PHONE'] as $phone)
						{
							$str_phones .= '<div class="phone"><a rel="nofollow" href="tel:'.str_replace(array(' ', ',', '-', '(', ')'), '', $phone).'">'.$phone.'</a></div>';
						}
					}
					if($arShop['GPS_S'] && $arShop['GPS_N']){
						$mapLAT += $arShop['GPS_N'];
						$mapLON += $arShop['GPS_S'];

						$html = self::prepareItemMapHtml($arShop, "Y");

						$arPlacemarks[] = array(
							"ID" => $arShop["ID"],
							"LON" => $arShop['GPS_S'],
							"LAT" => $arShop['GPS_N'],
							"TEXT" => $html,
							"HTML" => $html
						);
					}
				}
				$arShops[$i] = $arShop;
			}
			?>
			<?if($arShops):?>
				<?if(abs($mapLAT) > 0 && abs($mapLON) > 0 && $showMap=="Y"):?>
					<?
					$mapLAT = $mapLAT;//floatval($mapLAT / count($arShops));
					$mapLON = $mapLON;//floatval($mapLON / count($arShops));
					?>
					<div class="contacts_map ">
						<?if($arParams["MAP_TYPE"] != "0"):?>
							<?$APPLICATION->IncludeComponent(
								"bitrix:map.google.view",
								"",
								array(
									"INIT_MAP_TYPE" => "ROADMAP",
									"MAP_DATA" => serialize(array("google_lat" => $mapLAT, "google_lon" => $mapLON, "google_scale" => 15, "PLACEMARKS" => $arPlacemarks)),
									"MAP_WIDTH" => "100%",
									"MAP_HEIGHT" => "550",
									"CONTROLS" => array(
									),
									"OPTIONS" => array(
										0 => "ENABLE_DBLCLICK_ZOOM",
										1 => "ENABLE_DRAGGING",
									),
									"MAP_ID" => "",
									"ZOOM_BLOCK" => array(
										"POSITION" => "right center",
									),
									"API_KEY" => $arParams["GOOGLE_API_KEY"],
									"COMPOSITE_FRAME_MODE" => "A",
									"COMPOSITE_FRAME_TYPE" => "AUTO"
								),
								false, array("HIDE_ICONS" =>"Y")
							);?>
						<?else:?>
							<?$APPLICATION->IncludeComponent(
								"bitrix:map.yandex.view",
								"map",
								array(
									"INIT_MAP_TYPE" => "ROADMAP",
									"MAP_DATA" => serialize(array("yandex_lat" => $mapLAT, "yandex_lon" => $mapLON, "yandex_scale" => 4, "PLACEMARKS" => $arPlacemarks)),
									"MAP_WIDTH" => "100%",
									"MAP_HEIGHT" => "550",
									"CONTROLS" => array(
										0 => "ZOOM",
										1 => "SMALLZOOM",
										3 => "TYPECONTROL",
										4 => "SCALELINE",
									),
									"OPTIONS" => array(
										0 => "ENABLE_DBLCLICK_ZOOM",
										1 => "ENABLE_DRAGGING",
									),
									"MAP_ID" => "",
									"ZOOM_BLOCK" => array(
										"POSITION" => "right center",
									),
									"COMPONENT_TEMPLATE" => "map",
									"API_KEY" => $arParams["GOOGLE_API_KEY"],
									"COMPOSITE_FRAME_MODE" => "A",
									"COMPOSITE_FRAME_TYPE" => "AUTO"
								),
								false, array("HIDE_ICONS" =>"Y")
							);?>
						<?endif;?>
					</div>
				<?endif;?>
				<div class="wrapper_inner">
					<div class="stores-list1">
						<div class="items">
							<?foreach($arShops as $arShop):?>

								<div class="item bordered box-shadow<?=(!$arShop["IMAGE"] || !strlen($arShop["IMAGE"]["src"]) ? ' wti' : '')?>" >
									<div class="row">
										<div class="col-md-6 col-sm-8 col-xs-12 left-block-contacts">
											<?if($arShop["IMAGE"] && strlen($arShop["IMAGE"]["src"])):?>
												<div class="image pull-left">
													<a href="<?=$arShop['URL']?>">
														<img src="<?=\Aspro\Functions\CAsproMax::showBlankImg($arShop["IMAGE"]["src"]);?>" data-src="<?=$arShop["IMAGE"]["src"];?>" alt="<?=$arShop["ADDRESS"]?>" title="<?=$arShop["ADDRESS"]?>" class="img-responsive lazy"/>
													</a>
												</div>
											<?endif;?>
											<div class="top-wrap">
												<div class="title font_mxs darken">
													<a href="<?=$arShop['URL']?>" class="darken">
														<?=$arShop["ADDRESS"]?>
													</a>
												</div>
												<div class="middle-prop">
													<?if (
														(is_numeric($arShop['GPS_S']) && abs($arShop['GPS_S']) > 0) 
														&& (is_numeric($arShop['GPS_N']) && abs($arShop['GPS_N']) > 0)
													):?>
														<div class="show_on_map font_upper colored_theme_text">
															<span class="text_wrap" data-coordinates="<?=(isset($arShop['IBLOCK_ID']) ? $arShop['GPS_S'].','.$arShop['GPS_N'] : $arShop['GPS_N'].','.$arShop['GPS_S']);?>">
																<?=static::showIconSvg("on_map colored", SITE_TEMPLATE_PATH.'/images/svg/show_on_map.svg');?>
																<span class="text"><?=GetMessage('SHOW_ON_MAP')?></span>
															</span>
														</div>
													<?endif;?>

													<?if ($arShop["METRO"] && is_array($arShop["METRO"])):?>
														<?foreach ($arShop["METRO"] as $metro):?>
															<div class="metro font_upper"><?=static::showIconSvg("metro colored", SITE_TEMPLATE_PATH."/images/svg/Metro.svg");?><span class="text muted777"><?=$metro;?></span></div>
														<?endforeach;?>
													<?endif;?>
												</div>
												<?if($arShop["SCHEDULE"]):?>
													<div class="schedule"><?=static::showIconSvg("clock colored", SITE_TEMPLATE_PATH."/images/svg/WorkingHours.svg");?><span class="text font_xs muted777"><?=$arShop["SCHEDULE"];?></span></div>
												<?endif;?>
											</div>
										</div>
										<div class="col-md-6 col-sm-4 col-xs-12 right-block-contacts">
											<div class="item-body">
												<div class="row">
													<?if($arShop["PHONE"]):?>
														<div class="phones col-md-6 col-sm-12 col-xs-12">
															<?foreach($arShop["PHONE"] as $phone):?>
																<div class="phone font_sm darken">
																	<a href="tel:<?=str_replace(array(' ', ',', '-', '(', ')'), '', $phone);?>" class="black"><?=$phone;?></a>
																</div>
															<?endforeach;?>
														</div>
													<?endif?>
													<?if(strlen($arShop["EMAIL"])):?>
														<div class="emails col-md-6 col-sm-12 col-xs-12">
															<div class="email font_sm">
																<a class="dark-color" href="mailto:<?=$arShop["EMAIL"];?>"><?=$arShop["EMAIL"];?></a>
															</div>
														</div>
													<?endif?>
												</div>
											</div>
										</div>

									</div>
								</div>

							<?endforeach;?>
						</div>
					</div>
				</div>
				<div class="clearboth"></div>
			<?endif;?>
			<?
		}
		else{
			LocalRedirect(SITE_DIR.'contacts/');
		}
	}

	public static function truncateLengthText($text = '', $length = 0){
		if(strlen($text)){
			$obParser = new CTextParser;
			$text = $obParser->html_cut($text, intval($length));
		}

		return $text;
	}

	public static function updateExtendedReviewsProps($commentId, $action = '') {
		if(CModule::IncludeModule('blog') && CModule::IncludeModule('iblock')) {
			$comment = CBlogComment::GetByID($commentId);

			if($comment) {
				$catalogIblockId = \Bitrix\Main\Config\Option::get('aspro.max', 'CATALOG_IBLOCK_ID', CMaxCache::$arIBlocks[SITE_ID]['aspro_max_catalog']['aspro_max_catalog'][0]);
				$product = CIBlockElement::GetList(array(), array('PROPERTY_BLOG_POST_ID' => $comment['POST_ID'], 'IBLOCK_ID' => $catalogIblockId), false, array('nTopCount' => '1'), array('ID', 'PROPERTY_BLOG_POST_ID', 'IBLOCK_ID'))->Fetch();
				if($product) {
					$productId = $product['ID'];
				}

				$commentsCount = $commentsRating = $commentsCountRaiting = 0;
				$resBlog = CBlogComment::GetList(array("ID"=>"DESC"), array('POST_ID' => $comment['POST_ID'], 'PARENT_ID' => false, 'PUBLISH_STATUS' => 'P'), false, false, array('ID', 'UF_ASPRO_COM_RATING'));
				while( $comment = $resBlog->Fetch() ) {
					if($comment['UF_ASPRO_COM_RATING']) {
						$commentsCountRaiting++;
						$commentsRating += $comment['UF_ASPRO_COM_RATING'];
					}
					$commentsCount++;
				}

				if($action == 'delete'){
					$commentsCount--;
				}

				foreach(GetModuleEvents(ASPRO_MAX_MODULE_ID, 'OnAsproUpdateExtendedReviewsProps', true) as $arEvent)
					ExecuteModuleEventEx($arEvent, array(&$commentsCount, &$commentsRating, &$commentsCountRaiting));

				if ($catalogIblockId) {
					CIBlock::clearIblockTagCache($catalogIblockId);
					if($commentsRating) {
						$value = round( $commentsRating/$commentsCountRaiting, 1 );
						CIBlockElement::SetPropertyValuesEx($productId, $catalogIblockId, array('EXTENDED_REVIEWS_COUNT' => $commentsCount, 'EXTENDED_REVIEWS_RAITING' => $value) );
					} else {
						CIBlockElement::SetPropertyValuesEx($productId, $catalogIblockId, array('EXTENDED_REVIEWS_COUNT' => $commentsCount, 'EXTENDED_REVIEWS_RAITING' => 0) );
					}
				}
			}
		}
	}

	public static function drawShopDetail($arShop, $arParams, $showMap="Y"){
		global $APPLICATION;
		$mapLAT = $mapLON = 0;
		$arPlacemarks = array();
		$arPhotos = array();
		if(is_array($arShop)){
			if(isset($arShop['IBLOCK_ID'])){
				$arShop['LIST_URL'] = $arShop['LIST_PAGE_URL'];
				$arShop['TITLE'] = (in_array('NAME', $arParams['FIELD_CODE']) ? strip_tags($arShop['~NAME']) : '');
				$arShop['ADDRESS'] = $arShop['DISPLAY_PROPERTIES']['ADDRESS']['VALUE'];
				$arShop['ADDRESS'] = $arShop['TITLE'].((strlen($arShop['TITLE']) && strlen($arShop['ADDRESS'])) ? ', ' : '').$arShop['ADDRESS'];
				$arShop['PHONE'] = $arShop['DISPLAY_PROPERTIES']['PHONE']['VALUE'];
				$arShop['EMAIL'] = $arShop['DISPLAY_PROPERTIES']['EMAIL']['VALUE'];
				if(
					isset($arShop['DISPLAY_PROPERTIES']['SCHEDULE']['VALUE']['TYPE']) &&
					strToLower($arShop['DISPLAY_PROPERTIES']['SCHEDULE']['VALUE']['TYPE']) == 'html'
				){
					$arShop['SCHEDULE'] = htmlspecialchars_decode($arShop['DISPLAY_PROPERTIES']['SCHEDULE']['~VALUE']['TEXT']);
				}
				else{
					$arShop['SCHEDULE'] = nl2br($arShop['DISPLAY_PROPERTIES']['SCHEDULE']['~VALUE']['TEXT'] ?? '');
				}
				$arShop['URL'] = $arShop['DETAIL_PAGE_URL'];
				$arShop['METRO_PLACEMARK_HTML'] = '';
				if($arShop['METRO'] = $arShop['DISPLAY_PROPERTIES']['METRO']['VALUE']){
					if(!is_array($arShop['METRO'])){
						$arShop['METRO'] = array($arShop['METRO']);
					}
					foreach($arShop['METRO'] as $metro){
						$arShop['METRO_PLACEMARK_HTML'] .= '<div class="metro"><i></i>'.$metro.'</div>';
					}
				}
				$arShop['DESCRIPTION'] = $arShop['DETAIL_TEXT'];
				$imageID = ((in_array('DETAIL_PICTURE', $arParams['FIELD_CODE']) && $arShop["DETAIL_PICTURE"]['ID']) ? $arShop["DETAIL_PICTURE"]['ID'] : false);
				if($imageID){
					$arShop['IMAGE'] = CFile::ResizeImageGet($imageID, array('width' => 600, 'height' => 600), BX_RESIZE_IMAGE_PROPORTIONAL);
					$arPhotos[] = array(
						'ID' => $arShop["DETAIL_PICTURE"]['ID'],
						'ORIGINAL' => ($arShop["DETAIL_PICTURE"]['SRC'] ? $arShop["DETAIL_PICTURE"]['SRC'] : $arShop['IMAGE']),
						'PREVIEW' => $arShop['IMAGE'],
						'DESCRIPTION' => (strlen($arShop["DETAIL_PICTURE"]['DESCRIPTION']) ? $arShop["DETAIL_PICTURE"]['DESCRIPTION'] : $arShop['ADDRESS']),
					);
				}
				if(is_array($arShop['DISPLAY_PROPERTIES']['MORE_PHOTOS']['VALUE'])) {
					foreach($arShop['DISPLAY_PROPERTIES']['MORE_PHOTOS']['VALUE'] as $i => $photoID){
						$arPhotos[] = array(
							'ID' => $photoID,
							'ORIGINAL' => CFile::GetPath($photoID),
							'PREVIEW' => CFile::ResizeImageGet($photoID, array('width' => 600, 'height' => 600), BX_RESIZE_IMAGE_PROPORTIONAL),
							'DESCRIPTION' => $arShop['DISPLAY_PROPERTIES']['MORE_PHOTOS']['DESCRIPTION'][$i],
						);
					}
				}

				$arShop['GPS_S'] = 0;
				$arShop['GPS_N'] = 0;

				if(
					isset($arShop['DISPLAY_PROPERTIES']['MAP']['VALUE']) &&
					strlen($arShop['DISPLAY_PROPERTIES']['MAP']['VALUE']) &&
					$arStoreMap = explode(',', $arShop['DISPLAY_PROPERTIES']['MAP']['VALUE'])
				){
					$arShop['GPS_S'] = $arStoreMap[0];
					$arShop['GPS_N'] = $arStoreMap[1];
				}

				if($arShop['GPS_S'] && $arShop['GPS_N']){
					$mapLAT += $arShop['GPS_S'];
					$mapLON += $arShop['GPS_N'];
					$str_phones = '';
					if($arShop['PHONE'])
					{
						foreach($arShop['PHONE'] as $phone)
						{
							$str_phones .= '<div class="phone"><a rel="nofollow" href="tel:'.str_replace(array(' ', ',', '-', '(', ')'), '', $phone).'">'.$phone.'</a></div>';
						}
					}

					$html = self::prepareItemMapHtml($arShop);

					$arPlacemarks[] = array(
						"ID" => $arShop["ID"],
						"LAT" => $arShop['GPS_S'],
						"LON" => $arShop['GPS_N'],
						// "TEXT" => $arShop["TITLE"],
						"TEXT" => $html
					);
				}
			}
			else{
				$arShop["TITLE"] = strip_tags(htmlspecialchars_decode($arShop["TITLE"]));
				$arShop["ADDRESS"] = htmlspecialchars_decode($arShop["ADDRESS"]);
				$arShop["ADDRESS"] = (strlen($arShop["TITLE"]) ? $arShop["TITLE"].', ' : '').$arShop["ADDRESS"];
				$arShop["DESCRIPTION"] = htmlspecialchars_decode($arShop['DESCRIPTION']);
				$arShop['SCHEDULE'] = htmlspecialchars_decode($arShop['SCHEDULE']);
				if($arShop["IMAGE_ID"]  && $arShop["IMAGE_ID"] != "null"){
					$arShop['IMAGE'] = CFile::ResizeImageGet($arShop["IMAGE_ID"], array('width' => 600, 'height' => 600), BX_RESIZE_IMAGE_PROPORTIONAL );
					$arPhotos[] = array(
						'ID' => $arShop["PREVIEW_PICTURE"]['ID'],
						'ORIGINAL' => CFile::GetPath($arShop["IMAGE_ID"]),
						'PREVIEW' => $arShop['IMAGE'],
						'DESCRIPTION' => (strlen($arShop["PREVIEW_PICTURE"]['DESCRIPTION']) ? $arShop["PREVIEW_PICTURE"]['DESCRIPTION'] : $arShop["ADDRESS"]),
					);
				}
				if(is_array($arShop['MORE_PHOTOS'])) {
					foreach($arShop['MORE_PHOTOS'] as $photoID){
						$arPhotos[] = array(
							'ID' => $photoID,
							'ORIGINAL' => CFile::GetPath($photoID),
							'PREVIEW' => CFile::ResizeImageGet($photoID, array('width' => 600, 'height' => 600), BX_RESIZE_IMAGE_PROPORTIONAL ),
							'DESCRIPTION' => $arShop["ADDRESS"],
						);
					}
				}

				$str_phones = '';
				if($arShop['PHONE'])
				{
					foreach($arShop['PHONE'] as $phone)
					{
						$str_phones .= '<div class="phone"><a rel="nofollow" href="tel:'.str_replace(array(' ', ',', '-', '(', ')'), '', $phone).'">'.$phone.'</a></div>';
					}
				}
				if($arShop['GPS_S'] && $arShop['GPS_N']){
					$mapLAT += $arShop['GPS_N'];
					$mapLON += $arShop['GPS_S'];

					$html = self::prepareItemMapHtml($arShop, "Y");

					$arPlacemarks[] = array(
						"ID" => $arShop["ID"],
						"LON" => $arShop['GPS_S'],
						"LAT" => $arShop['GPS_N'],
						"TEXT" => $html,
						"HTML" => $html
					);
				}
			}
			?>


			<?/*<div class="wrapper_inner shop-detail1">*/?>

				<div class="item item-shop-detail1  <?=($showMap ? 'col-md-6' : 'col-md-12')?>">
					<div class="left_block_store <?=($showMap ? '' : 'margin0')?>">
						<?//if(in_array('NAME', $arParams['LIST_FIELD_CODE']) || in_array('PREVIEW_PICTURE', $arParams['LIST_FIELD_CODE']) && $arItem['PREVIEW_PICTURE']):?>
							<div class="top_block">
								<?if(strlen($arShop['ADDRESS'])):?>
									<div class="address">
										<div class="title font_upper muted"><?=GetMessage('ADDRESS')?></div>
										<div class="value darken"><?=$arShop['ADDRESS']?></div>
									</div>
								<?endif;?>
								<?if($arPhotos):?>
									<!-- noindex-->
									<div class="gallery_wrap swipeignore">
										<?//gallery?>
										<div class="big-gallery-block text-center">
										    <div class="owl-carousel owl-theme owl-bg-nav short-nav" data-slider="content-detail-gallery__slider" data-plugin-options='{"items": "1", "autoplay" : false, "autoplayTimeout" : "3000", "smartSpeed":1000, "dots": true, "nav": true, "loop": false, "rewind":true, "margin": 10}'>
											<?foreach($arPhotos as $i => $arPhoto):?>
											    <div class="item">
												<a href="<?=$arPhoto['ORIGINAL']?>" class="fancy" data-fancybox="item_slider" target="_blank" title="<?=$arPhoto['DESCRIPTION']?>">
													<div class="lazy" data-src="<?=$arPhoto['PREVIEW']['src']?>" style="background-image:url('<?=\Aspro\Functions\CAsproMax::showBlankImg($arPhoto['PREVIEW']['src']);?>')"></div>
													<?/*<img data-src="<?=$arPhoto['PREVIEW']['src']?>" src="<?=\Aspro\Functions\CAsproMax::showBlankImg($arPhoto['PREVIEW']['src']);?>" class="img-responsive inline lazy" alt="<?=$arPhoto['DESCRIPTION']?>" />*/?>
												</a>
											    </div>
											<?endforeach;?>
										    </div>
										</div>
									</div>
									<!-- /noindex-->
								<?endif;?>
							</div>
						<?//endif;?>
						<div class="bottom_block">
							<div class="properties clearfix">
								<div class="col-md-6 col-sm-6">
									<?if($arShop["METRO"]):?>
										<?foreach($arShop["METRO"] as $metro):?>
											<div class="property metro">
												<div class="title font_upper"><?=GetMessage('METRO')?></div>
												<div class="value darken"><?=$metro;?></div>
											</div>
										<?endforeach;?>
									<?endif;?>
									<?if($arShop["SCHEDULE"]):?>
										<div class="property schedule">
											<div class="title font_upper"><?=GetMessage('SCHEDULE')?></div>
											<div class="value darken"><?=$arShop["SCHEDULE"];?></div>
										</div>
									<?endif;?>
								</div>
								<div class="col-md-6 col-sm-6">
									<?if($arShop["PHONE"]):?>
										<div class="property phone">
											<div class="title font_upper"><?=GetMessage('PHONE')?></div>
											<?foreach($arShop["PHONE"] as $phone):?>
												<div class="value phone darken">
													<a href="tel:<?=str_replace(array(' ', ',', '-', '(', ')'), '', $phone);?>" rel="nofollow" class="black"><?=$phone;?></a>
												</div>
											<?endforeach;?>
										</div>
									<?endif?>
									<?if(strlen($arShop["EMAIL"])):?>
										<div class="property email">
											<div class="title font_upper">Email</div>
											<div class="value darken"><a class="dark-color" rel="nofollow" href="mailto:<?=$arShop["EMAIL"];?>"><?=$arShop["EMAIL"];?></a></div>
										</div>
									<?endif;?>
								</div>

							</div>
							<div class="social-block">
								<div class="wrap">
									<?$APPLICATION->IncludeComponent(
									    "aspro:social.info.max",
									    ".default",
									    array(
									        "CACHE_TYPE" => "A",
									        "CACHE_TIME" => "3600000",
									        "CACHE_GROUPS" => "N",
									        "TITLE_BLOCK" => "",
									        "COMPONENT_TEMPLATE" => ".default",
									    ),
									    false, array("HIDE_ICONS" => "Y")
									);?>
								</div>
							</div>
							<div class="feedback item">
								<div class="wrap">
									<?if($arShop['DESCRIPTION']):?>
										<div class="previewtext muted777"><?=$arShop['DESCRIPTION'];?></div>
									<?endif;?>
									<?//if(1 || $bUseFeedback):?>
										<div class="button_wrap">
											<span>
												<span class="btn  btn-transparent-border-color white  animate-load" data-event="jqm" data-param-form_id="ASK" data-name="contacts"><?=Loc::getMessage('S_ASK_QUESTION');?></span>
											</span>
										</div>
									<?//endif;?>
								</div>
							</div>
						</div>
						<div class="clearboth"></div>
						<!-- noindex-->
						<div class="bottom-links-block">
							<a class="muted back-url url-block" href="javascript:history.back();">
								<?=static::showIconSvg("return_to_the_list", SITE_TEMPLATE_PATH."/images/svg/return_to_the_list.svg", "");?>
							<span class="font_upper back-url-text"><?=GetMessage('BACK_STORE_LIST')?></span></a>

							<?//if($arParams["USE_SHARE"] == "Y"):?>
								<?//\Aspro\Functions\CAsproMax::showShareBlock('bottom')?>
							<?//endif;?>
						</div>
						<!-- /noindex-->
					</div>

				</div>
				<?if($showMap == "Y"):?>
					<div class="item col-md-6 map-full padding0">
						<div class="right_block_store contacts_map">
							<?if(abs($mapLAT) > 0 && abs($mapLON) > 0 && $showMap=="Y"):?>
								<?//<div class="contacts_map">?>
									<?if($arParams["MAP_TYPE"] != "0"):?>
										<?$APPLICATION->IncludeComponent(
											"bitrix:map.google.view",
											"",
											array(
												"INIT_MAP_TYPE" => "ROADMAP",
												"MAP_DATA" => serialize(array("google_lat" => $mapLAT, "google_lon" => $mapLON, "google_scale" => 16, "PLACEMARKS" => $arPlacemarks)),
												"MAP_WIDTH" => "100%",
												"MAP_HEIGHT" => "100%",
												"CONTROLS" => array(
												),
												"OPTIONS" => array(
													0 => "ENABLE_DBLCLICK_ZOOM",
													1 => "ENABLE_DRAGGING",
												),
												"MAP_ID" => "",
												"ZOOM_BLOCK" => array(
													"POSITION" => "right center",
												),
												"COMPONENT_TEMPLATE" => "map",
												"API_KEY" => $arParams["GOOGLE_API_KEY"],
												"COMPOSITE_FRAME_MODE" => "A",
												"COMPOSITE_FRAME_TYPE" => "AUTO"
											),
											false, array("HIDE_ICONS" =>"Y")
										);?>
									<?else:?>
										<?$APPLICATION->IncludeComponent(
											"bitrix:map.yandex.view",
											"map",
											array(
												"INIT_MAP_TYPE" => "ROADMAP",
												"MAP_DATA" => serialize(array("yandex_lat" => $mapLAT, "yandex_lon" => $mapLON, "yandex_scale" => 17, "PLACEMARKS" => $arPlacemarks)),
												"MAP_WIDTH" => "100%",
												"MAP_HEIGHT" => "100%",
												"CONTROLS" => array(
													0 => "ZOOM",
													1 => "SMALLZOOM",
													3 => "TYPECONTROL",
													4 => "SCALELINE",
												),
												"OPTIONS" => array(
													0 => "ENABLE_DBLCLICK_ZOOM",
													1 => "ENABLE_DRAGGING",
												),
												"MAP_ID" => "",
												"ZOOM_BLOCK" => array(
													"POSITION" => "right center",
												),
												"COMPONENT_TEMPLATE" => "map",
												"API_KEY" => $arParams["GOOGLE_API_KEY"],
												"COMPOSITE_FRAME_MODE" => "A",
												"COMPOSITE_FRAME_TYPE" => "AUTO"
											),
											false, array("HIDE_ICONS" =>"Y")
										);?>
									<?endif;?>
								<?//</div>?>
							<?endif;?>
						</div>
					</div>
				<?endif;?>




			<?/*</div>
			<div class="clearboth"></div>
			*/?>
			<?
		}
		else{
			LocalRedirect(SITE_DIR.'contacts/');
		}
	}

	static public function nlo($code, $attrs = ''){
		static $arAvailable, $isStarted, $arNlo;

		if(!isset($arAvailable)){
			$arAvailable = array(
				'menu-fixed' => $GLOBALS['arTheme']['NLO_MENU']['VALUE'] === 'Y',
				'menu-mobile' => $GLOBALS['arTheme']['NLO_MENU']['VALUE'] === 'Y',
				'menu-megafixed' => $GLOBALS['arTheme']['NLO_MENU']['VALUE'] === 'Y',
			);

			$arNlo = array();
		}

		if($arAvailable[$code]){
			if(
				isset($_REQUEST['nlo']) &&
				$_REQUEST['nlo'] === $code
			){
				if(isset($isStarted)){
					die();
				}

				$isStarted = true;
				$GLOBALS['APPLICATION']->RestartBuffer();
				return true;
			}
			else{
				if(isset($arNlo[$code])){
					echo '</div>';
				}
				else{
					$arNlo[$code] = true;
					echo '<div '.(strlen($attrs) ? $attrs : '').' data-nlo="'.$code.'">';
				}

				return false;
			}
		}

		return true;
	}

	public static function checkMask($mask_exc = ''){
		if($mask_exc)
		{
			$request = Application::getInstance()->getContext()->getServer();
			$arMaskTmp = explode(";", $mask_exc);

			// trim space in array items
			$arMask = array_map(function($n){
				return trim($n);
			}, $arMaskTmp);

			$reg = '%^' . implode('|', $arMask) . '%i'; // set reg

			if($request["REAL_FILE_PATH"]) // real page
				$url = $request["REAL_FILE_PATH"];
			else
				$url = $request->getScriptName();

			if(!preg_match($reg, $url))
				return false; // no page in mask
			else
				return true; // page in mask
		}
		return false;
	}

	public static function getSectionDescriptionPriority($siteId = ''){
		static $arCacheValues;

		$siteId = strlen($siteId) ? $siteId : (defined('SITE_ID') ? SITE_ID : '');

		if(!isset($arCacheValues)){
			$arCacheValues = array();
		}

		if(!isset($arCacheValues[$siteId])){
			$arCacheValues[$siteId] = array();
		}

		$arPriority =& $arCacheValues[$siteId];

		if(!$arPriority){
			$arPriority = array();

			if($siteId){
				if(static::GetFrontParametrValue('USE_PRIORITY_SECTION_DESCRIPTION_SOURCE', $siteId) === 'Y'){
					$priority = static::GetFrontParametrValue('PRIORITY_SECTION_DESCRIPTION_SOURCE', $siteId);
					$arPriority = explode(',', $priority);

					if(!in_array('SMARTSEO', $arPriority)){
						$arPriority[] = 'SMARTSEO';
					}
					if(!in_array('SOTBIT_SEOMETA', $arPriority)){
						$arPriority[] = 'SOTBIT_SEOMETA';
					}
					if(!in_array('IBLOCK', $arPriority)){
						$arPriority[] = 'IBLOCK';
					}
				}
			}
		}

		return $arPriority;
	}

	public static function unsetViewPart($viewCode, $searchPartContent){
		if(
			$GLOBALS['APPLICATION']->__view &&
			is_array($GLOBALS['APPLICATION']->__view) &&
			array_key_exists($viewCode, $GLOBALS['APPLICATION']->__view) &&
			$GLOBALS['APPLICATION']->__view[$viewCode] &&
			is_array($GLOBALS['APPLICATION']->__view[$viewCode])
		){
			$searchPartContent = trim($searchPartContent);

			foreach($GLOBALS['APPLICATION']->__view[$viewCode] as $i => $arPartContent){
				$partContent = trim($arPartContent[0]);

				if($partContent === $searchPartContent){
					unset($GLOBALS['APPLICATION']->__view[$viewCode][$i]);
					break;
				}
			}
		}
	}

	public static function replaceViewPart($viewCode, $searchPartContent, $newPartContent){
		if(
			$GLOBALS['APPLICATION']->__view &&
			is_array($GLOBALS['APPLICATION']->__view) &&
			array_key_exists($viewCode, $GLOBALS['APPLICATION']->__view) &&
			$GLOBALS['APPLICATION']->__view[$viewCode] &&
			is_array($GLOBALS['APPLICATION']->__view[$viewCode])
		){
			$searchPartContent = trim($searchPartContent);
			$newPartContent = trim($newPartContent);

			foreach($GLOBALS['APPLICATION']->__view[$viewCode] as $i => $arPartContent){
				$partContent = trim($arPartContent[0]);

				if($partContent === $searchPartContent){
					$GLOBALS['APPLICATION']->__view[$viewCode][$i][0] = $newPartContent;
					break;
				}
			}
		}
	}

	public static function setCatalogSectionDescription(array $arParams){
		$siteId = strlen($siteId) ? $siteId : (defined('SITE_ID') ? SITE_ID : '');

		$posSectionDescr = static::GetFrontParametrValue('SHOW_SECTION_DESCRIPTION', $siteId);

		if( self::isSmartSeoInstalled() ){
			$GLOBALS['APPLICATION']->IncludeComponent(
				"aspro:smartseo.content",
				".default",
				array(
					"FIELDS" => array(
						"TOP_DESCRIPTION",
						"BOTTOM_DESCRIPTION",
						"ADDITIONAL_DESCRIPTION",
					),
					"SHOW_VIEW_CONTENT" => "Y",
					"CODE_VIEW_CONTENT" => "smartseo",
				),
				false,
				array("HIDE_ICONS" => "Y")
			);
		}

		if(\Bitrix\Main\Loader::includeModule("sotbit.seometa")){
			// unset, because the sotbit:seo.meta component may have already been included
			unset($GLOBALS['APPLICATION']->__view['sotbit_seometa_h1']);
			unset($GLOBALS['APPLICATION']->__view['sotbit_seometa_top_desc']);
			unset($GLOBALS['APPLICATION']->__view['sotbit_seometa_bottom_desc']);
			unset($GLOBALS['APPLICATION']->__view['sotbit_seometa_add_desc']);
			unset($GLOBALS['APPLICATION']->__view['sotbit_seometa_file']);

			$GLOBALS['APPLICATION']->IncludeComponent(
				"sotbit:seo.meta",
				".default",
				array(
					"FILTER_NAME" => $arParams["FILTER_NAME"],
					"SECTION_ID" => $arParams['SECTION_ID'],
					"CACHE_TYPE" => $arParams["CACHE_TYPE"],
					"CACHE_TIME" => $arParams["CACHE_TIME"],
				),
				false,
				array("HIDE_ICONS" => "Y")
			);
		}

		$top_desc = trim($GLOBALS['APPLICATION']->GetViewContent('top_desc'));
		$bottom_desc = trim($GLOBALS['APPLICATION']->GetViewContent('bottom_desc'));
		$smartseo_top_desc = trim($GLOBALS['APPLICATION']->GetViewContent('smartseo_top_description'));
		$smartseo_bottom_desc = trim($GLOBALS['APPLICATION']->GetViewContent('smartseo_bottom_description'));
		$smartseo_add_desc = trim($GLOBALS['APPLICATION']->GetViewContent('smartseo_additional_description'));
		$sotbit_top_desc = trim($GLOBALS['APPLICATION']->GetViewContent('sotbit_seometa_top_desc'));
		$sotbit_bottom_desc = trim($GLOBALS['APPLICATION']->GetViewContent('sotbit_seometa_bottom_desc'));
		$sotbit_add_desc = trim($GLOBALS['APPLICATION']->GetViewContent('sotbit_seometa_add_desc'));

		$bShowTopDescInTop = $bShowAdditionalDescInTop2 = $bShowAdditionalDescInBottom = $bShowBottomDescInBottom = $bShowSeoDesc = false;

		if($arParams['SEO_ITEM']){
			$bShowTopDescInTop = true;
			$bShowBottomDescInBottom = true;
			$bShowAdditionalDescInTop2 = true;
			$bShowSeoDesc = true;
		}
		elseif(
			$arParams['SHOW_SECTION_DESC'] !== 'N' &&
			strpos($_SERVER['REQUEST_URI'], 'PAGEN') === false
		){
			$bShowTopDescInTop = $posSectionDescr === 'BOTH' || $posSectionDescr === 'TOP';
			$bShowBottomDescInBottom = $posSectionDescr === 'BOTH' || $posSectionDescr === 'BOTTOM';
			$bShowAdditionalDescInBottom = true;
			$bShowSeoDesc = true;
		}

		if($bShowSeoDesc){
			if(strlen($smartseo_top_desc)){
				$GLOBALS['APPLICATION']->AddViewContent('top_content', '<div class="group_description_block top muted777 smartseo-block">'.$smartseo_top_desc.'</div>');
			}

			if(strlen($sotbit_top_desc)){
				$GLOBALS['APPLICATION']->AddViewContent('top_content', '<div class="group_description_block top muted777 sotbit-block">'.$sotbit_top_desc.'</div>');
			}

			if(strlen($smartseo_bottom_desc)){
				static::replaceViewPart('smartseo_bottom_description', $smartseo_bottom_desc, '<div class="group_description_block bottom muted777 smartseo-block">'.$smartseo_bottom_desc.'</div>');
			}

			if(strlen($sotbit_bottom_desc)){
				static::replaceViewPart('sotbit_seometa_bottom_desc', $sotbit_bottom_desc, '<div class="group_description_block bottom muted777 sotbit-block">'.$sotbit_bottom_desc.'</div>');
			}
		}
		else{
			unset($GLOBALS['APPLICATION']->__view['smartseo_bottom_description']);
			unset($GLOBALS['APPLICATION']->__view['sotbit_seometa_bottom_desc']);
		}

		if(!$bShowTopDescInTop){
			unset($GLOBALS['APPLICATION']->__view['top_desc']);
			static::unsetViewPart('top_content', $top_desc);
		}

		if(!$bShowBottomDescInBottom){
			unset($GLOBALS['APPLICATION']->__view['bottom_desc']);
		}

		if($bShowAdditionalDescInTop2){
			if(strlen($smartseo_add_desc)){
				$GLOBALS['APPLICATION']->AddViewContent('top_content2', '<div class="group_description_block top muted777 smartseo-block">'.$smartseo_add_desc.'</div>');
			}

			if(strlen($sotbit_add_desc)){
				$GLOBALS['APPLICATION']->AddViewContent('top_content2', '<div class="group_description_block top muted777 sotbit-block">'.$sotbit_add_desc.'</div>');
			}
		}

		if($bShowAdditionalDescInBottom){
			if(strlen($smartseo_add_desc)){
				static::replaceViewPart('smartseo_additional_description', $smartseo_add_desc, '<div class="group_description_block bottom muted777 smartseo-block">'.$smartseo_add_desc.'</div>');
			}

			if(strlen($sotbit_add_desc)){
				static::replaceViewPart('sotbit_seometa_add_desc', $sotbit_add_desc, '<div class="group_description_block bottom muted777 sotbit-block">'.$sotbit_add_desc.'</div>');
			}
		}
		else{
			unset($GLOBALS['APPLICATION']->__view['smartseo_additional_description']);
			unset($GLOBALS['APPLICATION']->__view['sotbit_seometa_add_desc']);
		}

		$arPriority = static::getSectionDescriptionPriority(SITE_ID);
		if($arPriority){
			$bTopDescFilled = $bTopDesc2Filled = $bBottomDescFilled = false;

			foreach($arPriority as $priorityCode){
				if($priorityCode === 'IBLOCK'){
					if(strlen($top_desc)){
						if($bTopDescFilled){
							unset($GLOBALS['APPLICATION']->__view['top_desc']);
							static::unsetViewPart('top_content', $top_desc);
						}
						else{
							$bTopDescFilled = true;
						}
					}

					if(strlen($bottom_desc)){
						if($bBottomDescFilled){
							unset($GLOBALS['APPLICATION']->__view['bottom_desc']);
						}
						else{
							$bBottomDescFilled = true;
						}
					}
				}
				elseif($priorityCode === 'SMARTSEO'){
					if(strlen($smartseo_top_desc) && $bShowSeoDesc){
						if($bTopDescFilled){
							unset($GLOBALS['APPLICATION']->__view['smartseo_top_description']);
							static::unsetViewPart('top_content', '<div class="group_description_block top muted777 smartseo-block">'.$smartseo_top_desc.'</div>');
						}
						else{
							$bTopDescFilled = true;

							if(strlen($top_desc)){
								static::unsetViewPart('top_content', '<div class="group_description_block top muted777 smartseo-block">'.$smartseo_top_desc.'</div>');
								static::replaceViewPart('top_content', $top_desc, '<div class="group_description_block top muted777 smartseo-block">'.$smartseo_top_desc.'</div>');
							}
						}
					}

					if(strlen($smartseo_add_desc) && $bShowAdditionalDescInTop2){
						if($bTopDesc2Filled){
							unset($GLOBALS['APPLICATION']->__view['smartseo_additional_description']);
							static::unsetViewPart('top_content2', '<div class="group_description_block top muted777 smartseo-block">'.$smartseo_add_desc.'</div>');
						}
						else{
							$bTopDesc2Filled = true;
						}
					}

					if(strlen(($bShowSeoDesc ? $smartseo_bottom_desc : '').($bShowAdditionalDescInBottom ? $smartseo_add_desc : ''))){
						if($bBottomDescFilled){
							unset($GLOBALS['APPLICATION']->__view['smartseo_bottom_description']);
							unset($GLOBALS['APPLICATION']->__view['smartseo_additional_description']);
						}
						else{
							$bBottomDescFilled = true;
						}
					}
				}
				else{
					if(strlen($sotbit_top_desc) && $bShowSeoDesc){
						if($bTopDescFilled){
							unset($GLOBALS['APPLICATION']->__view['sotbit_top_desc']);
							static::unsetViewPart('top_content', '<div class="group_description_block top muted777 sotbit-block">'.$sotbit_top_desc.'</div>');
						}
						else{
							$bTopDescFilled = true;

							if(strlen($top_desc)){
								static::unsetViewPart('top_content', '<div class="group_description_block top muted777 sotbit-block">'.$sotbit_top_desc.'</div>');
								static::replaceViewPart('top_content', $top_desc, '<div class="group_description_block top muted777 sotbit-block">'.$sotbit_top_desc.'</div>');
							}
						}
					}

					if(strlen($sotbit_add_desc) && $bShowAdditionalDescInTop2){
						if($bTopDesc2Filled){
							unset($GLOBALS['APPLICATION']->__view['sotbit_add_desc']);
							static::unsetViewPart('top_content2', '<div class="group_description_block top muted777 sotbit-block">'.$sotbit_add_desc.'</div>');
						}
						else{
							$bTopDesc2Filled = true;
						}
					}

					if(strlen(($bShowSeoDesc ? $sotbit_bottom_desc : '').($bShowAdditionalDescInBottom ? $sotbit_add_desc : ''))){
						if($bBottomDescFilled){
							unset($GLOBALS['APPLICATION']->__view['sotbit_bottom_desc']);
							unset($GLOBALS['APPLICATION']->__view['sotbit_add_desc']);
						}
						else{
							$bBottomDescFilled = true;
						}
					}
				}
			}
		}
	}
	
	/**
	 * Class CMax
	 * @deprecated
	 * @mixin \Aspro\Max\Product\Quantity
	 * @see \Aspro\Max\Product\Quantity
	 */
	public static function CCatalogStore_GetList(
		$arOrder = ["SORT" => "ASC"], 
		$arFilter = [], 
		$arGroupBy = false, 
		$arNavStartParams = false, 
		$arSelectFields = []
	){
		return Aspro\Max\Product\Quantity::CCatalogStore_GetList($arOrder, $arFilter, $arGroupBy, $arNavStartParams, $arSelectFields);
	}

	public static function SortBySearchRank($searchQuery, $arElements, $arParams){
		$arResult = array();

		if($arElements){
			if(Loader::includeModule('search')){
				$aSort = array(
					'CUSTOM_RANK' => 'DESC',
					'TITLE_RANK' => 'DESC',
					'RANK' => 'DESC',
					'DATE_CHANGE' => 'DESC'
				);

				$exFILTER = CSearchParameters::ConvertParamsToFilter($arParams, 'arrFILTER');

				$arFilter = array(
					'SITE_ID' => SITE_ID,
					'QUERY' => trim($searchQuery),
					'ITEM_ID' => array_values($arElements),
				);

				$obSearch = new CSearch();

				$obSearch->SetOptions(array(
					'ERROR_ON_EMPTY_STEM' => $arParams['RESTART'] != 'Y',
					'NO_WORD_LOGIC' => $arParams['NO_WORD_LOGIC'] == 'Y',
				));

				$obSearch->Search($arFilter, $aSort, $exFILTER);

				//echo $obSearch->errorno;
				//echo $obSearch->error;

				if($obSearch->errorno == 0){
					$obSearch->NavStart(20, false);
					$ar = $obSearch->GetNext();

					if(!$ar && $obSearch->Query->bStemming){
						$exFILTER['STEMMING'] = false;
						$obSearch = new CSearch();
						$obSearch->Search($arFilter, $aSort, $exFILTER);

						//echo $obSearch->errorno;
						//echo $obSearch->error;

						if($obSearch->errorno == 0){
							$obSearch->NavStart($arParams['PAGE_RESULT_COUNT'], false);
							$ar = $obSearch->GetNext();
						}
					}

					while($ar){
						$arResult[] = $ar['ITEM_ID'];
						$ar = $obSearch->GetNext();
					}
				}

			}
		}

		return $arResult;
	}

	public static function SortBySearchOrder($arElementsIDsSorted, $arItemsToSort){
		$arResult = array();

		if($arItemsToSort){
			$arResult = array_column($arItemsToSort, 'ID');
			$arElementsIDsSorted = array_values($arElementsIDsSorted);

			usort($arResult, function($a, $b) use ($arElementsIDsSorted){
				$posA = array_search($a, $arElementsIDsSorted);
				$posB = array_search($b, $arElementsIDsSorted);

				if($posA !== false && $posB !== false){
					return $posA <=> $posB;
				}

				return $posA !== false ? -1 : ($posB !== false ? 1 : 0);
			});
		}

		return $arResult;
	}
	protected static function getJQuerySrc() {
		static $files;

		if (!isset($files)) {
			$files = [];
			$tmp = @glob($_SERVER['DOCUMENT_ROOT'].'/bitrix/js/main/jquery/jquery-*');
			if ($tmp) {
				foreach ($tmp as $file) {
					if (strpos($file, '.map.') === false) {
						if (preg_match('/\/jquery-((\d+)\.\d+\.\d+)\.?(min)?\.js$/', $file, $arMatch)) {
							$src = '/bitrix/js/main/jquery'.$arMatch[0];
							if ($arMatch[3]) {
								$files[$arMatch[2]] = $src;
							}
							else {
								$files[$arMatch[2].'_src'] = $src;
							}				
						}
					}
				}
			}
		}

		$version = Option::get(self::moduleID, 'JQUERY_VERSION', '3');

		switch ($version) {
			case '2':
				return $files['2'] ?: $files['2_src'];
			default: 
				return $files['3'] ?: $files['3_src'];
		}
	}
}
