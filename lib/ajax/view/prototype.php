<?php
namespace Starlabs\Tools\Ajax\View;

use Exception;

/**
 * Абстрактный view
 */
abstract class Prototype
{
	protected $baseDir = '';
	protected $name = '';
	protected $data = [];

	/**
	 * @param string $name
	 * @param mixed $data
	 */
	public function __construct($name = '', $data = [])
	{
		if (!$this->baseDir) {
			$this->baseDir = __DIR__ . '/views/';
		}
		$this->name = $name;
		$this->data = $data;
	}

	/**
	 * @return void
	 */
	public function sendHeaders()
	{
	}

	/**
	 * @return string
	 * @throws Exception
	 */
	abstract public function render();

	/**
	 * @param mixed $data
	 * @return void
	 */
	final public function setData($data)
	{
		$this->data = $data;
	}

	/**
	 * @param string $dir
	 * @return void
	 */
	final public function setBaseDir($dir)
	{
		$this->baseDir = $dir;
	}
}
