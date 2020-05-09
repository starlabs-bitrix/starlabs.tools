<?php
namespace Starlabs\Tools\Iblock;

use Bitrix\Iblock\IblockTable;
use Bitrix\Iblock\InheritedProperty\ElementTemplates;
use Bitrix\Iblock\PropertyIndex\Manager;
use Bitrix\Iblock\PropertyTable;
use Bitrix\Iblock\SectionTable;
use Bitrix\Main\ArgumentException;
use Bitrix\Main\Loader;
use Bitrix\Main\ObjectPropertyException;
use Bitrix\Main\SystemException;
use CIBlock;
use CIBlockElement;
use CIBlockSection;
use Exception;
use ReflectionClass;
use ReflectionException;
use Starlabs\Tools\Cache;

if (!Loader::includeModule('iblock')) {
    throw new Exception("Iblock module is't installed.");
}

/**
 * Прототип инфоблока
 *
 * Префикс констант для хранения кодов инфоблоков, соответсвующих идентификаторам
 */
class Prototype
{
    /**
     * Префикс констант для хранения идентификаторов справочников, соответсвующих кодам
     */
    const ID_CONSTANTS_PREFIX = 'IB_';

    /**
     * Singleton экземпляры
     *
     * @var array
     */
    protected static $instances = [];

    /**
     * Константы были определены
     *
     * @var boolean
     */
    protected static $constantsDefined = false;

    /**
     * ID инфоблока
     *
     * @var integer
     */
    protected $id = 0;

    /**
     * Экземпляр класса CIBlockElement
     *
     * @var CIBlockElement
     */
    public $el = null;

    /**
     * Экземпляр класса CIBlockSection
     *
     * @var CIBlockSection
     */
    public $bs = null;

    /**
     * Данные инфоблока
     *
     * @var array|null
     */
    protected $data = null;

    /**
     * Свойства инфоблока
     *
     * @var array|null
     */
    protected $properties = null;

    /**
     * Секции инфоблока
     *
     * @var array|null
     */
    protected $sections = null;

    /**
     * Обработчики изображений
     *
     * @var array
     */
    protected $imageHandlers = [];

    /**
     * Конструктор
     *
     * @param integer $id ID инфоблока
     * @throws Exception
     */
    protected function __construct($id = 0)
    {
        if ($id) {
            $this->id = $id;
        }

        $this->el = new CIBlockElement();
        $this->bs = new CIBlockSection();

        if (!$this->id) {
            throw new Exception('Iblock ID is undefined.');
        }
    }

    /**
     * Возвращает данные инфоблока
     *
     * @param integer $cacheTime Время кэширования
     * @return array
     */
    public function getData($cacheTime = 3600)
    {
        //@todo Если вызывать метод с кэшем, не работает. Разобраться с тегированием кэшем
        if ($this->data !== null) {
            return $this->data;
        }
//        $cache = $this->getCache([__METHOD__], $cacheTime);
//        if ($cache->start()) {
        $data = IblockTable::getList(
            [
                'order' => ['SORT' => 'ASC'],
                'filter' => ['ID' => $this->id]
            ]
        )->fetch();

//            if ($data) {
//
//                $cache->end($data);
//            } else {
//                $cache->abort();
//            }
//        } else {
//            $data = $cache->getVars();
//        }

        $this->data = $data;
        return $data;
    }

    /**
     * Возвращает инфоблок по namespace
     * Singleton + Factory
     *
     * @return mixed
     * @throws Exception
     */
    public static function getInstance()
    {
        /*
        * Символьный код желательно задавать в формате ТипИнфоблока_КодИнфоблока
        */
        $code = str_replace(
            '\\',
            '_',
            substr(
                get_called_class(),
                strripos(get_called_class(), 'Iblock\\') + 7
            )
        );
        if (stripos($code, '_') === 0) {
            $code = substr($code, 1);
        }
        return self::getInstanceByCode($code);
    }

