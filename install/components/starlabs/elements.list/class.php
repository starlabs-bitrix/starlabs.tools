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
 * Component for show elements list
 *
 * @author Nik Samokhvalov <nik@samokhvalov.info>
 */
class ElementsList extends Basis
{
	use Elements;

	protected $needModules = ['iblock'];

	protected $checkParams = [
		'IBLOCK_TYPE' => ['type' => 'string'],
		'IBLOCK_ID' => ['type' => 'int']
	];

	protected function executeMain()
	{
		$rsElements = CIBlockElement::GetList(
			$this->getParamsSort(),
			$this->getParamsFilters(),
			$this->getParamsGrouping(),
			$this->getParamsNavStart(),
			$this->getParamsSelected([
				'DETAIL_PAGE_URL',
				'LIST_PAGE_URL'
			])
		);

		if (!isset($this->arResult['ELEMENTS'])) {
			$this->arResult['ELEMENTS'] = [];
		}

		$processingMethod = $this->getProcessingMethod();

		while ($element = $rsElements->$processingMethod()) {
			if ($arElement = $this->processingElementsResult($element)) {
				$this->arResult['ELEMENTS'][] = $arElement;
			}
		}

		if ($this->arParams['SET_404'] === 'Y' && empty($this->arResult['ELEMENTS'])) {
			$this->return404();
		}

		$this->generateNav($rsElements);
		$this->setResultCacheKeys(['NAV_CACHED_DATA']);
	}
}