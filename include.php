<?php
namespace Starlabs\Tools;

use Bitrix\Main\Event;

$event = new Event('starlabs.tools', 'onModuleInclude');
$event->send();
