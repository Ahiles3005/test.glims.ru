<?php
/**
 * Created by PhpStorm.
 * User: vtsurka
 * Date: 12/30/15
 * Time: 3:41 PM
 */

use \Bitrix\Main\Localization\Loc;

if (!check_bitrix_sessid()) {
    return;
}

if ($ex = $APPLICATION->GetException()) {
    ShowMessage(array(
        "TYPE" => "ERROR",
        "MESSAGE" => Loc::getMessage("MOD_UNINST_ERR"),
        "DETAILS" => $ex->GetString(),
        "HTML" => true
    ));
} else {
    ShowNote(Loc::getMessage("MOD_UNINST_OK"));
}
?>
<form action="<?=$APPLICATION->GetCurPage();?>">
    <input type="hidden" name="lang" value="<?=LANGUAGE_ID?>">
    <input type="submit" name="" value="<?=Loc::getMessage("MOD_BACK")?>">
</form>