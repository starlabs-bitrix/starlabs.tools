<?php
namespace Starlabs\Tools\Helpers;

use Bitrix\Main\Context;
use Bitrix\Main\Type\Date;

class p
{
	private $die;
	private $css;
	private $data;
	private $color;
	private $toFile;
	private $varDump;
	private $onlyAdmin;
	private $forUser;
	private $onlyAjax;
	private $RestartBuffer;

	function __call($name, $arguments)
	{
		$separator = explode("_", $name);
		if ($separator[1]) {
			$name = "";
			foreach ($separator as $key => $val) {
				$name .= $val . (count($separator) > $key + 1 ? '-' : "");
			}
		}
		$this->css .= $name . ":" . $arguments[0] . ";";
		return $this;
	}

	static function init($val)
	{
		$p = new self();
		$p->onlyAdmin = false;
		$p->forUser = null;
		$p->data = $val;
		$p->die = false;
		$p->varDump = false;
		$p->toFile = false;
		$p->onlyAjax = false;

		return $p;
	}

	protected function setStyle($val)
	{
		return '<pre style="border:1px solid ' . (empty($this->color) ? "red" : $this->color) . ';' . $this->css . '">' . $val . "</pre>\n";
	}

	function forUser($id)
	{
		$this->forUser = $id;
		return $this;
	}

	function forAjax()
	{
		$this->onlyAjax = true;
		return $this;
	}

	function forAdmin()
	{
		$this->onlyAdmin = true;
		return $this;
	}

	function forAll()
	{
		$this->onlyAdmin = false;
		return $this;
	}

	function setColor($val)
	{
		$this->color = $val;
		return $this;
	}

	function _type()
	{
		$this->data = gettype($this->data);
		return $this;
	}

	function _die($restart = false)
	{
		$this->RestartBuffer = $restart;
		$this->die = true;
		return $this;
	}

	function _varDump()
	{
		$this->varDump = true;
		return $this;
	}

	function _toFile($fileName = '')
	{
		$this->toFile = true;

		if ($fileName == 'date') {
			define("LOG_FILENAME", $_SERVER["DOCUMENT_ROOT"] . "/.!__log_" . (new Date())->toString());
		} elseif (strlen($fileName) > 0) {
			define("LOG_FILENAME", $_SERVER["DOCUMENT_ROOT"] . "/" . $fileName);
		} else {
			define("LOG_FILENAME", $_SERVER["DOCUMENT_ROOT"] . "/.!__log");
		}

		return $this;
	}

	function getRequest()
	{
		$this->data = Context::getCurrent()->getRequest()->toArray();
		return $this;
	}

	function __destruct()
	{
		if (!$this->toFile) {
			global $USER, $APPLICATION;

			if (
				(($USER->IsAdmin() && $this->onlyAdmin) || !$this->onlyAdmin) &&
				(Context::getCurrent()->getRequest()->isAjaxRequest() === $this->onlyAjax) &&
				(
					!is_null($this->forUser) && $USER->GetID() == $this->forUser ||
					is_null($this->forUser)
				)
			) {
				$debug = (
				$this->varDump === true
					? var_dump($this->data)
					: $this->setStyle(print_r($this->data, true))
				);

				if ($this->RestartBuffer === true) {
					$APPLICATION->RestartBuffer();
				}

				echo $debug;
			}

		} else {
			AddMessage2Log($this->data, '', 1000);
		}

		if ($this->die === true && Context::getCurrent()->getRequest()->isAjaxRequest() === $this->onlyAjax) {
			die();
		}
	}
}