    /**
     * Возвращает инфоблок по символьному коду
     * Singleton + Factory
     *
     * @param string $code символьный код инфоблока
     * @param bool $instanceSave Сохранять в обхект с сущностями
     * @return mixed
     * @throws Exception
     */
    public static function getInstanceByCode($code, $instanceSave = true)
    {
        if (!$code) {
            throw new Exception('Iblock code is undefined.');
        }
        $id = self::getIdByCode($code);
        if (array_key_exists($id, self::$instances)) {
            return self::$instances[$id];
        }

        $className = get_called_class();
        if (!class_exists($className)) {
            $className = '\\' . __CLASS__;
        }
        if ($instanceSave) {
            self::$instances[$id] = new $className($id);
            return self::$instances[$id];
        } else {
            return new $className($id);
        }
    }

    /**
     * Возвращает инфоблок по внешнему коду
     * Singleton + Factory
     *
     * @param string $xmlId внешний код инфоблока
     * @return mixed
     * @throws Exception
     */
    public static function getInstanceByXmlId($xmlId)
    {
        $rs = IblockTable::getList(
            [
                'select' => ['XML_ID', 'CODE'],
                'filter' => ['XML_ID' => $xmlId],
                'limit' => 1
            ]
        );

        $ar = $rs->fetch();
        if ($ar && $ar['XML_ID'] == $xmlId) {
            $code = $ar['CODE'];
            return self::getInstanceByCode($code, false);

        } else {
            throw new Exception('Iblock xml_id is undefined.');
        }
    }

    /**
     * Возвращает инфоблок по ID
     * Singleton + Factory
     *
     * @param integer $id Идентификатор инфоблока
     * @return \Remark\Tools\Iblock\Prototype
     * @throws Exception
     */
    public static function getInstanceById($id)
    {
        $rs = IblockTable::getList(
            [
                'select' => ['ID', 'CODE'],
                'filter' => ['ID' => $id],
                'limit' => 1
            ]
        );

        $ar = $rs->fetch();
        if ($ar && $ar['ID'] == $id) {
            $code = $ar['CODE'];
            return self::getInstanceByCode($code, false);

        } else {
            throw new Exception('Iblock id is undefined.');
        }
    }

    /**
     * Возвращает ID инфоблока по его символьному коду
     *
     * @param string $code Символьный код инфоблока
     * @return int
     * @throws Exception
     */
    public static function getIdByCode($code)
    {
        if (!$code) {
            throw new Exception('Iblock code is undefined.');
        }

        self::defineConstants();

        $const = self::ID_CONSTANTS_PREFIX . $code;
        if (!defined($const)) {
            throw new Exception(sprintf("Constant for infoblock code '%s' is undefined.", $code));
        }

        return constant($const);
    }

    /**
     * Возвращает название класса инфоблока по его символьному коду
     * @param $code Символьный код инфоблока
     * @return string
     * @throws ReflectionException
     */
    public static function getClassByCode($code)
    {
        $reflector = new ReflectionClass(get_called_class());
        $namespace = $reflector->getNamespaceName();
        $namespaceIblockExplode = explode('\\', $namespace);
        $namespaceIblockExplode = array_slice($namespaceIblockExplode, 0, 3);
        $namespaceIblock = implode('\\', $namespaceIblockExplode);
        return '\\' . $namespaceIblock . '\\' . str_replace(['-', '_', '\\'], '\\', $code);
    }

