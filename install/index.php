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

	private $eventHandlers = [];

	public function __construct()
	{
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

		$this->AddUrlRewriterRule();

		return true;
	}

	/**
	 * Создаем правило для обработки ajax запросов
	 */
	public function AddUrlRewriterRule()
	{

		Bitrix\Main\UrlRewriter::add('s3', [
			'CONDITION' => '#^/ajax/#',
			'RULE' => '',
			'ID' => null,
			'PATH' => '/bitrix/services/starlabs.tools/ajax.php',
			'SORT' => 110,
		]);
	}

	/**
	 * Удаление правила для обработки ajax запросов
	 */
	public function DeleteUrlRewriterRule()
	{
		Bitrix\Main\UrlRewriter::delete('s3', [
			'CONDITION' => '#^/ajax/#'
		]);
	}

	/**
	 * @return bool
	 */
	public function unInstallFiles()
	{
		\Bitrix\Main\IO\Directory::deleteDirectory($_SERVER['DOCUMENT_ROOT'] . '/bitrix/services/starlabs.tools/');
		\Bitrix\Main\IO\Directory::deleteDirectory($_SERVER['DOCUMENT_ROOT'] . '/bitrix/components/starlabs/');
		$this->DeleteUrlRewriterRule();
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
		if ($this->installEvents() && $this->installFiles()) {
			ModuleManager::registerModule($this->MODULE_ID);
		}
	}

	/**
	 *
	 */
	public function DoUninstall()
	{
		if ($this->unInstallEvents() && $this->unInstallFiles()) {
			ModuleManager::unRegisterModule($this->MODULE_ID);
		}
	}


}
