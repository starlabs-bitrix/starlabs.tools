<?php

use Bitrix\Main\Loader;
use Starlabs\Tools\Ajax\Router;

define('NO_KEEP_STATISTIC', 'Y');
define('NO_AGENT_STATISTIC', 'Y');
define('NO_AGENT_CHECK', true);
define('PUBLIC_AJAX_MODE', true);
define('DisableEventsCheck', true);
#Если модуль стоит на Б24, убрать комент. Права доступа на папки, не позволяют отрабатывать скрипт и кидает на форму авторизации.
//define('NOT_CHECK_PERMISSIONS', true);

require_once $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php';

if (!Loader::includeModule('starlabs.tools')) {
   die();
}

try {
   $Router = new Router();
   $Router->Action();
} catch (Exception $e) {
	print $e->GetMessage();
}

require_once $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/epilog_after.php';
