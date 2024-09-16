<?php
/**
 * Created by PhpStorm.
 * User: Nikolay Starostenko
 * Date: 01.06.18
 * Time: 13:03
 */

namespace Netex\Export;

//use Illuminate\Support\Facades\Log;
use Bitrix\Catalog\PriceTable;
use Bitrix\Main\Application;
use Bitrix\Main\Loader;
use Bitrix\Main\FileTable;
use Bitrix\Catalog\ProductTable;
use Bitrix\Main\Type\DateTime;
use Bitrix\Iblock\Template;
use Netex\Domains\Domain;
use Netex\Export\Orm\ContentTable;
use Netex\Export\Orm\OffersTable;
use Netex\Export\Orm\ProfileTable;
use Monolog\Registry;

Loader::includeModule('catalog');
Loader::includeModule('iblock');

/**
 * Класс реализует функционал модуля
 *
 * Class Export
 * @package Netex\Export
 */
class Export
{
    /** @var self $instance */
    protected static $instance;
    /** @var array $iblockInfo */
    public $iblockInfo = [];
    /** @var array $arProfiles */
    public $arProfiles = [];
    /** @var bool|null|string $uploadDir*/
    public $uploadDir = '';
    /** @var \Monolog\Logger $logger */
//    protected $logger;

    /**
     * Получает информацию о каталоге, профилях экспорта
     *
     * Export constructor.
     */
    private function __construct()
    {
        $id = \COption::GetOptionString('netex.export', 'catalog_iblock_id');
        $this->iblockInfo = \CCatalogSKU::GetInfoByProductIBlock($id);

//        $this->logger = Log::channel('export_yandex_market');

        $resProfiles = ProfileTable::getList(['filter' => ['ACTIVE' => 'Y']]);
        while ($arProfile = $resProfiles->fetch()) {
            if (Loader::includeModule('netex.domains')) {
                $arProfile['DOMAIN_OBJECT'] = new Domain($arProfile['DOMAIN']);
            }
            $this->arProfiles[$arProfile['ID']] = $arProfile;
        }

        $this->uploadDir = \COption::GetOptionString("main", "upload_dir", "upload");
    }

    /**
     * Получение экземляра класса
     *
     * @return Export
     */
    public static function getInstance()
    {
        if (self::$instance === null) {
            self::$instance = new self;
        }

        return self::$instance;
    }

    /**
     * Полный реиндекс торговых предложений профиля
     *
     * @param $id
     * @return bool
     */
    public function reindex($id)
    {
        if (empty($id)) return false;
        $arProfile = $this->arProfiles[$id];

        Application::getConnection()->query('DELETE FROM ' . OffersTable::getTableName() . ' WHERE PROFILE_ID="' . $id . '"');
        Application::getConnection()->query('DELETE FROM ' . ContentTable::getTableName() . ' WHERE PROFILE_ID="' . $id . '"');

        $iblock = new \CIBlockElement();

        $arFilter = [
            'IBLOCK_ID' => $this->iblockInfo['PRODUCT_IBLOCK_ID']
        ];
        if (is_array($this->arProfiles[$id]['FILTER'])) {
            $arFilter = array_merge($this->arProfiles[$id]['FILTER'], $arFilter);
        }

        $resProduct = $iblock::GetList([], $arFilter, false, false, ['ID']);

        while ($arProduct = $resProduct->Fetch()) {
            $resSCU = $iblock::GetList([], [
                'PROPERTY_CML2_LINK' => $arProduct['ID'],
                'IBLOCK_ID' => $this->iblockInfo['IBLOCK_ID']
            ]);

            if ($arProfile['OFFERS']) {
                while ($rowSCU = $resSCU->GetNextElement()) {
                    $arOffer = $rowSCU->GetFields();
                    $arOffer['PROPERTY_VALUES'] = $rowSCU->GetProperties([], ['CODE' => 'CML2_LINK']);

                    OffersTable::add([
                        'PROFILE_ID' => $id,
                        'OFFER_ID' => $arOffer['ID'],
                        'PRODUCT_ID' => $arOffer['PROPERTY_VALUES']['CML2_LINK']['VALUE'],
                        'CHANGED' => 'Y',
                    ]);
                }
            } else {
                OffersTable::add([
                    'PROFILE_ID' => $id,
                    'OFFER_ID' => 0,
                    'PRODUCT_ID' => $arProduct['ID'],
                    'CHANGED' => 'Y',
                ]);
            }
        }

        return true;
    }

