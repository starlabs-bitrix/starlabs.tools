<?php
namespace Starlabs\Tools\Hlblock;

use Bitrix\Highloadblock\HighloadBlockTable;
use Bitrix\Main\Entity;
use Bitrix\Main\Entity\AddResult;
use Bitrix\Main\Entity\UpdateResult;
use Bitrix\Main\Loader;
use CUserFieldEnum;
use CUserTypeEntity;
use Exception;
use Starlabs\Tools\Cache;

if (!Loader::includeModule('highloadblock')) {
    throw new Exception("highloadblock module is't installed.");
}

/**
 * Прототип справочника
 */
class Prototype extends Entity\DataManager
{
    /**
     * Префикс констант для хранения идентификаторов справочников, соответсвующих кодам
     */
    const ID_CONSTANTS_PREFIX = 'HL_';

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
     * Конструктор
     *
     * @param integer $id ID справочников
     *
     * @throws Exception
     */
    protected function __construct($id = 0)
    {
        if ($id) {
            $this->id = $id;
        }
        if (!$this->id) {
            throw new Exception('highloadblock id is undefined.');
        }
    }

    /**
     * Возвращает справочник
     * Singleton + Factory
     *
     *
     * @param null | string $code Код справочника
     * @return mixed | Prototype
     * @throws Exception
     */
    public static function getInstance($code = null)
    {
        if (is_null($code)) {
            $code = str_replace(
                '\\',
                '_',
                substr(
                    get_called_class(),
                    strripos(get_called_class(), 'Hlblock\\') + 8
                )
            );

            if (stripos($code, '_') === 0) {
                $code = substr($code, 1);
            }
        }

        if (!$code) {
            throw new Exception('highloadblock code is undefined.');
        }

        $id = self::getIdByCode($code);
        $className = get_called_class();
        if (!class_exists($className)) {
            $className = '\\' . __CLASS__;
        }
        self::$instances[$id] = new $className($id);
        return self::$instances[$id];
    }

    /**
     * Возвращает ID справочника по его символьному коду
     *
     * @param string $code Символьный код справочника
     * @return int
     * @throws Exception
     */
    public static function getIdByCode($code)
    {
        if (!$code) {
            throw new Exception('highloadblock code is undefined.');
        }

        self::defineConstants();

        $const = self::ID_CONSTANTS_PREFIX . $code;
        if (!defined($const)) {
            throw new Exception(sprintf("Constant for highloadblock code '%s' is undefined.", $code));
        }

        return constant($const);
    }

    /**
     * Определяет константы вида HL_{CODE} для всех справочников
     *
     * @param integer $cacheTime Время кэширования
     * @return void
     */
    public static function defineConstants($cacheTime = 3600)
    {
        if (self::$constantsDefined) {
            return;
        }
        //@todo Сбро кеша не рабоатет на
//        $cache = new Cache(__METHOD__, __CLASS__, $cacheTime);
//        if ($cache->start()) {
        $arHlblocks = HighloadBlockTable::getList(
            [
                "select" => [
                    "ID",
                    "NAME"
                ]
            ]
        );
        while ($arHlblock = $arHlblocks->fetch()) {
            $data[] = [
                "ID" => $arHlblock["ID"],
                "CODE" => $arHlblock["NAME"]
            ];
        }
//            $cache->end($data);
//        } else {
//            $data = $cache->getVars();
//        }

        foreach ($data as $arHlblock) {
            $arHlblock['CODE'] = trim($arHlblock['CODE']);
            if ($arHlblock['CODE']) {
                $const = self::ID_CONSTANTS_PREFIX . $arHlblock['CODE'];
                if (!defined($const)) {
                    define($const, $arHlblock['ID']);
                }
            }
        }

        self::$constantsDefined = true;
    }


    /**
     * Возвращает массив с данными из справочника
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

//        $cache = $this->getCache([__METHOD__, $params], $cacheTime, $cacheTag);
//        if ($cache->start()) {
        $hlblock = HighloadBlockTable::getById($this->id)->fetch();
        $entity = HighloadBlockTable::compileEntity($hlblock);
        $entity_data_class = $entity->getDataClass();
        $rsData = $entity_data_class::getList($params);
        $arResult = [];
        $index = 0;
        while ($arRes = $rsData->fetch()) {
            if ($indexArray) {
                $arRes['INDEX'] = $index;
                $arResult[$arRes[$indexArray]] = $arRes;
            } else {
                $arResult[] = $arRes;
            }
            $index++;
        }

//            if (!empty($arResult)) {
//                $cache->end($arResult);
//            } else {
//                $cache->abort();
//            }
//        } else {
//            $arResult = $cache->getVars();
//        }
        return $arResult;
    }


    /**
     * Обновляет элемент hlblock инфоблока
     *
     * @param integer $id - id элемента
     * @param array $arFields - массив с данными для обновления
     *
     * @return UpdateResult
     */
    public function updateData($id, $arFields)
    {
        $entityDataClass = $this->getClassHlblock();
        $res = $entityDataClass::update($id, $arFields);

        return $res;
    }

