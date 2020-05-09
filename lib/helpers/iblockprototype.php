<?php
namespace Starlabs\Tools\Helpers;

use Bitrix\Iblock\ElementTable;
use Bitrix\Iblock\IblockTable;
use Bitrix\Iblock\SectionTable;
use Bitrix\Main\Loader;
use Bitrix\Main\ORM\Entity;
use Bitrix\Main\ORM\Query\Query;
use Bitrix\Main\ORM\Query\Result;
use Exception;
use Starlabs\Tools\Helpers\Patterns\Singleton;

Loader::includeModule('iblock');

/**
 * Желательно задавать API_CODE в настройках инфоблока, уменьшит кол-во запросов к БД
 */
class IBlockPrototype
{
	use Singleton;

	private static $IBLOCKS = [];
	private $IBlockType = null;
	private $IBlockCode = null;
	private $IBlockApi = null;
	private $IBlockID = 0;

	/**
	 * @return mixed
	 * @return void
	 * @throws Exception
	 */
	public function execute()
	{
		self::setIBlockConstants();
		$this->IBlockIsExists();
		return;
	}

	public function getID()
	{
		return $this->IBlockID;
	}

	public function getCODE()
	{
		return $this->IBlockCode;
	}

	/**
	 * @param array $arParams Массив ключей: Любой set'ер объекта Bitrix\Main\ORM\Query\Query без префикса 'set'
	 * @param string $returnMethod любой метод в объекте Bitrix\Main\ORM\Query\Result
	 * @return Result
	 * @throws Exception
	 */
	public function getElements($arParams = [], $returnMethod = '')
	{
		$iblockapi = $this->IBlockApi;

		if (is_null($iblockapi)) {
			$iblockapi = $this->IBlockID;
		}

		$Entity = IblockTable::compileEntity($iblockapi);

		return $this->getData($Entity, $arParams, $returnMethod);
	}

	/**
	 * @param array $arParams Массив ключей: Любой set'ер объекта Bitrix\Main\ORM\Query\Query без префикса 'set'
	 * @param string $returnMethod любой метод в объекте Bitrix\Main\ORM\Query\Result
	 * @return Result
	 * @throws Exception
	 */
	public function getSections($arParams = [], $returnMethod = '')
	{
		$Entity = SectionTable::getEntity();
		return $this->getData($Entity, $arParams, $returnMethod);
	}

	/**
	 * @param Entity $Entity
	 * @param array $arParams
	 * @param string $returnMethod
	 * @return Result
	 * @throws Exception
	 */
	protected function getData(Entity $Entity, $arParams = [], $returnMethod = '')
	{
		$Query = new Query($Entity);

		foreach ($arParams as $method => $value) {
			if (method_exists($Query, 'set' . $method)) {
				call_user_func([$Query, 'set' . $method], $value);
			}
		}

		$Result = $Query->exec();

		if (!empty($returnMethod)) {
			if (method_exists($Result, $returnMethod)) {
				return $Result->{$returnMethod}();
			} else {
				throw new Exception(sprintf('Метод "%s" объекта Bitrix\Main\ORM\Query\Result не существует', $returnMethod));
			}
		}

		return $Result;
	}

	/**
	 * Формирует статический массив данных существующих на данные момент Типов ИБ и ИБ.
	 *
	 * @return void
	 * @throws Exception
	 */
	private static function setIBlockConstants()
	{
		if (!empty(self::$IBLOCKS)) {
			return;
		}

		$Query = new Query(IblockTable::getEntity());
		$Query->setSelect(['CODE', 'IBLOCK_TYPE_ID', 'ID', 'API_CODE']);
		$Query->setFilter(['ACTIVE' => 'Y']);
//		$Query->setCacheTtl( 43200 );                   //Кэш на месяц

		foreach ($Query->fetchAll() as $key => $iblock) {
			$iblock['IBLOCK_TYPE_ID'] = strtoupper($iblock['IBLOCK_TYPE_ID']);
			$iblock['CODE'] = strtoupper(trim($iblock['CODE']));

			if (
				!empty($iblock['CODE']) &&
				!empty($iblock['IBLOCK_TYPE_ID'])
			) {
				self::$IBLOCKS[$iblock['IBLOCK_TYPE_ID']][$iblock['CODE']] = [
					'ID' => (int)$iblock['ID'],
					'API_CODE' => $iblock['API_CODE']
				];
			}
		}

	}

	/**
	 * По Namespace проверяем, что есть такой инфоблок с таким типов.
	 *  Пример:
	 *  Namespace: Starlabs\Tools\IBlock\Module\Bbc
	 *  Значит есть тип инфоблока Module с инфоблоком Bbc
	 * @return bool
	 * @throws Exception
	 */
	protected function IBlockIsExists()
	{
		$IBlockType = null;
		$IBlockCode = null;
		$IBlockId = 0;

		$namespace = get_called_class();
		$arNamespacePath = explode('\\', $namespace);
		$IBlock = strtolower($arNamespacePath[2]);

		if ($IBlock === 'iblock') {

			$IBlockType = $arNamespacePath[3];
			$IBlockCode = $arNamespacePath[4];
			$IBlockId = self::$IBLOCKS[strtoupper($IBlockType)][strtoupper($IBlockCode)]['ID'];
			$IBlockApi = self::$IBLOCKS[strtoupper($IBlockType)][strtoupper($IBlockCode)]['API_CODE'];

			if ($IBlockId > 0 && !is_null($IBlockId)) {
				$this->IBlockCode = $IBlockCode;
				$this->IBlockType = $IBlockType;
				$this->IBlockID = $IBlockId;
				$this->IBlockApi = $IBlockApi;
				return true;
			}
		}

		throw new Exception('Информационный блок: "' . $IBlockCode . '" с типом: "' . $IBlockType . '"  не был найден');
	}
}