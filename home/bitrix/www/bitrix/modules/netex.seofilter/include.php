<?

use Bitrix\Catalog\GroupTable;
use Bitrix\Iblock\ElementTable;
use Bitrix\Iblock\PropertyTable;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Application;
use Netex\Seofilter\FacetPropFinder;
use Netex\SeoFilter\LandingTable;
use Bitrix\Main\Loader;
use Bitrix\Main\Web\Uri;

global $APPLICATION;

if(!CNetexSeoFilter::$catalogModule)
{
	CNetexSeoFilter::$catalogModule = Loader::includeModule('catalog');
}

Loc::loadMessages(__FILE__);
Loc::loadCustomMessages(__FILE__);

class CNetexPermission
{
	public static function canWrite()
	{
		global $APPLICATION;
		return !$APPLICATION->GetGroupRight('netex.seofilter') < "W";
	}

	public static function canRead()
	{
		global $APPLICATION;
		return !$APPLICATION->GetGroupRight('netex.seofilter') < "R";
	}
}

class CNetexEvent
{
	/**
	 * Set SEO parameters of page (such as title, meta, h1 etc.)
	 */
	public static function OnEpilog()
	{
		if(CNetexSeoFilter::getInstance()->isLanding())
		{
			global $APPLICATION;
			$landing = CNetexSeoFilter::getInstance()->getLanding();

			if($landing)
			{
				$APPLICATION->SetTitle($landing['H1']);
				$APPLICATION->SetPageProperty('TITLE',$landing['TITLE']);
				$APPLICATION->SetPageProperty('DESCRIPTION',$landing['META_DESCRIPTION']);
				$APPLICATION->SetPageProperty('KEYWORDS',$landing['META_KEYWORDS']);

				$APPLICATION->SetPageProperty("ROBOTS", "index, follow");

				if(strlen(trim($landing['CUSTOM_URL'])) > 0)
				{
					$APPLICATION->SetPageProperty("CANONICAL", $landing['CUSTOM_URL']);
				}

				$APPLICATION->AddViewContent('SPW_CONTENT_HEADER', $landing['CONTENT_HEADER']);
				$APPLICATION->AddViewContent('SPW_CONTENT', $landing['CONTENT']);
				$APPLICATION->AddViewContent('SPW_CONTENT_BOTTOM', $landing['CONTENT_BOTTOM']);

                $chainItems = CNetexSeoFilter::getInstance()->getChainItems();
                foreach($chainItems as $item)
				{
					$APPLICATION->AddChainItem($item['TEXT'], $item['URL']);
				}

                $event = new Bitrix\Main\Event('netex.seofilter', 'onAfterSetMetaTags', array(
                    'LANDING' => $landing,
                ));
                $event->send();
			}
		}
	}

	/**
	 * @param $obj \CAdminForm
	 * @return bool
	 */
	public static function OnAdminTabControlBegin($obj)
	{
		global $APPLICATION;

		if (strpos($APPLICATION->GetCurPage(),"iblock_section_edit.php") !== FALSE ||
            strpos($APPLICATION->GetCurPage(),"cat_catalog_edit.php") !== FALSE ||
			strpos($APPLICATION->GetCurPage(),"cat_section_edit.php") !== FALSE)
		{
			$tab = array();
			$tab['DIV'] = "netex_seofilter";
			$tab['TAB'] = Loc::getMessage("NETEX_SEOFILTER_TAB_NAME");
			$tab['ICON'] = "catalog";
			$tab['TITLE'] = Loc::getMessage("NETEX_SEOFILTER_TAB_TITLE");
			$tab['CONTENT'] = '';

			$GLOBALS['SPW_IBLOCK_ID'] = intval($_REQUEST['IBLOCK_ID']);
			$GLOBALS['SPW_SECTION_ID'] = intval($_REQUEST['ID']);


			ob_start();
			require('admin/templates/list.php');
			$content = ob_get_clean();
			$tab['CONTENT'] = '<tr><td>'. $content . '</td></tr>';

			$tabs = array();
			foreach($obj->tabs as $i => $t) {
				$tabs[] = $t;
				if ($i == 1 || $t['TAB'] == 'SEO')
					$tabs[] = $tab;
			}

			$obj->tabs = $tabs;
			return true;
		}

		return false;
	}

	public function onIBlockElementUpdate($newFields, $oldFields)
	{
		return;
	}

	public function OnAfterIBlockElementSetPropertyValues($elementId, $iblockId, $newValues)
	{
		$dirtyPropertyIds = array();
		foreach ($newValues as $propId => $values)
		{
			if (!is_array($values))
			 	continue;

			foreach ($values as $key => $value)
			{
				if ($value != "" && in_array($key, array('n0','n1','n2','n3','n4','n5')))
				{
					$dirtyPropertyIds[] = $propId;
				}
			}
		}
		if (!empty($dirtyPropertyIds))
		{
			$res = CIBlockElement::GetByID($elementId);
			if ($ar = $res->GetNext()) {
				$sectionId =  $ar['IBLOCK_SECTION_ID'];
				foreach($dirtyPropertyIds as $propertyId)
				{
					$cache = CNetexCache::getInstance();
					$cache->setIBlockId($iblockId);
					$cache->setSectionId($sectionId);
					$cache->cleanProperty($propertyId);
				}
			}
		}
	}

