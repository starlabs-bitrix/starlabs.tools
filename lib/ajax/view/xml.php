<?php
namespace Starlabs\Tools\Ajax\View;

/**
 * @todo Протестировать работу
 * @todo Перед установкой модуля, проверить расширение DOMDocument
 *
 */
class Xml extends Prototype
{
	/**
	 * Название для элемента индексированного массива
	 *
	 * @var string
	 */
	protected $indexedArrayElement = 'item';

	/**
	 * Создает новый MVC XML view
	 *
	 * @param mixed $data Данные view
	 */
	public function __construct($data = [])
	{
		$this->data = $data;
	}

	/**
	 * Отсылает http-заголовки для view
	 *
	 * @return void
	 */
	public function sendHeaders()
	{
		header('Content-type: application/xml; charset=' . SITE_CHARSET);
	}

	/**
	 * Формирует view
	 * @return string
	 * @throws \Exception
	 */
	public function render()
	{
		if (!class_exists('\DOMDocument')) {
			throw new \Exception('libxml extension is not installed.');
		}

		$doc = new \DOMDocument('1.0', SITE_CHARSET);
		$root = $doc->createElement('response');
		$doc->appendChild($root);

		$this->buildNode($doc, $root, $this->data);

		return $doc->saveXML();
	}

	/**
	 * Формирует узел дерева
	 *
	 * @param DOMDocument $doc Документ
	 * @param DOMElement $parent Родительский узел
	 * @param mixed $data Данные
	 * @return void
	 */
	protected function buildNode($doc, $parent, $data)
	{
		if (is_array($data) || is_object($data)) {
			foreach ($data as $key => $val) {
				$isIndexed = is_int($key);
				if ($isIndexed) {
					$elementName = $this->indexedArrayElement;
				} else {
					$elementName = $key;
					if (ctype_digit(substr($elementName, 0, 1))) {
						$elementName = $this->indexedArrayElement . $elementName;
					}
				}

				$element = $doc->createElement($elementName);
				if ($isIndexed) {
					$element->setAttribute('index', $key);
				}
				$parent->appendChild($element);

				$this->buildNode($doc, $element, $val);
			}
		} else {
			$type = gettype($data);
			switch ($type) {
				case 'boolean':
					$data = $data ? 'true' : 'false';
					break;
			}

			$parent->setAttribute('type', $type);
			$parent->appendChild(
				$doc->createTextNode($data)
			);
		}
	}
}
