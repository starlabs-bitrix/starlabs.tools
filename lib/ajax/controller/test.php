<?php

namespace Starlabs\Tools\Ajax\Controller;

use Starlabs\Tools\Helpers\p;

class Test extends Prototype
{
	public function testAction()
	{
		$this->returnAsIs = true;
		return ['<b>testAction</b>'];
	}
}