    /**
     * Возвращает идентификатор инфоблока
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     *
     *
     * Определяет константы вида IB_{CODE}
     *
     * @param integer $cacheTime Время кэширования
     * @return void
     */
    public static function defineConstants($cacheTime = 3600)
    {
        if (self::$constantsDefined) {
            return;
        }

        $cache = new Cache(__METHOD__, __CLASS__, $cacheTime);
        if ($cache->start()) {
            $iblocks = CIBlock::GetList(
                [
                    'sort' => 'asc',
                ],
                [
                    'ACTIVE' => 'Y',
                    'CHECK_PERMISSIONS' => 'N',
                ]
            );
            $data = [];
            while ($iblock = $iblocks->Fetch()) {
                $data[] = $iblock;
            }

            $cache->end($data);
        } else {
            $data = $cache->getVars();
        }
        foreach ($data as $iblock) {
            $iblock['CODE'] = trim($iblock['CODE']);
            if ($iblock['CODE']) {
                $const = self::ID_CONSTANTS_PREFIX . $iblock['CODE'];
                if (!defined($const)) {
                    define($const, $iblock['ID']);
                }
            }
        }
        self::$constantsDefined = true;
    }

    /**
     * Возвращает id раздела по символьному коду
     * @param $code символьный код
     * @param int $cacheTime время кеширования
     * @return mixed идентификатор раздела ИБ
     * @throws ArgumentException
     * @throws ObjectPropertyException
     * @throws SystemException
     */
    public function getSectionId($code, $cacheTime = 3600)
    {
        //@todo ошибка кажется в передаче параметров
        $cache = $this->getCache([__METHOD__, $code], $cacheTime);
        if ($cache->start()) {

            $rs = SectionTable::getList([
                'filter' => ['CODE' => $code, 'IBLOCK_ID' => $this->getId()],
                'select' => ['ID']
            ]);
            $ar = $rs->fetch();
            $id = $ar['ID'];
            if ($id) {
                $cache->end($id);
            } else {
                $cache->abort();
            }
        } else {
            $id = $cache->getVars();
        }
        return $id;
    }

    /**
     * Возвращает массив с разделами
     *
     * @param array $params массив с ключами, аналогичными ключам Bitrix\Main\Entity\DataManager::getList() и
     *      дополнительными ключами
     *          "cacheTime" => integer время кэширования
     *          "indexArray" => ключ который будет взят за основу результирующего массива
     *          "cacheTag" => тег кэширования
     *
     * @return array|mixed
     */
    public function getSections($params = [])
    {
        if (key_exists('cacheTime', $params)) {
            $cacheTime = $params['cacheTime'];
            unset($params['cacheTime']);
        } else {
            $cacheTime = 3600;
        }

        if (key_exists('cacheTag', $params)) {
            $cacheTag = $params['cacheTag'];
            unset($params['cacheTag']);
        } else {
            $cacheTag = '';
        }

        if (key_exists('indexArray', $params)) {
            $indexArray = $params['indexArray'];
            unset($params['indexArray']);
        } else {
            $indexArray = false;
        }

        $order = $params['order'] ? $params['order'] : [];
        $arFilter = $params['filter'] ? $params['filter'] : [];
        $arSelect = $params['select'] ? $params['select'] : ['*'];
        $cnt = $params['cnt'] ? $params['cnt'] : false;

        if (!$arFilter['IBLOCK_ID']) {
            $arFilter['IBLOCK_ID'] = $this->id;
        }

        $cache = $this->getCache([__METHOD__, $params], $cacheTime, $cacheTag);
        if ($cache->start()) {

            $rs = CIBlockSection::GetList($order, $arFilter, $cnt, $arSelect);
            $index = 0;
            $arResult = [];
            while ($ar = $rs->GetNext()) {
                if ($indexArray) {
                    $ar['INDEX'] = $index;
                    $arResult[$ar[$indexArray]] = $ar;
                } else {
                    $arResult[] = $ar;
                }
                $index++;
            }

            if (!empty($arResult)) {
                $cache->end($arResult);
            } else {
                $cache->abort();
            }
        } else {
            $arResult = $cache->getVars();
        }
        return $arResult;
    }

