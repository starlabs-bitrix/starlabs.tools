<?php
/**
 * @link http://bbc.bitrix.expert
 * @copyright Copyright © 2014-2015 Nik Samokhvalov
 * @license MIT
 */

use Bitrix\Main\Loader;
use Starlabs\Tools\Bbc\Basis;
use Starlabs\Tools\Bbc\Traits\Elements;

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
	die();
}

if (!Loader::includeModule('starlabs.tools')) {
	return false;
}

/**
 * Show page with element of the info-block
 *
 * @author Nik Samokhvalov <nik@samokhvalov.info>
 */
class ElementsDetail extends Basis
{
	use Elements;

	protected $needModules = ['iblock'];

	protected $checkParams = [
		'IBLOCK_TYPE' => ['type' => 'string'],
		'IBLOCK_ID' => ['type' => 'int'],
		'ELEMENT_ID' => ['type' => 'int', 'error' => false],
		'ELEMENT_CODE' => ['type' => 'string', 'error' => false]
	];

	protected function executeProlog()
	{
		if (!$this->arParams['ELEMENT_ID'] && !$this->arParams['ELEMENT_CODE']) {
			$this->return404(true);
		}
	}

	protected function executeMain()
	{
		$rsElement = CIBlockElement::GetList(
			[],
			$this->getParamsFilters(),
			false,
			false,
			$this->getParamsSelected()
		);

		$processingMethod = $this->getProcessingMethod();

		if ($element = $rsElement->$processingMethod()) {
			if ($arElement = $this->processingElementsResult($element)) {
				$this->arResult = array_merge($this->arResult, $arElement);
			}

			$this->setResultCacheKeys([
				'ID',
				'IBLOCK_ID',
				'CODE',
				'NAME',
				'IBLOCK_SECTION_ID',
				'IBLOCK',
				'LIST_PAGE_URL',
				'SECTION_URL',
				'SECTION'
			]);
		} elseif ($this->arParams['SET_404'] === 'Y') {
			$this->return404();
		}
	}
}