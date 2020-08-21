<? if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) {
	die();
}

if (!check_bitrix_sessid()) {
	return;
}

$Query = \Bitrix\Main\SiteTable::query();
$Result = $Query
	->setSelect(['NAME', 'LID'])
	->exec();
?>

<form action="<?= $APPLICATION->GetCurPage(); ?>">
	<?= bitrix_sessid_post() ?>
	<input type="hidden" name="lang" value="<?= LANG ?>">
	<input type="hidden" name="id" value="starlabs.tools">
	<input type="hidden" name="step" value="2">
	<input type="hidden" name="install" value="Y">
	<p><?= GetMessage('SLTOOLS_MODULE_STEP_ACTION_TITLE'); ?></p>
	<? while ($ar = $Result->fetch()) { ?>
		<p>
			<input type="checkbox" name="SITE_ID[]" id="SITE_<?= $ar['LID'] ?>" value="<?= $ar['LID'] ?>" checked>
			<label for="SITE_<?= $ar['LID'] ?>"><?= $ar['NAME'] ?></label>
		</p>
	<? } ?>

	<input type="submit" value="<?= GetMessage('SLTOOLS_MODULE_STEP_BUTTON_TITLE'); ?>">
</form>


