<?php
namespace Starlabs\Tools\Helpers;

use Bitrix\Iblock\IblockTable;
use Bitrix\Iblock\SectionTable;
use Bitrix\Main\Loader;
use Bitrix\Main\ORM\Entity;
use Bitrix\Main\ORM\Query\Query;
use Starlabs\Tools\Helpers\Patterns\Singleton;

Loader::includeModule('iblock');

class IBlockPrototype
{
	use Singleton;

	protected static $IBLOCKS = [];
	protected $type = null;
	protected $code = null;
	protected $api = null;
	protected $id = 0;

	/**
	 * @return void
	 * @throws \Exception
	 */
	public function execute()
	{
		self::setIBlockConstants();
		$this->IBlockIsExists();
	}

	public function getID()
	{
		return $this->id;
	}

	public function getCODE()
	{
		return $this->code;
	}

	/**
	 * @return \Bitrix\Iblock\ORM\ElementEntity|false
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
	 */
	public function getElementEntity()
	{
		return IblockTable::compileEntity($this->api);
	}

	/**
	 * @return Query
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
	 */
	public function getElementQuery()
	{
		return new Query($this->getElementEntity());
	}

	/**
	 * @return Entity
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\SystemException
	 */
	public function getSectionEntity()
	{
		return SectionTable::getEntity();
	}

	/**
	 * @return Query
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\SystemException
	 */
	public function getSectionQuery()
	{
		return new Query($this->getSectionEntity());
	}

	/**
	 * Формирует статический массив данных существующих на данные момент Типов ИБ и ИБ.
	 *
	 * @return void
	 * @throws \Exception
	 */
	private static function setIBlockConstants()
	{
		if (!empty(self::$IBLOCKS)) {
			return;
		}

		$Query = new Query(IblockTable::getEntity());
		$Query->setSelect(['CODE', 'IBLOCK_TYPE_ID', 'ID', 'API_CODE', 'VERSION']);
		$Query->setFilter(['ACTIVE' => 'Y']);

		foreach ($Query->fetchAll() as $key => $iblock) {
			$iblock['IBLOCK_TYPE_ID'] = strtoupper($iblock['IBLOCK_TYPE_ID']);
			$iblock['CODE'] = strtoupper(trim($iblock['CODE']));

			if (
				!empty($iblock['CODE']) &&
				!empty($iblock['IBLOCK_TYPE_ID'])
			) {
				self::$IBLOCKS[$iblock['IBLOCK_TYPE_ID']][$iblock['CODE']] = [
					'ID' => (int)$iblock['ID'],
					'API_CODE' => $iblock['API_CODE'],
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
	 * @throws \Exception
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

			if (empty($IBlockApi)) {
				throw new \Exception('Не задан Символьный код API информационного блока');
			}

			if ($IBlockId > 0) {
				$this->code = $IBlockCode;
				$this->type = $IBlockType;
				$this->id = $IBlockId;
				$this->api = $IBlockApi;
				return true;
			}
		}

		throw new \Exception('Информационный блок: "' . $IBlockCode . '" с типом: "' . $IBlockType . '"  не был найден');
	}
}