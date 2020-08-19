<?php
namespace Starlabs\Tools\Helpers\Patterns;

trait Singleton
{
	private static $instances = [];

	protected function __construct()
	{
	}

	protected function __clone()
	{
	}

	/**
	 * @throws \Exception
	 */
	public function __wakeup()
	{
		throw new \Exception("Cannot unserialize a singleton.");
	}

	public static function getInstance(): self
	{
		$cls = static::class;

		$instances = self::$instances[$cls];

		if (!isset($instances)) {
			$instances = new static;
			self::$instances[$cls] = $instances;
		}

		$instances->execute();
		return $instances;
	}

	abstract public function execute();
}