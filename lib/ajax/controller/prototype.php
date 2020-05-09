<?php
namespace Starlabs\Tools\Ajax\Controller;

use Bitrix\Main\Context\HttpRequest;
use Bitrix\Main\SystemException;
use Exception;
use Starlabs\Tools\Ajax\View\Html;
use Bitrix\Main\Application;
use stdClass;

/**
 * Прототип AJAX контроллера
 */
class Prototype
{
	/** @var HttpRequest * */
	protected $request = null;

	/** @var \Starlabs\Tools\Ajax\View\Prototype * */
	protected $view = null;

	protected $returnAsIs = false;
	protected $params = [];

	/**
	 * @throws SystemException
	 */
	public function __construct()
	{
		$this->request = Application::getInstance()->getContext()->getRequest();
	}

	/**
	 * @param string $name
	 * @return Prototype
	 * @throws Exception
	 */
	public static function factory($name)
	{
		$name = preg_replace('/[^A-z0-9_]/', '', $name);
		$className = '\\' . __NAMESPACE__ . '\\' . ucfirst($name);

		if (!class_exists($className)) {
			$className = str_replace('Tools', 'Project', $className);
		}

		if (!class_exists($className)) {
			throw new Exception(sprintf('Контроллер "%s" не найден.', $name));
		}

		return new $className();
	}

	/**
	 * @param string $name
	 * @return void
	 * @throws Exception
	 */
	public function doAction($name)
	{
		$name = preg_replace('/[^A-z0-9_]/', '', $name);
		$methodName = $name . 'Action';

		if (!method_exists($this, $methodName)) {
			throw new Exception(sprintf('Action "%s" doesn\'t exists.', $name));
		}

		$this->view = new Html();

		$response = new stdClass();
		$response->success = false;
		try {
			$response->data = call_user_func([$this, $methodName]);
			$response->success = true;
		} catch (Exception $e) {
			$response->code = $e->getCode();
			$response->message = $e->getMessage();
		}

		try {
			$this->view->setData($this->returnAsIs ? (
			isset($response->data) ? $response->data : null
			) : $response);
			$this->view->sendHeaders();
			print $this->view->render();
		} catch (Exception $e) {
			print $e->getMessage();
		}
	}

	/**
	 * @param string $name
	 * @param string $template
	 * @param array $params
	 * @param mixed $componentResult
	 * @return string
	 */
	protected function getComponent($name, $template = '', $params = [], &$componentResult = null)
	{
		ob_start();
		$componentResult = $GLOBALS['APPLICATION']->IncludeComponent($name, $template, $params);
		$result = ob_get_contents();
		ob_end_clean();

		return $result;
	}

	/**
	 * @param string $name
	 * @param mixed $default
	 * @return mixed
	 */
	protected function getParam($name, $default = '')
	{
		$result = array_key_exists($name, $this->params)
			? $this->params[$name]
			: $this->request->get($name);

		return $result === null ? $default : $result;
	}
}
