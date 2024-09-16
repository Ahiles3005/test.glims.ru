<?php
// @codingStandardsIgnoreStart
IncludeModuleLangFile(__FILE__);

class CRoistat
{
    function OnEndBufferContentHandler(&$content)
    {
        if (defined('ADMIN_SECTION'))
            return;
        $PROJECT_ID = COption::GetOptionString('roistat.integration', 'PROJECT_ID');
        $availableSites = json_decode(COption::GetOptionString('roistat.integration', 'AVAILABLE_SITES_LIST'), true);
        $counterKeys = json_decode(COption::GetOptionString('roistat.integration', 'COUNTER_KEYS_LIST'), true);
        $isMatches = preg_match("/'SITE_ID':'([\d\w]{2})'/", $content, $matches);
        $siteId = null;

        if ($isMatches && count($matches) > 1) {
            $siteId = $matches[1];
        }

        $counterKey = $counterKeys[$siteId];
        if ($PROJECT_ID !== null && $counterKey === null ) {
            $counterKey = $PROJECT_ID;
        }
        $escapedProjectId = CUtil::JSEscape($counterKey);

        $isDisableCounter = (array_key_exists($siteId, $availableSites) && !$availableSites[$siteId]) || $counterKey === '';

        if ($isDisableCounter) {
            return;
        }
        $js =
<<<JAVASCRIPT
    <script>
    (function(w, d, s, h, id) {
        w.roistatProjectId = id; w.roistatHost = h;
        var p = d.location.protocol == "https:" ? "https://" : "http://";
        var u = /^.*roistat_visit=[^;]+(.*)?$/.test(d.cookie) ? "/dist/module.js" : "/api/site/1.0/"+id+"/init?referrer="+encodeURIComponent(d.location.href);
        var js = d.createElement(s); js.charset="UTF-8"; js.async = 1; js.src = p+h+u; var js2 = d.getElementsByTagName(s)[0]; js2.parentNode.insertBefore(js, js2);
    })(window, document, 'script', 'cloud.roistat.com', '{$escapedProjectId}');
    </script>
JAVASCRIPT;
        $content = preg_replace("/<head>/", "<head>{$js}", $content, 1);
    }

    function __AddRoistatOrderProperty($ORDER_ID)
    {
        if (!array_key_exists('visit', $_REQUEST) && !array_key_exists('roistat_visit', $_COOKIE)) {
            return;
        }
        if (defined('ADMIN_SECTION'))
            return;
        if (!$arOrder = CSaleOrder::GetByID($ORDER_ID))
            return false;
        if (MakeTimeStamp($arOrder['DATE_INSERT'], "YYYY-MM-DD HH:MI:SS") < AddToTimeStamp(array('MI' => -5))) {
            return;
        }

        $rsProp = CSaleOrderProps::GetList(array(), array("PERSON_TYPE_ID" => $arOrder["PERSON_TYPE_ID"], "CODE" => "ROISTAT_VISIT"));
        if (!$arProp = $rsProp->GetNext())
            return;

        $arPropFields = array(
            "ORDER_ID" => $ORDER_ID,
            "ORDER_PROPS_ID" => $arProp["ID"],
            "NAME" => GetMessage('ROISTAT_PROPERTY_NAME'),
            "CODE" => "ROISTAT_VISIT",
            "VALUE" => array_key_exists('visit', $_REQUEST) ? $_REQUEST['visit'] : $_COOKIE["roistat_visit"]
        );


        $rsPropValue = CSaleOrderPropsValue::GetList(array(), array("ORDER_ID" => $ORDER_ID, "ORDER_PROPS_ID" => $arProp["ID"]));
        if ($arPropValue = $rsPropValue->GetNext())
            CSaleOrderPropsValue::Update($arPropValue["ID"], $arPropFields); else
            CSaleOrderPropsValue::Add($arPropFields);
    }

    function OnOrderSaveHandler($ID, $arFields)
    {
        CRoistat::__AddRoistatOrderProperty($ID);
    }

    /**
     * @param \Bitrix\Main\Event $event
     */
    function OnSaleOrderSavedHandler(Bitrix\Main\Event $event)
    {
        $order = $event->getParameter('ENTITY');
        CRoistat::__AddRoistatOrderProperty($order->getId());
    }

    function OnOrderNewSendEmailHandler($ID, &$eventName, &$arFields)
    {
        CRoistat::__AddRoistatOrderProperty($ID);
    }
}
// @codingStandardsIgnoreEnd