    /**
     * Получение id разделов, вложенных в запрашиваемый
     * @param int $sectionId Идентификатор раздела
     * @param int $cacheTime Время кеширования
     * @return array|mixed
     * @throws ArgumentException
     * @throws ObjectPropertyException
     * @throws SystemException
     */
    public function getInnerSectionsId($sectionId, $cacheTime = 3600)
    {
        $arInnerSectionsId = [];
        $cache = $this->getCache([__METHOD__, $sectionId], $cacheTime);
        if ($cache->start()) {
            $rs = SectionTable::getList(
                [
                    'filter' => [
                        '=IBLOCK_ID' => $this->getId(),
                        '=ID' => $sectionId
                    ],
                    'select' => [
                        'ID',
                        'LEFT_MARGIN',
                        'RIGHT_MARGIN'
                    ]
                ]
            );
            $ar = $rs->fetch();
            if (!empty($ar)) {
                $rs = SectionTable::getList(
                    [
                        'filter' => [
                            '=IBLOCK_ID' => $this->getId(),
                            '>LEFT_MARGIN' => $ar['LEFT_MARGIN'],
                            '<RIGHT_MARGIN' => $ar['RIGHT_MARGIN']
                        ],
                        'select' => [
                            'ID'
                        ]
                    ]
                );
                while ($ar = $rs->fetch()) {
                    $arInnerSectionsId[] = $ar['ID'];
                }
            }
            if (!empty($arInnerSectionsId)) {
                $cache->end($arInnerSectionsId);
            } else {
                $cache->abort();
            }
        } else {
            $arInnerSectionsId = $cache->getVars();
        }
        return $arInnerSectionsId;
    }

    /**
     * Возвращает модель кэша для инфоблока
     *
     * @param mixed $cacheId Идентификатор кэша
     * @param mixed $cacheTime Время жизни кэша
     * @param mixed $cacheTag Код тега для тегированного кэша
     * @return Cache
     */
    protected function getCache($cacheId, $cacheTime = 3600, $cacheTag)
    {
        $cache = new Cache(
            [
                $this->id,
                $cacheId
            ],
            $this->getCacheDir(),
            $cacheTime,
            $cacheTag
        );
        return $cache;
    }

    /**
     * Формирует имя каталога для хранения кэшей данного инфоблока
     *
     * @return string
     */
    protected function getCacheDir()
    {
        return get_class($this);
    }

    /**
     * Добавление / Обновление элемента инфоблока по внешнему коду
     *
     * @param array $arFields Поля элемента
     * @param array $arProps Массив свойств
     * @param array $arSeo Массив параметров для SEO
     * @param array $arSections Массив данных по разделам
     * @param string $entityName Название сущности ( для логов )
     * @return int | CIBlockElement $elementID id элемента
     * @throws Exception
     */
    public function addUpdateElement($arFields, $arProps = [], $arSeo = [], $arSections = [], $entityName = '')
    {
        if (!$arFields["IBLOCK_ID"]) {
            $arFields["IBLOCK_ID"] = $this->getId();
        }

        if (!is_null($arFields["XML_ID"])) {
            $existedElID = $this->isExistElementXmlID($arFields["XML_ID"]);
        } else {
            $existedElID = $this->isExistElement($arFields);
        }

        $elementID = $existedElID;
        $el = $this->el;
        if ($existedElID) { // если есть, обновляем элемент
            $res = $el->Update($existedElID, $arFields, false, false, true);
            if (!$res) { // если fail пишем ошибку в лог
                throw new Exception($entityName . " import update: " . $el->LAST_ERROR, 'iblock_errors.txt'); // обновляем поля
            } else { // все ок, обновляем свойства
                CIBlockElement::SetPropertyValuesEx($existedElID, false, $arProps);
            }
        } else {  // если нет, добавляем
            $res = $el->Add($arFields, false, false, true);
            if (!$res) {
                throw new Exception($entityName . " import add: " . $el->LAST_ERROR, 'iblock_errors.txt'); // добавляем поля
            } else { // все ок, добавляем свойства
                if (!empty($arProps)) {
                    CIBlockElement::SetPropertyValuesEx($res, false, $arProps);
                }
                $elementID = $res;
            }
        }

        if (intval($elementID)) { // установим SEO-поля
            $seoTemplates = new ElementTemplates($this->getId(), $elementID);
            $seoTemplates->set($arSeo);
        }

        if (!empty($arSections) && intval($elementID)) { // привяжем элемент к разделам
            $arSectionsId = [];
            foreach ($arSections as $xmlId => $arSection) {
                $arSectionsId[] = $this->addUpdateSection(
                    [
                        'XML_ID' => $xmlId,
                        'NAME' => $arSection['NAME'],
                        'CODE' => $arSection['CODE']
                    ],
                    'NEWS_SECTIONS'
                );
            }

            CIBlockElement::SetElementSection($elementID, $arSectionsId);
            Manager::updateElementIndex($this->getId(), $elementID);
        }

        return $elementID;
    }