	public function OnBeforeIBlockPropertyUpdate($fields)
	{
		return $fields;
	}

	public function OnBeforeIBlockPropertyDelete($ID)
	{
		return $ID;
	}

	public function OnAfterIBlockElementDelete($fields)
	{
		return $fields['ID'];
	}

	public static function OnIBlockDelete($id)
	{
		Application::getConnection()->query('DELETE FROM '.LandingTable::getTableName().' WHERE IBLOCK_ID='.$id.';');
	}

	public static function OnAfterIBlockSectionDelete($fields)
	{
		$id = $fields['ID'];
		Application::getConnection()->query('DELETE FROM '.LandingTable::getTableName().' WHERE SECTION_ID='.$id.';');
	}
}

class CNetexCache
{
	/**
	 * @var CNetexCache
	 */
	private static $instance;

	private $varCache;

	public function setVarCache($item,$value)
	{
		$this->varCache[$item] = $value;
	}

	public function getVarCache($item)
	{
		return isset($this->varCache[$item])?$this->varCache[$item]:null;
	}
	/**
	 * @return CNetexCache
	 */
	public static function getInstance()
	{
		if (!isset(self::$instance)) {
			self::$instance = new CNetexCache();
		}
		return self::$instance;
	}
	private $cache;
	private $lifetime;
	private $dir;
	private function __construct()
	{
		$this->cache = new CPHPCache();
		$this->lifetime = 3600 * 3;
		$this->dir = DIRECTORY_SEPARATOR . 'netex_seofilter' . DIRECTORY_SEPARATOR;
	}

	private $IBlockID;
	public function setIBlockId($ID)
	{
		$this->IBlockID = $ID;
	}

	private $sectionID;
	public function setSectionId($ID)
	{
		$this->sectionID = $ID;
	}

	public function getPropertyValues($propertyId)
	{
		if ($this->IBlockID && $this->sectionID &&
			($this->cache->InitCache($this->lifetime,$this->getPropertyKey($propertyId), $this->dir)))
			return $this->cache->GetVars();

		return false;
	}

	public function setPropertyValues($propertyId, $values)
	{
		if ($this->lifetime <= 0)
			return;

		if ($this->IBlockID && $this->sectionID == NULL)
			return;

		if ($this->cache->StartDataCache($this->lifetime,$this->getPropertyKey($propertyId), $this->dir))  {
			$this->cache->EndDataCache($values);
		}
	}

	protected function getPropertyKey($propertyId)
	{
		return $this->IBlockID . '/' . $this->sectionID . '/' . $propertyId;
	}

	public function cleanProperty($ID)
	{
		$this->cache->Clean($this->getPropertyKey($ID), $this->dir);
	}
}

class CNetexSeoFilter
{
	/** @var bool Is catalog module connected */
	public static $catalogModule = false;

	/** @var CNetexSeoFilter */
	private static $instance;

	/** @var array Items for adding in nav chain */
    protected $chainItems = array();

	/** @var array Landing fields */
	protected $landing;

	/** @var string Current page Url */
	protected $url;

	/** @var \CBitrixCatalogSmartFilter */
	protected $filterComponent;

	/** @var bool Is current pages landing */
	protected $isLanding = false;

	/**
	 * CNetexSeoFilter constructor
	 */
	private function __construct()
	{

	}

	/**
	 * Get instance of class
	 *
	 * @return CNetexSeoFilter
	 */
	public static function getInstance()
	{
		if (!isset(self::$instance))
		{
			self::$instance = new CNetexSeoFilter();
		}

		return self::$instance;
	}

	/**
	 * Init filter and get landing page
	 *
	 * @param $component \CBitrixCatalogSmartFilter
	 */
	public function initFilter($component)
	{
		if (!($component instanceof CBitrixComponent)) {
			$GLOBALS["APPLICATION"]->ThrowException("CNetexSeoFilter \$component ".GetMessage("NETEX_SEOFILTER_DOLJEN_BYTQ_NASLEDNI"));
		}

		$this->filterComponent = $component;

		$this->landing = LandingTable::getByUrl($this->getUrl());
		if($this->landing)
		{
			$this->isLanding = true;
		}
	}

	/**
	 * Get url of current page
	 *
	 * @return string
	 */
	public function getUrl()
    {
		if ($this->url == null) {
            $requestUrl = Application::getInstance()->getContext()->getRequest()->getRequestUri();
            $uri = new Uri($requestUrl);

			return $this->url = $uri->getPath();
		}

		return $this->url;
	}

