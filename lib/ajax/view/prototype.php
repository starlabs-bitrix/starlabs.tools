<?php
namespace Starlabs\Tools\Ajax\View;

use const StarLabs\Tools\BASE_DIR;

/**
 * @todo Конструктор проверить, разные параметры
 * Абстрактный view
 */
class Prototype
{
	/**
	 * Каталог по умолчанию для файлов view
	 *
	 * @var string
	 */
	protected $baseDir = '';

	/**
	 * Имя view
	 *
	 * @var string
	 */
	protected $name = '';

	/**
	 * Данные view
	 *
	 * @var mixed
	 */
	protected $data = [];

	/**
	 * Создает новый MVC view
	 *
	 * @param string $name Название шаблона view
	 * @param mixed $data Данные view
	 */
	public function __construct($name = '', $data = [])
	{
		if (!$this->baseDir) {
			$this->baseDir = BASE_DIR . '/views/';
		}
		$this->name = $name;
		$this->data = $data;
	}

	/**
	 * Отсылает http-заголовки для view
	 *
	 * @return void
	 */
	public function sendHeaders()
	{
	}

	/**
	 * Формирует view
	 * @return string
	 * @throws \Exception
	 */
	public function render()
	{
		throw new \Exception("Abstract view can't be rendered.");
	}

	/**
	 * Устанавливает данные
	 *
	 * @param mixed $data Данные
	 * @return void
	 */
	public function setData($data)
	{
		$this->data = $data;
	}

	/**
	 * Устанавливает базовый каталог
	 *
	 * @param string $dir Базовый каталог
	 * @return void
	 */
	public function setBaseDir($dir)
	{
		$this->baseDir = $dir;
	}
}