    /**
     * Генерация контента для измененных и добавленых индексов
     *
     * @var int $step
     */
    public function generateContent($step = 0)
    {
        $arQueryParams = ['filter' => ['CHANGED' => 'Y'], 'select' => ['ID', 'PRODUCT_ID', 'OFFER_ID', 'PROFILE_ID']];
        if ($step) {
            $arQueryParams['limit'] = $step;
        }

        $arOfferTable = [];
        $resOffers = OffersTable::getList($arQueryParams);
        while ($arOffer = $resOffers->fetch()) {
            $arOfferTable[$arOffer['PRODUCT_ID']]['PRODUCT_ID'] = $arOffer['PRODUCT_ID'];
            $arOfferTable[$arOffer['PRODUCT_ID']]['IDS'][$arOffer['OFFER_ID']][$arOffer['PROFILE_ID']] = $arOffer['ID'];
        }

        foreach ($arOfferTable as $arOffer) {
            $arContent = $this->getContent($arOffer['PRODUCT_ID']);

            foreach ($arContent as $profileId => $arOffersContent) {
                foreach ($arOffersContent as $offerId => $content) {
                    if (isset($arOfferTable[$arOffer['PRODUCT_ID']]['IDS'][0][$profileId])) {
                        $offerId = 0;
                    }

                    $arFields = [
                        'PROFILE_ID' => $profileId,
                        'OFFER_ID' => $offerId,
                        'PRODUCT_ID' => $arOffer['PRODUCT_ID'],
                    ];

                    if (empty($content)) {
                        $id = ContentTable::getRow([
                            'filter' => $arFields
                        ])['ID'];
                        if ($id) ContentTable::delete($id);
                    } else {
                        $arFields['CONTENT'] = $content;
                        ContentTable::add($arFields);
                    }

                    if (!empty($arOfferTable[$arOffer['PRODUCT_ID']]['IDS'][$offerId][$profileId])) {
                        OffersTable::update($arOfferTable[$arOffer['PRODUCT_ID']]['IDS'][$offerId][$profileId], [
                            'CHANGED' => 'N',
                            'GENERATE_DATE' => new DateTime()
                        ]);
                    }
                }
            }
        }
    }

    /**
     * Переиндексация контента
     *
     * @var int $step
     */
    public function generateContentAll($step = 0)
    {
        $arQueryParams = ['select' => ['ID', 'PRODUCT_ID', 'OFFER_ID', 'PROFILE_ID']];
        if ($step) {
            $arQueryParams['limit'] = $step;
        }

        $arOfferTable = [];
        $resOffers = OffersTable::getList($arQueryParams);
        while ($arOffer = $resOffers->fetch()) {
            $arOfferTable[$arOffer['PRODUCT_ID']]['PRODUCT_ID'] = $arOffer['PRODUCT_ID'];
            $arOfferTable[$arOffer['PRODUCT_ID']]['IDS'][$arOffer['OFFER_ID']][$arOffer['PROFILE_ID']] = $arOffer['ID'];
        }

        foreach ($arOfferTable as $arOffer) {
            $arContent = $this->getContent($arOffer['PRODUCT_ID']);

            foreach ($arContent as $profileId => $arOffersContent) {
                foreach ($arOffersContent as $offerId => $content) {
                    if ($arOfferTable[$arOffer['PRODUCT_ID']]['IDS'][$offerId][$profileId]) {
                        $arFields = [
                            'PROFILE_ID' => $profileId,
                            'OFFER_ID' => $offerId,
                            'PRODUCT_ID' => $arOffer['PRODUCT_ID'],
                        ];

                        if (empty($content)) {
                            $id = ContentTable::getRow([
                                'filter' => $arFields
                            ])['ID'];
                            if ($id) ContentTable::delete($id);
                        } else {
                            $arFields['CONTENT'] = $content;
                            ContentTable::add($arFields);
                        }

                        OffersTable::update($arOfferTable[$arOffer['PRODUCT_ID']]['IDS'][$offerId][$profileId], [
                            'CHANGED' => 'N',
                            'GENERATE_DATE' => new DateTime()
                        ]);
                    }
                }
            }
        }
    }