	/**
	 * Get url for property in smart filter
	 *
	 * @param $property
	 * @param $value
	 * @return mixed
	 */
	public function getComboBoxUrl($property, $value)
	{
		$request[$property['CODE_ALT']][$value['HTML_VALUE_ALT']] = $value['HTML_VALUE_ALT'];
		return $this->filterComponent->getUrl($this->getUrl(), $request);
	}

	/**
	 * Get current landing
	 *
	 * @return array
	 */
	public function getLanding()
	{
		return $this->landing;
	}

	/**
	 * @return bool Is current page landing
	 */
	public function isLanding()
	{
		return $this->isLanding;
	}

	/**
	 * Get custom Url for landing
	 *
	 * @return string
	 */
    public function getSeoUrl()
    {
        return $this->landing['CUSTOM_URL'];
    }

	/**
	 * Get chain items array
	 *
	 * @return array
	 */
    public function getChainItems()
    {
        if(empty($this->chainItems))
        {
            $landing = $this->landing;
            $this->chainItems[] = array(
                'TEXT' => $landing['BREADCRUMB_NAME'],
                'URL' => !empty($landing['CUSTOM_URL']) ? $landing['CUSTOM_URL'] : $landing['URL'],
            );

            if((int)$landing['PARENT_ID'] > 0)
                $this->findChainRecursive((int)$landing['PARENT_ID']);

            $this->chainItems = array_reverse($this->chainItems);
        }

        return $this->chainItems;
    }

	/**
	 * Find chains items
	 *
	 * @param $parentId int Landing ID
	 */
    protected function findChainRecursive($parentId)
    {
        if(count($this->chainItems) > 7)
            return;

        $landing = LandingTable::getRowById($parentId);
        if($landing)
        {
            $this->chainItems[] = array(
                'TEXT' => $landing['BREADCRUMB_NAME'],
                'URL' => !empty($landing['CUSTOM_URL']) ? $landing['CUSTOM_URL'] : $landing['URL'],
            );
            if((int)$landing['PARENT_ID'] > 0)
                $this->findChainRecursive((int)$landing['PARENT_ID']);
        }
    }
}

class CNetexCatalogCondCtrlGroup extends CGlobalCondCtrlGroup
{
	public static function GetControlDescr()
	{
		$strClassName = get_called_class();
		return array(
			'ID' => static::GetControlID(),
			'GROUP' => 'Y',
			'GetControlShow' => array($strClassName, 'GetControlShow'),
			'GetConditionShow' => array($strClassName, 'GetConditionShow'),
			'IsGroup' => array($strClassName, 'IsGroup'),
			'Parse' => array($strClassName, 'Parse'),
			'Generate' => array($strClassName, 'Generate'),
			'ApplyValues' => array($strClassName, 'ApplyValues')
		);
	}
}

/**
 * @TODO ��� ��������� �������� ������� autocomplete
 *
 * Class CSfCondCtrlIBlockProps
 * @package Netex\CatalogLanding
 */
