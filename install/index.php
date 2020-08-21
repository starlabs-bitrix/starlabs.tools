<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) {
	die();
}

use \Bitrix\Main\{EventManager, Localization, ModuleManager};

Localization\Loc::loadMessages(__FILE__);

class starlabs_tools extends CModule
{
	public $MODULE_ID = 'starlabs.tools';
	public $MODULE_VERSION = '';
	public $MODULE_VERSION_DATE = '';
	public $MODULE_NAME = '';
	public $MODULE_DESCRIPTION = '';
	public $PARTNER_NAME = "StarLabs";
	public $PARTNER_URI = "http://starlabs.su/";

	public $Request;
	private $eventHandlers = [];

	public function __construct()
	{
		$this->Request = \Bitrix\Main\Context::getCurrent()->getRequest();
		$version = include __DIR__ . '/version.php';

		$this->MODULE_VERSION = $version['VERSION'];
		$this->MODULE_VERSION_DATE = $version['VERSION_DATE'];
		$this->MODULE_NAME = Localization\Loc::getMessage('SLTOOLS_MODULE_NAME');
		$this->MODULE_DESCRIPTION = Localization\Loc::getMessage('SLTOOLS_MODULE_DESCRIPTION');

		$this->eventHandlers = [
			[
				'main',
				'OnPageStart',
				'\Starlabs\Tools\Module',
				'onPageStart',
			]
		];
	}

	/**
	 * @return bool
	 */
	public function installFiles()
	{
		$moduleDir = explode('/', __DIR__);
		array_pop($moduleDir);
		$moduleDir = implode('/', $moduleDir);
		$sourceRoot = $moduleDir . '/install/';

		$parts = [
			'services' => [
				'target' => '/bitrix/services/starlabs.tools/',
				'rewrite' => false,
			],
			'components' => [
				'target' => '/bitrix/components/',
				'rewrite' => false,
			],
		];

		foreach ($parts as $dir => $config) {
			CopyDirFiles(
				$sourceRoot . $dir,
				$_SERVER['DOCUMENT_ROOT'] . $config['target'],
				$config['rewrite'],
				true
			);
		}

		return true;
	}

	/**
	 * Создаем правило для обработки ajax запросов
	 */
	public function AddUrlRewriterRule($arSites = [])
	{
		foreach ($arSites as $site) {
			Bitrix\Main\UrlRewriter::add($site, [
				'CONDITION' => '#^/ajax/#',
				'RULE' => '',
				'ID' => null,
				'PATH' => '/bitrix/services/starlabs.tools/ajax.php',
				'SORT' => 13,
			]);
		}
	}

	/**
	 * Удаление правила для обработки ajax запросов
	 */
	public function DeleteUrlRewriterRule()
	{
		$Query = \Bitrix\Main\SiteTable::query();
		$Result = $Query->setSelect(['LID'])->exec();

		while ($site = $Result->fetch()) {
			Bitrix\Main\UrlRewriter::delete($site['LID'], ['CONDITION' => '#^/ajax/#']);
		}

		return true;
	}

	/**
	 * @todo Удалять только те компоненты, которые идут вместе с этим модулем.
	 * @return bool
	 */
	public function unInstallFiles()
	{
		\Bitrix\Main\IO\Directory::deleteDirectory($_SERVER['DOCUMENT_ROOT'] . '/bitrix/services/starlabs.tools/');
		\Bitrix\Main\IO\Directory::deleteDirectory($_SERVER['DOCUMENT_ROOT'] . '/bitrix/components/starlabs/');

		return true;
	}

	/**
	 * @return bool
	 */
	public function installEvents()
	{
		$eventManager = EventManager::getInstance();
		foreach ($this->eventHandlers as $handler) {
			$eventManager->registerEventHandler($handler[0], $handler[1], $this->MODULE_ID, $handler[2], $handler[3]);
		}

		return true;
	}

	/**
	 * @return bool
	 */
	public function unInstallEvents()
	{
		$eventManager = EventManager::getInstance();
		foreach ($this->eventHandlers as $handler) {
			$eventManager->unRegisterEventHandler($handler[0], $handler[1], $this->MODULE_ID, $handler[2], $handler[3]);
		}

		return true;
	}

	/**
	 *
	 */
	public function DoInstall()
	{
		global $APPLICATION;
		$step = intval($this->Request->get('step'));

		if ($step < 2) {
			$APPLICATION->includeAdminFile(
				'Установка: Шаг 1',
				__DIR__ . '/step1.php'
			);
		} elseif ($step == 2) {
			if ($this->installEvents() && $this->installFiles()) {
				$this->AddUrlRewriterRule($this->Request->get('SITE_ID'));
				ModuleManager::registerModule($this->MODULE_ID);
				$APPLICATION->includeAdminFile(
					'Установка завершена',
					__DIR__ . '/step2.php'
				);
			}
		}
	}

	/**
	 *
	 */
	public function DoUninstall()
	{
		$this->unInstallEvents();
		$this->unInstallFiles();
		$this->DeleteUrlRewriterRule();
		ModuleManager::unRegisterModule($this->MODULE_ID);
	}


}
