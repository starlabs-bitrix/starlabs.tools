<?php
namespace Starlabs\Tools;

use Bitrix\Main\Event;

const BASE_DIR = __DIR__;

$event = new Event('starlabs.tools', 'onModuleInclude');
$event->send();