class CNetexCondCtrlIBlockProps extends CCatalogCondCtrlIBlockProps
{
    /**
     * @param bool|string $strControlID
     * @return bool|array
     * @throws \Bitrix\Main\ArgumentException
     * @throws \Bitrix\Main\LoaderException
     * @throws \Bitrix\Main\SystemException
     */
	public static function GetControls($strControlID = false)
	{
		$arControlList = array();
		$arIBlockList = array();

		$IBLOCK_ID = isset($_REQUEST['IBLOCK_ID']) ? intval($_REQUEST['IBLOCK_ID']) : 0;
		$SECTION_ID = isset($_REQUEST['SECTION_ID']) ? intval($_REQUEST['SECTION_ID']) : 0;

		$propertyFinder = new FacetPropFinder($IBLOCK_ID, $SECTION_ID, true);
		$properties = $propertyFinder->find();

		foreach ($properties as $intIBlockID => $rsProps) {
			CNetexCache::getInstance()->setVarCache($intIBlockID.':'.$SECTION_ID, $rsProps);

			$strName = CIBlock::GetArrayByID($intIBlockID, 'NAME');
			if (false !== $strName)	{
				$boolSep = true;
				foreach ($rsProps as $arProp) {
					if ('CML2_LINK' == $arProp['XML_ID'] || 'F' == $arProp['PROPERTY_TYPE'])
						continue;
					$strFieldType = '';
					$arLogic = array();
					$arValue = array();
					$arPhpValue = '';

					$boolUserType = false;
					if (isset($arProp['USER_TYPE']) && !empty($arProp['USER_TYPE'])) {
						switch ($arProp['USER_TYPE']) {
							case 'DateTime':
								$strFieldType = 'datetime';
								$arLogic = static::GetLogic(array(BT_COND_LOGIC_EQ));
								$arValue = array(
									'type' => 'datetime',
									'format' => 'datetime'
								);
								$boolUserType = true;
								break;
                            case "ElementXmlID":
                                $strFieldType = 'string';
                                $arLogic = static::GetLogic(array(BT_COND_LOGIC_EQ));
                                $arValue = array(
                                    'type' => 'select',
                                    'values' => array(CNetexConditionTree::ALL_VALUES_KEY => CNetexConditionTree::ALL_VALUES)
                                );
                                foreach ($arProp['VALUES'] as $key => $VALUE) {
                                    $query = ElementTable::getRow([
                                        'filter' => [
                                            'XML_ID' => $VALUE
                                        ],
                                        'select' => [
                                            'NAME', 'ID'
                                        ]
                                    ]);

                                    $arValue['values'][$query['ID']] = $query['NAME'];
                                }
                                unset($arProp['VALUES']);
                                $boolUserType = true;
                                break;
							default:
								$boolUserType = false;
								break;
						}
					}

					if (!$boolUserType) {
						switch ($arProp['PROPERTY_TYPE']) {
							case 'N':
								$strFieldType = 'double';
								$arLogic = static::GetLogic(array(BT_COND_LOGIC_EGR, BT_COND_LOGIC_ELS));
								$arValue = array('type' => 'input');
								break;
							case 'S':
								$strFieldType = 'text';
								$arLogic = static::GetLogic(array(BT_COND_LOGIC_EQ));
								$arValue = array('type' => 'input');
								if (in_array($arProp['DISPLAY_TYPE'], array('F', 'G', 'H'))) {
									$arLogic = static::GetLogic(array(BT_COND_LOGIC_EQ));
									$arValue = array(
										'type' => 'select',
										'values' => array(CNetexConditionTree::ALL_VALUES => CNetexConditionTree::ALL_VALUES)
									);

                                    if ($arProp['USER_TYPE'] == 'directory') {
                                        if(!Loader::includeModule('highloadblock'))
                                            break;

                                        $isSerialized = unserialize($arProp['USER_TYPE_SETTINGS']);
                                        if($isSerialized)
                                            $arProp['USER_TYPE_SETTINGS'] = $isSerialized;

                                        $hlblock = Bitrix\Highloadblock\HighloadBlockTable::getList(
                                            array(
                                                'filter' => array(
                                                    'TABLE_NAME' => $arProp['USER_TYPE_SETTINGS']['TABLE_NAME']
                                                )
                                            )
                                        )->fetch();
                                        $entity = Bitrix\Highloadblock\HighloadBlockTable::compileEntity($hlblock);
                                        $entity_data_class = $entity->getDataClass();
                                        $arTable = $entity_data_class::getList()->fetchAll();
                                        $arValues = Array();

                                        foreach($arTable as $arRow)
                                            $arValues[$arRow['UF_XML_ID']] = $arRow['UF_NAME'];

                                        foreach ($arProp['VALUES'] as &$arOnePropValue) {
                                            $arValue['values'][$arOnePropValue] = $arValues[$arOnePropValue];
                                        }
                                    } elseif ($arProp['USER_TYPE'] == 'NETEX_CHECKBOX') {
                                        $arValue['values'] = $arProp['USER_TYPE_SETTINGS']['VIEW'];
                                    } else {
                                        foreach ($arProp['VALUES'] as &$arOnePropValue) {
                                            $arValue['values'][$arOnePropValue] = $arOnePropValue;
                                        }
                                    }

									if (isset($arOnePropValue))
										unset($arOnePropValue);
									$arPhpValue = array('VALIDATE' => 'list');
								}
								break;
							case 'L':
								$strFieldType = 'int';
								$arLogic = static::GetLogic(array(BT_COND_LOGIC_EQ));
								$arValue = array(
									'type' => 'select',
									'values' => array(CNetexConditionTree::ALL_VALUES_KEY => CNetexConditionTree::ALL_VALUES)
								);
								foreach ($arProp['VALUES'] as &$arOnePropValue)	{
									$arValue['values'][$arOnePropValue['ID']] = $arOnePropValue['VALUE'];
								}
								if (isset($arOnePropValue))
									unset($arOnePropValue);
								$arPhpValue = array('VALIDATE' => 'list');
								break;
							case 'E':
								$strFieldType = 'int';
								$arLogic = static::GetLogic(array(BT_COND_LOGIC_EQ));

                                $arValue = array(
                                    'type' => 'select',
                                    'values' => array(CNetexConditionTree::ALL_VALUES_KEY => CNetexConditionTree::ALL_VALUES)
                                );
                                $linkIblockId = PropertyTable::getRowById($arProp['ID'])['LINK_IBLOCK_ID'];
                                $iBlockProp = ElementTable::getList(Array('filter' => Array('IBLOCK_ID' => $linkIblockId, 'ACTIVE' => 'Y')));
                                while($prop = $iBlockProp->fetch()) {
                                    $arValue['values'][$prop['ID']] = $prop['NAME'];
                                }

                                if (isset($arOnePropValue))
                                    unset($arOnePropValue);
                                $arPhpValue = array('VALIDATE' => 'list');
								break;
							case 'G':
								$strFieldType = 'int';
								$arLogic = static::GetLogic(array(BT_COND_LOGIC_EQ));
								$arValue = array(
									'type' => 'popup',
									'popup_url' =>  '/bitrix/admin/cat_section_search.php',
									'popup_params' => array(
										'lang' => LANGUAGE_ID,
										'IBLOCK_ID' => $arProp['LINK_IBLOCK_ID'],
										'discount' => 'Y'
									),
									'param_id' => 'n'
								);
								$arPhpValue = array('VALIDATE' => 'section');
								break;
						}
					}
					$arControlList['CondIBProp:'.$intIBlockID.':'.$arProp['ID']] = array(
						'ID' => 'CondIBProp:'.$intIBlockID.':'.$arProp['ID'],
						'PARENT' => false,
						'EXIST_HANDLER' => 'Y',
						'MODULE_ID' => 'catalog',
						'MODULE_ENTITY' => 'iblock',
						'ENTITY' => 'ELEMENT_PROPERTY',
						'IBLOCK_ID' => $intIBlockID,
                        'PROPERTY_ID'=> $arProp['ID'],
						'FIELD' => 'PROPERTY_'.$arProp['ID'].'_VALUE',
						'FIELD_TABLE' => $intIBlockID.':'.$arProp['ID'],
						'FIELD_TYPE' => $strFieldType,
						'MULTIPLE' => 'Y',
						'GROUP' => 'N',
						'SEP' => ($boolSep ? 'Y' : 'N'),
						'SEP_LABEL' => ($boolSep ? str_replace(array('#ID#', '#NAME#'), array($intIBlockID, $strName), Loc::getMessage('BT_MOD_CATALOG_COND_CMP_CATALOG_PROP_LABEL')) : ''),
						'LABEL' => $arProp['NAME'],
						'PREFIX' => str_replace(array('#NAME#', '#IBLOCK_ID#', '#IBLOCK_NAME#'), array($arProp['NAME'], $intIBlockID, $strName), Loc::getMessage('BT_MOD_CATALOG_COND_CMP_CATALOG_ONE_PROP_PREFIX')),
						'LOGIC' => $arLogic,
						'JS_VALUE' => $arValue,
						'PHP_VALUE' => $arPhpValue
					);

					$boolSep = false;
				}
			}
		}
		if (isset($intIBlockID))
			unset($intIBlockID);
		unset($arIBlockList);

        Loader::includeModule('catalog');
        $priceTypes = GroupTable::getList(array(
            'order' => array(
                'SORT' => 'ASC',
                'ID' => 'ASC',
            ),
            'select' => array(
                'ID',
                'TITLE' => 'CURRENT_LANG.NAME',
            )
        ));
        $boolSep = true;
        while ($raw = $priceTypes->fetch()) {
            $arControlList['CondPrice:' . $raw['ID']] = array(
                'ID' => 'CondPrice:' . $raw['ID'],
                'PARENT' => false,
                'EXIST_HANDLER' => 'Y',
                'MODULE_ID' => 'catalog',
                'MODULE_ENTITY' => 'price',
                'ENTITY' => 'PRODUCT_PRICE',
                'FIELD' => 'CATALOG_PRICE'.  $raw['ID'],
                'FIELD_TABLE' => $raw['ID'],
                'FIELD_TYPE' => 'double',
                'MULTIPLE' => 'Y',
                'GROUP' => 'N',
                'SEP' => ($boolSep ? 'Y' : 'N'),
                'SEP_LABEL' => Loc::getMessage('NETEX_SEOFILTER_PRICE_TYPES'),
                'LABEL' => $raw['TITLE'],
                'PREFIX' => $raw['TITLE'],
                'LOGIC' => static::GetLogic(array(BT_COND_LOGIC_EGR, BT_COND_LOGIC_ELS)),
                'JS_VALUE' => array('type' => 'input'),
            );

            $boolSep = false;
        }

		if ($strControlID === false) {
			return $arControlList;
		} elseif (isset($arControlList[$strControlID]))	{
			return $arControlList[$strControlID];
		} else {
			return false;
		}
	}