    /**
     * Проверяет есть ли элемент с переданным внешним кодом в БД
     *
     * @param string $xmlID
     * @return mixed false/elementID
     */
    public function isExistElementXmlID($xmlID)
    {
        if (intval($xmlID) > 0) {
            $arSelect = ["ID"];
            $arFilter = ["XML_ID" => $xmlID, "IBLOCK_ID" => $this->getId()];
            $rsElements = CIBlockElement::GetList([], $arFilter, false, false, $arSelect);
            if ($arElement = $rsElements->GetNext()) {
                return $arElement["ID"];
            }
        }
        return false;
    }

    /**
     * Проверяет есть ли элемент с переданным набором параметров
     *
     * @param array $ar
     * @return mixed false/elementID
     */
    public function isExistElement($ar)
    {
        if (!empty($ar)) {
            $arSelect = ["ID"];
            $arFilter = ["IBLOCK_ID" => $this->getId()];
            $arFilter = array_merge($arFilter, $ar);

            $rsElements = CIBlockElement::GetList([], $arFilter, false, false, $arSelect);
            if ($arElement = $rsElements->GetNext()) {
                return $arElement["ID"]; // такой элемент уже есть, вернем его id
            }
        }
        return false;
    }

    /**
     * Проверяет есть ли раздел с переданным внишним кодом в БД
     *
     * @param string $xmlID
     * @return mixed false/sectionID
     */
    public function isExistSectionXmlID($xmlID)
    {
        $arSelect = ["ID"];
        $arFilter = ["XML_ID" => $xmlID, "IBLOCK_ID" => $this->getId()];
        $rsSections = CIBlockSection::GetList([], $arFilter, false, false, $arSelect);

        if ($rsSection = $rsSections->GetNext()) {
            return $rsSection["ID"];
        }

        return false;
    }

    /**
     * Добавление / Обновление раздела инфоблока по внешнему коду
     *
     * @param array $arFields Поля элемента
     * @param string $entityName Название сущности ( для логов )
     * @return int $sectionID id элемента
     * @throws Exception
     */
    protected function addUpdateSection($arFields, $entityName)
    {
        $bs = $this->bs;
        if (!$arFields["IBLOCK_ID"]) {
            $arFields["IBLOCK_ID"] = $this->id;
        }
        $existedSecID = $this->isExistSectionXmlID($arFields["XML_ID"]);
        $sectionID = $existedSecID;
        if ($sectionID) {
            $res = $bs->Update($sectionID, $arFields);
            if (!$res) {
                throw new Exception($entityName . " import update: " . $bs->LAST_ERROR);
            }
        } else {
            $res = $bs->Add($arFields);
            if (!$res) {
                throw new Exception($entityName . " import add: " . $bs->LAST_ERROR);
            }
            $sectionID = $res;
        }
        return $sectionID;
    }