    /**
     * Генерация файла для профиля
     *
     * @param int $profileId
     * @return bool
     */
    public function generateFile($profileId)
    {
        if (empty($profileId)) return false;

        $arContents = ContentTable::getList(['filter' => ['PROFILE_ID' => $profileId]])->fetchAll();
        $arProfile = ProfileTable::getRowById($profileId);
        $content = '';

        $content .= Export::getInstance()->getHeaderContent($arProfile) . "\r\n";

        foreach ($arContents as $arContent) {
            $content .= $arContent['CONTENT'] . "\r\n";
        }

        $content .= $arProfile['FOOTER_TEMPLATE'];

        file_put_contents(Application::getDocumentRoot() . '/export/' . $arProfile['NAME'] . '.xml', $content);
    }

    /**
     * Получение контента шапки файла для профиля
     *
     * @param $arProfile
     * @return mixed
     */
    public function getHeaderContent($arProfile) {
        $iblockId = $this->iblockInfo['PRODUCT_IBLOCK_ID'];

        $find = [
            '#date#',
            '#url#',
            '#categories#'
        ];

        $url = $arProfile['HTTPS'] ? 'https://' : 'http://';
        $url .= $arProfile['DOMAIN'];

        $arFilter = ['IBLOCK_ID' => $iblockId];
        if ($arProfile['FILTER']['!IBLOCK_SECTION_ID']) {
            $arFilter['!ID'] = $arProfile['FILTER']['!IBLOCK_SECTION_ID'];
        }

        $categories = [
            "<category id=\"1\">Все товары</category>",
            "<category id=\"2\" parentId=\"1\">Строительство и ремонт</category>",
            "<category id=\"{$iblockId}\" parentId=\"2\">Строительные материалы</category>"
        ];
        $objSections = \CIBlockSection::GetList(
            ['ID' => 'ASC'],
            $arFilter,
            false,
            ['ID', 'IBLOCK_SECTION_ID', 'NAME', 'UF_YM_CATEGORY']
        );
        while ($arSection = $objSections->Fetch()) {
            $categoryName = $arSection['UF_YM_CATEGORY'] ? $arSection['UF_YM_CATEGORY'] : $arSection['NAME'];
            $parentId = $arSection['IBLOCK_SECTION_ID'] ? $arSection['IBLOCK_SECTION_ID'] : $iblockId;
            $categories[] = "<category id=\"{$arSection['ID']}\" parentId=\"{$parentId}\">{$categoryName}</category>";
        }

        $replace = [
            date('Y-m-d H:i'),
            $url,
            implode("\r\n", $categories),
        ];

        return str_replace($find, $replace, $arProfile['HEADER_TEMPLATE']);
    }