	public static function GetControlDescr()
	{
		$strClassName = get_called_class();
		return array(
			'COMPLEX' => 'Y',
			'GetControlShow' => array($strClassName, 'GetControlShow'),
			'GetConditionShow' => array($strClassName, 'GetConditionShow'),
			'IsGroup' => array($strClassName, 'IsGroup'),
			'Parse' => array($strClassName, 'Parse'),
			'Generate' => array($strClassName, 'Generate'),
			'ApplyValues' => array($strClassName, 'ApplyValues'),
			'InitParams' => array($strClassName, 'InitParams'),
			'CONTROLS' => static::GetControls()
		);
	}
}

class CNetexConditionTree extends CCatalogCondTree
{
	const ALL_VALUES_KEY = -1;
	const ALL_VALUES = 'Любое значение';
	protected function GetEventList($intEventID)
	{
		$arEventList = array(
			BT_COND_BUILD_CATALOG => array(
				'ATOMS' => array(
					'MODULE_ID' => 'netex.seofilter',
					'EVENT_ID' => 'OnCondCatAtomBuildList'
				),
				'CONTROLS' => array(
					'MODULE_ID' => 'netex.seofilter',
					'EVENT_ID' => 'OnCondCatControlBuildList'
				)
			),
		);

		return (isset($arEventList[$intEventID]) ? $arEventList[$intEventID] : false);
	}