    /**
     * Добавляет элемент hlblock инфоблока
     *
     * @param array $arFields - массив с с данными для обновления
     *
     * @return AddResult
     */
    public function addData($arFields)
    {
        $entityDataClass = $this->getClassHlblock();
        $res = $entityDataClass::add($arFields);

        return $res;
    }

    /**
     * Удаляет элемент hlblock инфоблока
     *
     * @param integer $id - id элемента
     *
     * @return bool | array
     */
    public function deleteData($id)
    {
        $entityDataClass = $this->getClassHlblock();
        $res = $entityDataClass::delete($id);
        if (!$res->isSuccess()) { //произошла ошибка
            return $res->getErrorMessages();
        }
        return false;
    }

    /**
     * Получения экземпляра класса для работы с элементами highload инфоблоков
     * @return Entity\DataManager $entity_data_class
     *
     */
    private function getClassHlblock()
    {
        $hlblock = HighloadBlockTable::getById($this->id)->fetch();
        $entity = HighloadBlockTable::compileEntity($hlblock);
        $entity_data_class = $entity->getDataClass();
        return $entity_data_class;
    }

    static public function getPropertyEnum($field)
    {
        $rsValues = CUserFieldEnum::GetList([], [
            'USER_FIELD_NAME' => $field
        ]);
        $arValues = [];
        foreach ($rsValues->arResult as $value) {
            $arValues[$value['XML_ID']] = $value;
        }

        return $arValues;
    }

    /**
     * Возвращает идентификатор справочника
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Возвращает модель кэша для справочника
     *
     * @param mixed $cacheId Идентификатор кэша
     * @param mixed $cacheTime Время жизни кэша
     * @param mixed $triggerTag Тег кэша
     *
     * @return Cache
     */
    protected function getCache($cacheId, $cacheTime = 3600, $triggerTag)
    {
        $cache = new Cache(
            [
                $this->id,
                $cacheId,
            ],
            $this->getCacheDir(),
            $cacheTime,
            $triggerTag
        );

        return $cache;
    }

    /**
     * Формирует имя каталога для хранения кэшей данного hlblock
     *
     * @return string
     */
    protected function getCacheDir()
    {
        return get_class($this);
    }

    /**
     * Возвращает варианты значений свойства типа "Список" в виде XML_ID => ID или ID => XML_ID
     *
     * @param array $arParams массив пармаметров с ключами
     *                        string CODE код свойства
     *                        string KEY поле свойства, которое будет ключем результирующего массива
     *                        string VALUE поле свойства, которое будет значения результирующего массива
     *                        array  FILTER дополнительный фильтр
     *
     * @return array
     * @throws Exception
     */

    public function getPropertyListValues($arParams)
    {
        if (!$arParams["CODE"]) {
            throw new Exception('CODE must be not empty!');
        }
        $arParams['KEY'] = $arParams['KEY'] ? $arParams['KEY'] : 'XML_ID';
        $arParams['VALUE'] = $arParams['VALUE'] ? $arParams['VALUE'] : 'ID';
        $cacheTime = $arParams['CACHE_TIME'] ? $arParams['CACHE_TIME'] : 3600;
        unset($arParams['CACHE_TIME']);
        $cache = $this->getCache([__METHOD__, $arParams], $cacheTime);
        if ($cache->start()) {
            $rsUserField = CUserTypeEntity::GetList([],
                ['FIELD_NAME' => $arParams["CODE"], 'ENTITY_ID' => 'HLBLOCK_' . $this->getId()]);
            $arUserField = $rsUserField->Fetch();
            if (empty($arUserField)) {
                $cache->abort();
                return [];
            }
            $arFilter = ["USER_FIELD_ID" => $arUserField["ID"]];
            if ($arParams['FILTER']) {
                $arFilter = array_merge($arFilter, $arParams['FILTER']);
            }
            $rsProps = CUserFieldEnum::GetList([], $arFilter);
            $propData = [];
            while ($arProp = $rsProps->GetNext()) {
                $propData[$arProp[$arParams['KEY']]] = $arProp[$arParams['VALUE']];
            }
            $cache->end($propData);
        } else {
            $propData = $cache->getVars();
        }
        return $propData;
    }
}