    /**
     * Получение контента оффера
     *
     * @param $productId
     * @param int $offerId
     * @param array $arProfile
     * @return array
     * @throws \Exception
     */
    public function getContent($productId, $offerId = 0, $arProfile = [])
    {
        if (empty($productId)) {
            throw new \Exception('Не задан ID продукта!');
        }

        $arProfiles = $this->arProfiles;
        if ($arProfile) {
            // TODO: разобраться что это
            $arProfiles = [$arProfile];
        }

        $entity = new Template\Entity\Element($productId);

        $objProduct = \CIBlockElement::GetList([], ['ID' => $productId], false, false, ['*', 'WEIGHT'])->GetNextElement();
        $arProduct = $objProduct->GetFields();
        $arProduct['PROPERTY_VALUES'] = $objProduct->GetProperties();

        if ($offerId) {
            $resOffer = \CIBlockElement::GetList([], ['ID' => $offerId]);
        } else {
            $resOffer = \CIBlockElement::GetList([], ['PROPERTY_CML2_LINK' => $productId]);
        }

        $arContent = [];
        foreach ($arProfiles as $arProfile) {
            if ($arProfile['OFFERS'] == '0') {
                $this->setProductContent($arContent, $arProfile, $arProduct, $entity);
            }
        }

        $whileCnt = $foreachCnt = 0;
        while ($objOffer = $resOffer->GetNextElement()) {
            $whileCnt++;
            $arOffer = $objOffer->GetFields();
            $arOffer['PROPERTY_VALUES'] = $objOffer->GetProperties([], ['CODE' => 'SIZES_SHOES']);

            $offerAvailable = ProductTable::getList([
                'filter' => ['ID' => $arOffer['ID']],
                'select' => ['AVAILABLE'],
            ])->fetch()['AVAILABLE'];

            $objSectionChain = \CIBlockSection::GetNavChain(
                $arProduct['IBLOCK_ID'],
                $arProduct['IBLOCK_SECTION_ID'],
                ['ID', 'NAME', 'CODE']
            );
            $sex = '';
            while ($arSectionChainElement = $objSectionChain->Fetch()) {
                if ($arSectionChainElement['ID'] == 277) {
                    $sex = 'Женские';
                } elseif ($arSectionChainElement['ID'] == 278) {
                    $sex = 'Мужские';
                }

                break;
            }

            $price = $oldPrice = '';
            $resPrices = PriceTable::getList(['filter' => ['=PRODUCT_ID' => $arOffer['ID']]]);
            while ($arPrice = $resPrices->fetch()) {
                if ($arPrice['CATALOG_GROUP_ID'] == 1)
                    $price = (int) $arPrice['PRICE'];

                if ($arPrice['CATALOG_GROUP_ID'] == 2)
                    $oldPrice = (int) $arPrice['PRICE'];
            }

            foreach ($arProfiles as $arProfile) {
                if ($arProfile['OFFERS'] == '0') {
                    continue;
                }

                $foreachCnt++;
                /** @var Domain $domain */
                $domain = $arProfile['DOMAIN_OBJECT'];
                $arSettings = $arProfile['SETTINGS'];

                $content = "<offer id=\"{$arOffer['ID']}\" available=\"{$available}\" group_id=\"{$arProduct['ID']}\">\r\n";

                $protocol = $arProfile['HTTPS'] ? 'https://' : 'http://';

                $url = $protocol . $arProfile['DOMAIN'] . '/products/' . $arProduct['CODE'] . '/';
                if ($arSettings['OID'] !== false) {
                    $url .= '?oid=' . $arOffer['ID'];
                }

                $roistatUrl = '';
                if ($arSettings['SHOP_ID']) {
                    $roistatUrl = '&amp;utm_source=yandex_market&amp;utm_medium=cpc&amp;utm_campaign=' . $domain->getCode()
                        . '&amp;utm_term=' . $arSettings['SHOP_ID'] . '&amp;roistat=yamarket2_' . $arSettings['SHOP_ID'] . '_' . $arOffer['ID'];
                }

                $arRelationFields = [
                    'url' => $url . $roistatUrl,
                    'price' => $price,
                    'oldprice' => $oldPrice,
                    'currencyId' => 'RUR',
                    'categoryId' => $arProduct['IBLOCK_SECTION_ID'],
                    'pickup' => 'true',
                    'delivery' => 'true',
                    'store' => 'true',
                    'name' => implode(' ', [
                            $arProduct['PROPERTY_VALUES']['NS_PRODUCT_NAME']['VALUE'],
                            '{=this.property.BRAND_REF}',
                            $arProduct['PROPERTY_VALUES']['CML2_ARTICLE']['VALUE']
                        ]
                    ),
                    'vendor' => '{=this.property.BRAND_REF}',
                    'model' => $arProduct['PROPERTY_VALUES']['CML2_ARTICLE']['VALUE'],
                    'vendorCode' => $arProduct['PROPERTY_VALUES']['CML2_ARTICLE']['VALUE'],
                    'description' => "<![CDATA[{$arProduct['DETAIL_TEXT']}]]>",
					'sales_notes' => 'Бесплатная доставка при оплате онлайн от 4000 руб.',
                    'country_of_origin' => '{=this.property.MANUFACTURER}',
                ];

                if ($arSettings['HIDE_MODEL']) {
                    unset($arRelationFields['model']);
                }

                if ($domain->getCode() == 'moscow') $arRelationFields['store'] = '';
                foreach ($arRelationFields as $field => $value) {
                    if (empty($value)) continue;
                    $content .= "<{$field}>{$value}</{$field}>\r\n";
                }

                $arImagesSrc = [];
                $arImagesIds = [
					$arProduct['DETAIL_PICTURE']
                ];

                $fileTableFilter = $arImagesIds;

                $resImg = FileTable::getList([
                    'filter' => ['=ID' => $fileTableFilter]
                ]);
                while ($row = $resImg->Fetch()) {
                    $arImagesSrc[$row['ID']] = $protocol . $arProfile['DOMAIN'] . '/' . $this->uploadDir . '/' . $row['SUBDIR'] . '/' . $row['FILE_NAME'];
                }

				if ($arProduct['DETAIL_PICTURE'])
				  $content .= '<picture>' . $arImagesSrc[$arProduct['DETAIL_PICTURE']] . "</picture>\r\n";

                $arProps = [
                    'Тип рисунка' => $arProduct['PROPERTY_VALUES']['Osobennocti']['VALUE'],
                    'Пол' => $sex,
                    'Возраст' => 'Взрослые',
                ];
                foreach ($arProps as $name => $value) {
                    if (empty($value)) continue;
                    $content .= "<param name=\"{$name}\">{$value}</param>\r\n";
                }

                $arOther = [
                    'param name="Размер" unit="RU"' => [
                        'ENDTAG' => 'param',
                        'VALUE' => $arOffer['PROPERTY_VALUES']['SIZES_SHOES']['VALUE']
                    ],
                ];
                foreach ($arOther as $field => $arParams) {
                    if (empty($arParams['VALUE'])) continue;
                    $content .= "<{$field}>{$arParams['VALUE']}</{$arParams['ENDTAG']}>\r\n";
                }

                $arExcludedProps = [
                    'MANUFACTURER',
                    'CML2_ARTICLE',
                    'NS_PRODUCT_NAME',
                    'BRAND_REF',
                    'PRICE',
                    'FORUM_TOPIC_ID',
                    'FORUM_MESSAGE_CNT',
                    'BLOG_COMMENTS_CNT',
                    'BLOG_POST_ID',
                    'FORUM_MESSAGE_CNT',
                    'SKIP',
                    'DISCOUNT'
                ];
                foreach ($arProduct['PROPERTY_VALUES'] as $code => $arProp) {
                    if (in_array($code, $arExcludedProps) || empty($arProp['VALUE'])) continue;
                    $content .= "<param name=\"{$arProp['NAME']}\">{=this.property.{$arProp['CODE']}}</param>\r\n";
                }

                $content .= '</offer>';

                $content = Template\Engine::process($entity, $content);

                $arContent[$arProfile['ID']][$arOffer['ID']] = $content;
            }
        }

        return $arContent;
    }