	public function getStrPrefix()
	{
		return $this->strPrefix;
	}

	public function Parse($arData = '', $params = false)
	{
		return parent::Parse($arData, $params);
	}
}



class CNetexAdmin
{
	public static function IBlockInheritedLandingInput($iblock_id, $section_id, $id, $data, $type, $checkboxLabel = "")
	{
		$inherited = false;
		$inputId = "IPROPERTY_TEMPLATES_".$id;
		$inputName = "IPROPERTY_TEMPLATES[".$id."][TEMPLATE]";
		$menuId = "mnu_IPROPERTY_TEMPLATES_".$id;
		$resultId = "result_IPROPERTY_TEMPLATES_".$id;
		$checkboxId = "ck_IPROPERTY_TEMPLATES_".$id;

        switch ($type)
        {
            case 'L':
                $u = new CAdminPopupEx(
                    $menuId,
                    self::GetInheritedLandingTemplateMenuItems($iblock_id, $section_id, "InheritedPropertiesTemplates.insertIntoInheritedPropertiesTemplate", $menuId, $inputId),
                    array("zIndex" => 2000)
                );
                $result = $u->Show(true)
                    .'<script>
                        window.ipropTemplates[window.ipropTemplates.length] = {
                        "ID": "'.$id.'",
                        "INPUT_ID": "'.$inputId.'",
                        "RESULT_ID": "'.$resultId.'",
                        "TEMPLATE": ""
                        };
                    </script>'
                    .'<input type="hidden" name="'.$inputName.'" value="'.htmlspecialcharsbx($data).'" />'
                    .'<textarea onclick="InheritedPropertiesTemplates.enableTextArea(\''.$inputId.'\')" name="'.$inputName.'" id="'.$inputId.'" '.($inherited? 'readonly="readonly"': '').' cols="55" rows="2" style="width:80%">'
                    .htmlspecialcharsbx($data)
                    .'</textarea>'
                    .'<input style="float:right" type="button" id="'.$menuId.'" '.($inherited? 'disabled="disabled"': '').' value="...">'
                    .'<br>'
                ;
                break;

            case 'T':
                $u = new CAdminPopupEx(
                    $menuId,
                    self::getUrlTemplateMenu($iblock_id, $section_id, "InheritedPropertiesTemplates.insertIntoInheritedPropertiesTemplate", $menuId, $inputId),
                    array("zIndex" => 2000)
                );
                $result = $u->Show(true)
                    .'<script>
                        window.ipropTemplates[window.ipropTemplates.length] = {
                        "ID": "'.$id.'",
                        "INPUT_ID": "'.$inputId.'",
                        "RESULT_ID": "'.$resultId.'",
                        "TEMPLATE": ""
                        };
                    </script>'
                    .'<input type="hidden" name="'.$inputName.'" value="'.htmlspecialcharsbx($data).'" />'
                    .'<input type="text" onclick="InheritedPropertiesTemplates.enableTextArea(\''.$inputId.'\')" name="'.$inputName.'" id="'.$inputId.'" '.($inherited? 'readonly="readonly"': '').' cols="55" style="width:80%" value="'.htmlspecialcharsbx($data).'">'
                    .'<input style="float:right" type="button" id="'.$menuId.'" '.($inherited? 'disabled="disabled"': '').' value="...">'
                    .'<br>'
                ;
                break;

            default:
                $result = '<input type="hidden" name="'.$inputName.'" value="'.htmlspecialcharsbx($data).'" />'
                    .'<input
                        type="text"
                        name="'.$inputName.'"
                        id="'.$inputId.'" '.($inherited? 'readonly="readonly"': '').'
                        cols="55"
                        style="width:80%"
                        value = "'.htmlspecialcharsbx($data).'">';
                break;
        }


		return $result;
	}

