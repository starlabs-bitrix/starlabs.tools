<?php
namespace Starlabs\Tools\Ajax;

use Bitrix\Main\Application;

class Router
{
	protected $Request = null;
	protected $controller = null;
	protected $action = null;

	/**
	 * @throws \Exception
	 */
	public function __construct()
	{
		$this->Request = Application::getInstance()->getContext()->getRequest();

		if ($this->Request->isAjaxRequest() === false && $this->Request->getQuery("ishook") !== 'y') {
			throw new \Exception('Только ajax запросы пройдут');
		}

		$this->controller = htmlspecialchars($this->Request->getQuery("controller"));
		$this->action = htmlspecialchars($this->Request->getQuery("action"));
	}

	/**
	 * @throws \Exception
	 */
	public function Action()
	{
		$Controller = Controller\Prototype::factory($this->controller);
		$Controller->doAction($this->action);
	}
}
