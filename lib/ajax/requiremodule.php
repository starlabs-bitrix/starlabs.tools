<?php
namespace Starlabs\Tools\Ajax;

use Bitrix\Main\Loader;
use Starlabs\Tools\Helpers\Patterns\Singleton;

class RequireModule
{
	use Singleton;

	protected $moduleList = [];

	public function execute()
	{

	}

	/**
	 * @param $name string Название модуля
	 * @throws \Bitrix\Main\LoaderException
	 */
	public function add($name)
	{
		if (Loader::includeModule($name) && $this->exists($name) === false) {
			$this->moduleList[] = $name;
		}
	}

	/**
	 * @param $name string
	 * @return bool
	 */
	public function exists($name): bool
	{
		return in_array($name, $this->moduleList);
	}

	public function getLists()
	{
		return $this->moduleList;
	}
}