	public static function GetInheritedLandingTemplateMenuItems($iblock_id, $section_id, $action_function, $menuID, $inputID = "")
	{
		$result = array();
		$result["section"] = array(
			"TEXT" => "Поля раздела",
			"MENU" => array(
				array(
					"TEXT" => "Название текущего раздела",
					"ONCLICK" => "$action_function('{=section.Name}', '$menuID', '$inputID')",
				),
				array(
					"TEXT" => "Название текущего раздела в нижнем регистре",
					"ONCLICK" => "$action_function('{=lower section.Name}', '$menuID', '$inputID')",
				),
				array(
					"TEXT" => "Символьный код текущего раздела",
					"ONCLICK" => "$action_function('{=section.Code}', '$menuID', '$inputID')",
				),
			),
		);

		$result["iblock"] = array(
			"TEXT" => "Инфоблок",
			"MENU" => array(
				array(
					"TEXT" => "Название инфоблока",
					"ONCLICK" => "$action_function('{=iblock.Name}', '$menuID', '$inputID')",
				),
				array(
					"TEXT" => "Символьный код инфоблока",
					"ONCLICK" => "$action_function('{=iblock.Code}', '$menuID', '$inputID')",
				),
			),
		);

		if ($iblock_id > 0)
		{
			$result["properties"] = array(
				"TEXT" => "Свойства раздела",
				"MENU" => array(),
			);

			$properties = CNetexCache::getInstance()->getVarCache($iblock_id.':'.$section_id);
			if (!$properties) {
				$properties = array();
				$rsProperty = CIBlockProperty::GetList(array(), array("IBLOCK_ID" => $iblock_id));
				while($property = $rsProperty->fetch())
				{
					$properties[] = $property;
				}
				CNetexCache::getInstance()->setVarCache($iblock_id.':'.$section_id, $properties);
			}

			foreach ($properties as $property)
			{
				if ($property["PROPERTY_TYPE"] != "F")
				{
					$result["properties"]["MENU"][] = array(
						"TEXT" => $property["NAME"],
						"ONCLICK" => "$action_function('{=property.".($property["CODE"]!=""? $property["CODE"]: $property["ID"])."}', '$menuID', '$inputID')",
					);
				}
			}
		}

		$arCatalog = false;
		if (CNetexSeoFilter::$catalogModule)
		{
			if ($iblock_id > 0)
				$arCatalog = \CCatalogSKU::GetInfoByIBlock($iblock_id);

			if (is_array($arCatalog))
			{
				$showCatalogSeo = ($arCatalog['CATALOG_TYPE'] != \CCatalogSKU::TYPE_PRODUCT);
				if ($arCatalog['CATALOG_TYPE'] == \CCatalogSKU::TYPE_PRODUCT || $arCatalog['CATALOG_TYPE'] == \CCatalogSKU::TYPE_FULL)
				{
					$result["sku_properties"] = array(
						"TEXT" => "Свойства SKU",
						"MENU" => array(),
					);
					$properties = CNetexCache::getInstance()->getVarCache($arCatalog["IBLOCK_ID"].':'.$section_id);
					if (!$properties) {
						$properties = array();
						$rsProperty = CIBlockProperty::GetList(array(), array("IBLOCK_ID" => $arCatalog["IBLOCK_ID"]));
						while($property = $rsProperty->fetch())
						{
							$properties[] = $property;
						}
						CNetexCache::getInstance()->setVarCache($arCatalog["IBLOCK_ID"].':'.$section_id, $properties);
					}


					foreach($properties as $property)
					{
						if ($property["PROPERTY_TYPE"] != "F")
						{
							$result["sku_properties"]["MENU"][] = array(
								"TEXT" => $property["NAME"],
								"ONCLICK" => "$action_function('{= property.".($property["CODE"] != "" ? $property["CODE"] : $property["ID"])." }', '$menuID', '$inputID')",
							);
						}
					}
				}

				if (false && $showCatalogSeo)
				{
					$result["price"] = array(
						"TEXT" => "Цены",
						"MENU" => array(),
					);
					foreach (self::getCatalogPrices() as $price)
					{
						if (preg_match("/^[a-zA-Z0-9]+\$/", $price["NAME"]))
							$result["price"]["MENU"][] = array(
								"TEXT" => $price["NAME"],
								"ONCLICK" => "$action_function('{=this.catalog.price.".$price["NAME"]."}', '$menuID', '$inputID')",
							);
						else
							$result["price"]["MENU"][] = array(
								"TEXT" => $price["NAME"],
								"ONCLICK" => "$action_function('{=this.catalog.price.".$price["ID"]."}', '$menuID', '$inputID')",
							);
					}
					$result["store"] = array(
						"TEXT" => "Магазин",
						"MENU" => array(),
					);
					foreach (self::getCatalogStores() as $store)
					{
						$result["store"]["MENU"][] = array(
							"TEXT" => ($store["TITLE"] != '' ? $store["TITLE"] : $store["ADDRESS"]),
							"ONCLICK" => "$action_function('{=catalog.store.".$store["ID"].".name}', '$menuID', '$inputID')",
						);
					}
				}
			}
		}

        if(\Bitrix\Main\ModuleManager::isModuleInstalled('netex.domains') && Loader::includeModule('netex.domains'))
        {
            $rsProps = PropertyTable::getList(array(
                'filter' => array(
                    'IBLOCK_ID' => \Netex\Domains\Helper::getInstance()->getIblockId(),
                    '!CODE' => \Netex\Domains\Helper::getInstance()->getRejectedProperties(),
                    'ACTIVE' => 'Y',
                )
            ));

            $result['domain_properties'] = array(
                'TEXT' => 'Свойства домена',
                'MENU' => array(
                    array(
                        'TEXT' => 'Название',
                        'ONCLICK' => "$action_function('{=domainProperty \"NAME\"}', '$menuID', '$inputID')",
                    )
                ),
            );
            while($prop = $rsProps->fetch())
            {
                $result['domain_properties']['MENU'][] = array(
                    'TEXT' => $prop['NAME'],
                    'ONCLICK' => "$action_function('{=domainProperty \"" . $prop['CODE'] . "\"}', '$menuID', '$inputID')",
                );
            }
        }

		$r = array();
		foreach($result as $category)
		{
			if (!empty($category) && !empty($category["MENU"]))
			{
				$r[] = $category;
			}
		}
		return $r;
	}

