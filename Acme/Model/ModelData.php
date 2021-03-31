<?php


namespace Acme\Model;

/**
 * Class ModelData 模型数据
 * @package Acme\Model
 * @author Billion
 * @time 2021-01-05 19:11:37
 */
class ModelData {

    const DATA_TYPE_FAIL = 0; // 数据类型(失败) : FAIL
    const DATA_TYPE_INSERT_ONE = 1; // 数据类型(插入一条数据) : INSERT_ONE
    const DATA_TYPE_INSERT_MULTIPLE = 2; // 数据类型(批量插入数据) : INSERT_MULTIPLE
    const DATA_TYPE_UPDATE = 3; // 数据类型 : UPDATE
    const DATA_TYPE_DELETE = 4; // 数据类型 : DELETE
    const DATA_TYPE_QUERY = 5; // 数据类型 : QUERY
    const DATA_TYPE_SYSTEM = 6; // 数据类型 : SYSTEM
    const DATA_TPPE_COUNT = 7; // 数据类型 : 聚合函数count
    const DATA_TPPE_AVG = 8; // 数据类型 : 聚合函数avg
    const DATA_TPPE_MAX = 9; // 数据类型 : 聚合函数max
    const DATA_TPPE_MIN = 10; // 数据类型 : 聚合函数min
    const DATA_TPPE_SUM = 11; // 数据类型 : 聚合函数sum

    private bool $success; // 是否成功
    private array $sourceData; // 原始数据
    private bool $findOne = false; // 返回一条数据
    private int $dataType; // 数据类型
    private int $rowCount = 0; // 影响的行数
    private int $insertId = 0; // 插入的id号 - 仅对DATA_TYPE_INSERT_ONE有效
    private int $count = 0; // 聚合函数 : count
    private float $avg = 0; // 聚合函数 : avg
    private float $max = 0; // 聚合函数 : max
    private float $min = 0; // 聚合函数 : min
    private float $sum = 0; // 聚合函数 : sum

    /**
     * ModelData constructor.
     * @param bool $success
     * @param int $dataType
     * @param array $sourceData
     */
    public function __construct(bool $success,int $dataType,array $sourceData=[]) {
        $this->success = $success;
        $this->dataType = $dataType;
        $this->sourceData = $sourceData;
    }

    // 判断操作是否为写
    public static function isWrite(int $dataType):bool{
        $writeType = [
            self::DATA_TYPE_INSERT_ONE,
            self::DATA_TYPE_INSERT_MULTIPLE,
            self::DATA_TYPE_UPDATE,
            self::DATA_TYPE_DELETE,
        ];
        return in_array($dataType,$writeType);
    }

    /**
     * 判断是否成功
     * @return bool
     */
    public function success():bool{
        return $this->success;
    }

    /**
     * 获取insertId
     * @return int
     */
    public function insertId() : int{
        return $this->insertId;
    }

    /**
     * 获取影响行数
     * @return int
     */
    public function rowCount():int{
        return $this->rowCount;
    }

    /**
     * 获取聚合函数count的值
     * @return int
     */
    public function count():int{
        return $this->count;
    }

    /**
     * 设置聚合函数 : count的值
     * @param int $count
     */
    public function setCount(int $count){
        $this->count = $count;
    }
    // 获取avg
    public function avg():float{
        return $this->avg;
    }
    // 设置avg
    public function setAvg(float $avg){
        $this->avg = $avg;
    }
    // 获取max
    public function max():float{
        return $this->max;
    }
    // 设置max
    public function setMax(float $max){
        $this->max = $max;
    }
    // 获取min
    public function min():float{
        return $this->min;
    }
    // 设置min
    public function setMin(float $min){
        $this->min = $min;
    }
    // 获取sum
    public function sum():float{
        return $this->sum;
    }
    // 设置sum
    public function setSum(float $sum){
        $this->sum = $sum;
    }




    /**
     * 设置插入id
     * @param int $insertId
     */
    public function setInsertId(int $insertId){
        $this->insertId = $insertId;
    }

    /**
     * 设置影响行数
     * @param int $rowCount
     */
    public function setRowCount(int $rowCount){
        $this->rowCount = $rowCount;
    }


    /**
     * 设置原始数据
     * @param array $sourceData
     */
    public function setSourceData(array $sourceData){
        $this->sourceData = $sourceData;
    }

    /**
     * 返回时设置为一条
     */
    public function findOne(){
        $this->findOne = true;
        return $this;
    }

    /**
     * 转换源数据为数组
     * @return array
     */
    public function toArray() : array{
        // 处理数据字段 - 如自动时间戳 TODO:
        $data = $this->sourceData();
        // 判断有没有数据
        if($data){
            // 判断是否返回一条
            if($this->findOne){
                return $data[0];
            }
            return $data;
        }
        return [];
    }


    /**
     * 获取源数据
     * @return array
     */
    public function sourceData() : array{
        return $this->sourceData;
    }


}