    /**
     * Возвращает массив с элементами
     *
     * @param array $params массив с ключами, аналогичными ключам Bitrix\Main\Entity\DataManager::getList() и
     *      дополнительными ключами
     *          "cacheTime" => integer время кэширования
     *          "indexArray" => ключ который будет взят за основу результирующего массива
     *          "cacheTag" => тег кэширования
     *
     * @return array|mixed
     */
    public function getElements($params = [])
    {
        if (key_exists('cacheTime', $params)) {
            $cacheTime = $params['cacheTime'];
            unset($params['cacheTime']);
        } else {
            $cacheTime = 0;
        }

        if (key_exists('cacheTag', $params)) {
            $cacheTag = $params['cacheTag'];
            unset($params['cacheTag']);
        } else {
            $cacheTag = '';
        }

        if (key_exists('indexArray', $params)) {
            $indexArray = $params['indexArray'];
            unset($params['indexArray']);
        } else {
            $indexArray = false;
        }

        $order = $params['order'] ? $params['order'] : [];
        $arFilter = $params['filter'] ? $params['filter'] : [];
        $arSelect = $params['select'] ? $params['select'] : ['*'];
        $arGroup = $params['group'] ? $params['group'] : false;
        $arNav = $params['nav'] ? $params['nav'] : false;

        if (!$arFilter['IBLOCK_ID']) {
            $arFilter['IBLOCK_ID'] = $this->id;
        }

        $cache = $this->getCache([__METHOD__, $params], $cacheTime, $cacheTag);
        if ($cache->start()) {
            $rs = CIBlockElement::GetList($order, $arFilter, $arGroup, $arNav, $arSelect);
            $index = 0;
            $arResult = [];
            while ($ar = $rs->GetNext()) {
                if ($indexArray) {
                    $ar['INDEX'] = $index;
                    $arResult[$ar[$indexArray]] = $ar;
                } else {
                    $arResult[] = $ar;
                }
                $index++;
            }

            if (!empty($arResult)) {
                $cache->end($arResult);
            } else {
                $cache->abort();
            }
        } else {
            $arResult = $cache->getVars();
        }
        return $arResult;
    }

    /**
     * Получение id привязанных элементов по свойству
     *
     * @param int $id ид элемента
     * @param string $propCode код элемента
     * @param array $addFilter дополнительный фильтр
     * @param int $cacheTime
     * @return array|null
     */
    protected function getLinkedElementsId($id, $propCode, $addFilter = [], $cacheTime)
    {
        $arFilter = ['ID' => $id];
        if (!empty($addFilter) && is_array($addFilter)) {
            $arFilter = array_merge($addFilter, $addFilter);
        }
        $ar = $this->getElements([
            'filter' => $arFilter,
            'select' => ['ID', 'IBLOCK_ID', 'PROPERTY_' . $propCode],
            'cacheTime' => $cacheTime
        ]);
        $ar = reset($ar);
        $value = $ar['PROPERTY_' . $propCode . '_VALUE'];
        return $value;
    }

    /**
     * Возвращает массив с элементами
     * @param array $params массив с ключами, аналогичными ключам Bitrix\Main\Entity\DataManager::getList() и
     * дополнительными ключами "cacheTime" => integer время кэширования
     * @return array|mixed
     * @throws ArgumentException
     * @throws ObjectPropertyException
     * @throws SystemException
     */
    public function getProperties($params)
    {
        if (key_exists('cacheTime', $params)) {
            $cacheTime = $params['cacheTime'];
            unset($params['cacheTime']);
        } else {
            $cacheTime = 3600;
        }
        $cache = $this->getCache([__METHOD__, $params], $cacheTime);
        if ($cache->start()) {
            $rs = PropertyTable::getList($params);
            $arResult = [];
            while ($ar = $rs->fetch()) {
                $arResult[] = $ar;
            }

            if (!empty($arResult)) {
                $cache->end($arResult);
            } else {
                $cache->abort();
            }
        } else {
            $arResult = $cache->getVars();
        }
        return $arResult;
    }
}
