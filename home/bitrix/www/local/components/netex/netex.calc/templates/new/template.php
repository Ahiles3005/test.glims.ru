<?php
$hash = filemtime('/web/docs/local/components/netex/netex.calc/templates/new/assets/js/mainifest.js');
?>

<div id="app"
     data-properties="<?=\CUtil::PhpToJSObject($arResult["PROPERTIES"])?>"
     data-min="<?=$arResult["MIN"]?>"
     data-max="<?=$arResult["MAX"]?>"></div>

<script src="/local/components/netex/netex.calc/templates/new/assets/js/mainifest.js?<?=$hash?>"></script>
<script src="/local/components/netex/netex.calc/templates/new/assets/js/bundle.js?<?=$hash?>"></script>
