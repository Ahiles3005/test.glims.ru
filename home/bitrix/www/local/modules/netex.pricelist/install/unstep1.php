<?php
/**
 * Created by PhpStorm.
 * User: vtsurka
 * Date: 12/30/15
 * Time: 4:01 PM
 */
use Bitrix\Main\Localization\Loc;
if (!check_bitrix_sessid())
    return;


Loc::loadMessages(__FILE__);
?>

<form action="<?=$APPLICATION->GetCurPage();?>">
    <?=bitrix_sessid_post();?>
    <input type="hidden" name="lang" value="<?=LANGUAGE_ID?>">
    <input type="hidden" name="id" value="<?=$Module->MODULE_ID?>">
    <input type="hidden" name="uninstall" value="Y">
    <input type="hidden" name="step" value="2">
    <?php ShowMessage(Loc::getMessage("MOD_UNINST_WARN")); ?>
    <p><?=Loc::getMessage("MOD_UNINST_SAVE");?></p>
    <p><input type="checkbox" name="savedata" value="Y" checked> <?=Loc::getMessage("MOD_UNINST_SAVE_TABLES");?></p>
    <input type="submit" name="" value="<?=Loc::getMessage("MOD_UNINST_DEL");?>">
</form>
