<?

use Bitrix\Main\Localization\Loc;

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) {
	die();
}
if (!check_bitrix_sessid()) {
	return;
}

CAdminMessage::ShowNote(Loc::getMessage('SLTOOLS_MODULE_STEP_FINISH'));