    public static function getUrlTemplateMenu($iBlockId, $sectionId, $action_function, $menuID, $inputID = "")
    {
        $arMenu = array(
            'SERVER' => Array(
                'TEXT' => 'Поля сервера',
                'MENU' => Array(
                    array(
                        "TEXT" => 'Корневая папка сервера',
                        "ONCLICK" => "$action_function('#SITE_DIR#', '$menuID', '$inputID')",
                    ),
                    array(
                        "TEXT" => 'URL сервера',
                        "ONCLICK" => "$action_function('#SERVER_NAME#', '$menuID', '$inputID')",
                    ),
                ),
            ),
            'IBLOCK' => Array(
                'TEXT' => 'Поля инфоблока',
                'MENU' => Array(
                    array(
                        "TEXT" => 'ID инфоблока',
                        "ONCLICK" => "$action_function('#IBLOCK_ID#', '$menuID', '$inputID')",
                    ),
                    array(
                        "TEXT" => 'Символьный код инфоблока',
                        "ONCLICK" => "$action_function('#IBLOCK_CODE#', '$menuID', '$inputID')",
                    ),
                    array(
                        "TEXT" => 'Внешний код инфоблока',
                        "ONCLICK" => "$action_function('#IBLOCK_EXTERNAL_ID#', '$menuID', '$inputID')",
                    ),
                ),
            ),
            'SECTION' => Array(
                'TEXT' => 'Поля раздела',
                'MENU' => Array(
                    array(
                        "TEXT" => 'ID раздела',
                        "ONCLICK" => "$action_function('#SECTION_ID#', '$menuID', '$inputID')",
                    ),
                    array(
                        "TEXT" => 'Символьный код текущего раздела',
                        "ONCLICK" => "$action_function('#SECTION_CODE#', '$menuID', '$inputID')",
                    ),
                    array(
                        "TEXT" => 'Путь из символьных кодов раздела',
                        "ONCLICK" => "$action_function('#SECTION_CODE_PATH#', '$menuID', '$inputID')",
                    ),
                ),
            ),
        );

        if ($iBlockId > 0)
        {
            $arMenu['PROPERTIES'] = array(
                "TEXT" => "Свойства раздела",
                "MENU" => array(),
            );

            $properties = CNetexCache::getInstance()->getVarCache($iBlockId.':'.$sectionId);
            if (!$properties) {
                $properties = array();
                $rsProperty = CIBlockProperty::GetList(array(), array("IBLOCK_ID" => $iBlockId));
                while($property = $rsProperty->fetch())
                {
                    $properties[] = $property;
                }
                CNetexCache::getInstance()->setVarCache($iBlockId.':'.$sectionId, $properties);
            }

            foreach ($properties as $property)
            {
                if ($property["PROPERTY_TYPE"] != "F")
                {
                    $arMenu['PROPERTIES']['MENU'][] = array(
                        "TEXT" => $property["NAME"],
                        "ONCLICK" => "$action_function('#PROPERTY_".($property["CODE"]!=""? $property["CODE"]: $property["ID"])."#', '$menuID', '$inputID')",
                    );
                }
            }
        }

        return $arMenu;
    }

	static $catalogPriceCache = null;
	private static function getCatalogPrices()
	{
		if (!isset(self::$catalogPriceCache))
		{
			self::$catalogPriceCache = array();

			if (CNetexSeoFilter::$catalogModule)
			{
				$rsPrice = \CCatalogGroup::GetListEx(array("SORT"=>"ASC"), array(), false, false, array("ID", "NAME"));
				while ($price = $rsPrice->Fetch())
				{
					self::$catalogPriceCache[] = $price;
				}
			}
		}
		return self::$catalogPriceCache;
	}

	static $catalogStoreCache = null;
	private static function getCatalogStores()
	{
		if (!isset(self::$catalogStoreCache))
		{
			self::$catalogStoreCache = array();
			if (CNetexSeoFilter::$catalogModule)
			{
				$storeCount = 0;
				$maxStores = (int)COption::GetOptionString("iblock", "seo_max_stores");
				$rsStore = CCatalogStore::GetList(array('SORT' => 'ASC'), array(), false, false, array('ID', 'TITLE', 'ADDRESS'));
				while ($store = $rsStore->Fetch())
				{
					self::$catalogStoreCache[$storeCount] = $store;
					$storeCount++;
					if ($maxStores > 0 && $storeCount >= $maxStores)
					{
						break;
					}
				}
			}
		}
		return self::$catalogStoreCache;
	}
}