    protected function setProductContent(&$arContent, $arProfile, $arProduct, $entity)
    {
        /** @var Domain $domain */
        $domain = $arProfile['DOMAIN_OBJECT'];
        $arSettings = $arProfile['SETTINGS'];

		$available = "true";
        if ($arProduct['PROPERTY_VALUES']['IN_STOCK']['VALUE'] != 'Y') {
            $available = "false";
        }

        if (((int) $arSettings['HIDE_NOT_AVAILABLE']) && $available === "false") {
            return false;
        }


        $content = "<offer id=\"{$arProduct['ID']}\" available=\"{$available}\">\r\n";

        $protocol = $arProfile['HTTPS'] ? 'https://' : 'http://';

        $url = $protocol . $arProfile['DOMAIN'] . '/products/' . $arProduct['CODE'] . '/';


        $roistatUrl = '';

        $price = '';
        $resPrices = PriceTable::getList(['filter' => ['=PRODUCT_ID' => $arProduct['ID']]]);
        while ($arPrice = $resPrices->fetch()) {
            if ($arPrice['CATALOG_GROUP_ID'] == 1)
                $price = (int) $arPrice['PRICE'];
        }
        $arRelationFields = [
            'url' => $url . $roistatUrl,
            'price' => $price,
            'weight' => empty($arProduct['WEIGHT']) ? null : (int)$arProduct['WEIGHT'] * 0.001,
            'currencyId' => 'RUR',
            'categoryId' => $arProduct['IBLOCK_SECTION_ID'],
            'pickup' => 'true',
            'delivery' => 'true',
            'store' => 'true',
            'vendor' => 'glims',
            'name' => $arProduct['NAME'],
            'description' => "<![CDATA[{$arProduct['DETAIL_TEXT']}]]>",
        ];

        if (!empty($arSettings['OFFER'])){
            foreach ($arSettings['OFFER'] as $moreProperties => $value){
                $arRelationFields[$moreProperties] = $value;
            }
        }

        if ($arSettings['HIDE_MODEL']) {
            unset($arRelationFields['model']);
        }

        foreach ($arRelationFields as $field => $value) {
            if (empty($value)) continue;
            $content .= "<{$field}>{$value}</{$field}>\r\n";
        }

        $arImagesSrc = [];
        $arImagesIds = [
            $arProduct['DETAIL_PICTURE']
        ];

        $fileTableFilter = $arImagesIds;

        $resImg = FileTable::getList([
            'filter' => ['=ID' => $fileTableFilter]
        ]);
        while ($row = $resImg->Fetch()) {
            $arImagesSrc[$row['ID']] = $protocol . $arProfile['DOMAIN'] . '/' . $this->uploadDir . '/' . $row['SUBDIR'] . '/' . $row['FILE_NAME'];
        }

        if ($arProduct['DETAIL_PICTURE'])
            $content .= '<picture>' . $arImagesSrc[$arProduct['DETAIL_PICTURE']] . "</picture>\r\n";

        $arExcludedProps = [
            'IN_STOCK',
            'MINIMUM_PRICE',
            'MAXIMUM_PRICE',
            'Kategoriya',
            'HIT',
            'BRAND',
            'POPUP_VIDEO',
            'PODBORKI',
            'SALE_TEXT',
            'CML2_ATTRIBUTES',
            'INSTRUCTIONS',
            'EXPANDABLES',
            'ASSOCIATED',
            'CML2_ARTICLE',
            'CML2_TAXES',
            'CML2_BASE_UNIT',
            'CML2_TRAITS',
            'FORUM_MESSAGE_CNT',
            'FORUM_TOPIC_ID',
            'SERVICES',
            'vote_sum',
            'rating',
            'VIDEO_YOUTUBE',
            'vote_count',
            'PROP_2033',
            'PROP_2033',
        ];

        if ($arSettings['USE_STOCK_100'] === 'Y') {
            $content .= '<param name="Stock" unit="ед">100</param>
                        <param name="Unit">ед</param>';
        }

        $content .= '</offer>';

        $content = Template\Engine::process($entity, $content);

        $arContent[$arProfile['ID']][$arProduct['ID']] = $content;
    }

    /**
     * Проходят ли товары по фильтру
     *
     * @param $element
     * @param $filter
     * @return bool
     */
    public function filtered($element, $filter) {
        foreach ($filter as $code => $value) {
            if (is_array($value)) {
                if (!in_array($element[$code], $value)) {
                    return false;
                }
            }

            if ($element[$code] != $value) {
                return false;
            }
        }

        return true;
    }
}
