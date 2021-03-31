<?php


namespace Acme\Model;

use Acme\AcmeInterface\IRequestManager;
use Acme\DataBase\Pdo\PdoManager;
use Acme\Lib\Method\Str;
use Acme\Lib\Method\Time;
use Acme\Lib\Request\RequestManager;
use Acme\Lib\Util\Console;
use Exception;
use PDO;
use Swoole\Database\PDOProxy;
use Swoole\Database\PDOStatementProxy;

/**
 * Class BaseModel 基础模型
 * @package Acme\Model
 * @author Billion
 * @time 2021-01-05 08:45:26
 */
class BaseModel {

    protected string $tableName = ''; // 留给子类
    protected string $fieldId = 'id'; // 字段id

    private PDOProxy $readPdo; // 读PDO
    private array $tableStruct = []; // 表结构
    private array $tableFields = []; // 表字段
    private SqlBuilder $sqlBuilder; // sql编译器
    private int $fd; // socket 对应的文件描述符 ID
    private IRequestManager $requestManager; // 请求管理器接口

    /**
     * BaseModel constructor.
     * Warnning 异步调用时Fd请勿使用控制器的!
     * @param IRequestManager $requestManager
     */
    public function __construct(IRequestManager $requestManager){
        $this->requestManager = $requestManager;
        $this->fd = $requestManager->fd();
        // 初始化读PDO
        $this->readPdo = PdoManager::getPdo();
        // 初始化sql编译器
        $this->sqlBuilder = new SqlBuilder($this->tableName);
        // 初始化表字段
        $this->tableStruct();
        // 给sql编译器表字段
        $this->sqlBuilder->setTableFields($this->tableFields);
    }

    /**
     * 字段聚合函数 any_value - 配合group by使用
     * @param string $field
     * @param string $fieldAlias
     * @return string
     */
    public static function fieldAnyValue(string $field,string $fieldAlias) : string{
        return "any_value($field) AS $fieldAlias";
    }

    /**
     * 字段聚合函数 count
     * @param string $field
     * @param string $fieldAlias
     * @return string
     */
    public static function fieldCount(string $field,string $fieldAlias) : string{
        return "count($field) AS $fieldAlias";
    }

    /**
     * 设置字段 - 字符串形式
     * @param string $fields
     * @return $this
     */
    public function fields(string $fields){
        $this->sqlBuilder->fields($fields);
        return $this;
    }

    /**
     * 设置字段 - 数组形式
     * @param array $fields
     * @return $this
     */
    public function fieldsArray(array $fields){
        $this->sqlBuilder->fieldsArray($fields);
        return $this;
    }

    /**
     * 快捷条件 - 查找指定的id
     * @param int $id
     * @return $this
     */
    public function whereId(int $id){
        $this->where($this->fieldId,$id);
        return $this;
    }

    /**
     * 条件 IN
     * @param string $field
     * @param array $values
     * @param string $union
     * @return BaseModel
     */
    public function whereIn(string $field,array $values,string $union=SqlBuilder::UNION_AND){
        $this->sqlBuilder->whereIn($field,$values,$union);
        return $this;
    }

    /**
     * 条件 BETWEEN
     * @param string $field
     * @param float|int $start
     * @param float|int $end
     * @param string $union
     * @return BaseModel
     */
    public function whereBetween(string $field,int|float $start,int|float $end,string $union=SqlBuilder::UNION_AND){
        $this->sqlBuilder->whereBetween($field,$start,$end,$union);
        return $this;
    }

    /**
     * 设置条件 - 字段(同字段多条件，请使用whereArray语法)
     * @param string $field
     * @param float|int|string $value
     * @param string $symbol
     * @return $this
     */
    public function where(string $field,string|int|float $value,string $symbol='='){
        $this->sqlBuilder->where($field,$value,$symbol,SqlBuilder::UNION_AND);
        return $this;
    }

    /**
     * 设置条件 - 数组
     * @param array $where 语法示例
     * 1. ['id' => 1]
     * 2. ['id' => ['>',13,'and','<',20]]
     * @param string $symbol
     * @return BaseModel
     */
    public function whereArray(array $where,string $symbol='='){
        $this->sqlBuilder->whereArray($where,$symbol,SqlBuilder::UNION_AND);
        return $this;
    }

    /**
     * 设置条件 或 - 字段(不能用于同一字段！)
     * 如需要同一字段or 请使用whereArray
     * @param string $field
     * @param float|int|string $value
     * @param string $symbol
     * @return $this
     */
    public function whereOr(string $field,string|int|float $value,string $symbol='='){
        $this->sqlBuilder->where($field,$value,$symbol,SqlBuilder::UNION_OR);
        return $this;
    }

    /**
     * 设置条件 - 数组
     * @param array $where
     * @param string $symbol
     * @return BaseModel
     */
    public function whereOrArray(array $where,string $symbol='='){
        $this->sqlBuilder->whereArray($where,$symbol,SqlBuilder::UNION_OR);
        return $this;
    }

    /**
     * 限制条数
     * @param int $limit
     * @return BaseModel
     */
    public function limit(int $limit){
        $this->sqlBuilder->limit($limit);
        return $this;
    }

    /**
     * 限制条数跨过多少条
     * @param int $skip
     * @param int $limit
     * @return BaseModel
     */
    public function limitSkip(int $skip,int $limit){
        $this->sqlBuilder->limitSkip($skip,$limit);
        return $this;
    }

    /**
     * 设置groupBy
     * @param string $field
     * @return BaseModel
     */
    public function groupBy(string $field){
        $this->sqlBuilder->groupBy($field);
        return $this;
    }

    /**
     * 聚合having
     * @param string $having
     * @return $this
     */
    public function having(string $having){
        $this->sqlBuilder->having($having);
        return $this;
    }

    /**
     * 设置排序
     * @param string $field
     * @param string $order
     * @return BaseModel
     */
    public function orderBy(string $field,string $order=SqlBuilder::ORDER_ASC){
        $this->sqlBuilder->orderBy($field,$order);
        return $this;
    }

    /**
     * 表别名
     * @param string $alias
     * @return BaseModel
     */
    public function alias(string $alias){
        $this->sqlBuilder->alias($alias);
        return $this;
    }

    /**
     * LEFT JOIN
     * @param string $joinTable
     * @param string $joinAlias
     * @param string $onWhere
     * @return BaseModel
     */
    public function leftJoin(string $joinTable,string $joinAlias,string $onWhere){
        $this->sqlBuilder->leftJoin($joinTable,$joinAlias,$onWhere);
        return $this;
    }

    /**
     * INNER JOIN
     * @param string $joinTable
     * @param string $joinAlias
     * @param string $onWhere
     * @return BaseModel
     */
    public function innerJoin(string $joinTable,string $joinAlias,string $onWhere){
        $this->sqlBuilder->innerJoin($joinTable,$joinAlias,$onWhere);
        return $this;
    }

    /**
     * RIGHT JOIN
     * @param string $joinTable
     * @param string $joinAlias
     * @param string $onWhere
     * @return BaseModel
     */
    public function rightJoin(string $joinTable,string $joinAlias,string $onWhere){
        $this->sqlBuilder->rightJoin($joinTable,$joinAlias,$onWhere);
        return $this;
    }

    /**
     * FULL OUTER JOIN
     * @param string $joinTable
     * @param string $joinAlias
     * @param string $onWhere
     * @return BaseModel
     */
    public function fullOuterJoin(string $joinTable,string $joinAlias,string $onWhere){
        $this->sqlBuilder->fullOuterJoin($joinTable,$joinAlias,$onWhere);
        return $this;
    }

    /**
     * 插入一条数据
     * @param array $data
     * @return ModelData
     * @throws Exception
     */
    public function insertOne(array $data) : ModelData{
        $sql = $this->sqlBuilder->insertOne($data);
        // 执行SQL语句
        return $this->runSql(ModelData::DATA_TYPE_INSERT_ONE,$sql,$this->sqlBuilder->prepareValue());
    }

    /**
     * 批量插入数据
     * @param string $fields 字段 : name,age
     * @param array $dataList 数据列表 [["李狗蛋",18,2000.00],["王胜",22,1800.00]]
     * @return ModelData
     * @throws Exception
     */
    public function insertMultiple(string $fields,array $dataList) : ModelData{
        $sql = $this->sqlBuilder->insertMultiple($fields,$dataList);
        // 执行SQL语句
        return $this->runSql(ModelData::DATA_TYPE_INSERT_MULTIPLE,$sql,$this->sqlBuilder->prepareValue());
    }

    /**
     * 单字段自增 - 如需要多字段请直接使用update方法
     * @param string $field
     * @param int $step
     * @return ModelData
     * @throws Exception
     */
    public function inc(string $field,int $step=1):ModelData{
        return $this->update([$field => SqlBuilder::updateInc($step)]);
    }

    /**
     * 单字段自减 - 如需要多字段请直接使用update方法
     * @param string $field
     * @param int $step
     * @return ModelData
     * @throws Exception
     */
    public function dec(string $field,int $step=1):ModelData{
        return $this->update([$field => SqlBuilder::updateDec($step)]);
    }


    /**
     * 更新数据
     * @param array $updateData
     * @return ModelData
     * @throws Exception
     */
    public function update(array $updateData): ModelData{
        $sql = $this->sqlBuilder->update($updateData);
//        var_dump([
//            'sql' => $sql,
//            'prepareValue' => $this->sqlBuilder->prepareValue(),
//        ]);
        // 执行SQL语句
        return $this->runSql(ModelData::DATA_TYPE_UPDATE,$sql,$this->sqlBuilder->prepareValue());
    }

    /**
     * 删除数据
     * @throws Exception
     */
    public function delete():ModelData{
        $sql = $this->sqlBuilder->delete();
//        var_dump([
//            'sql' => $sql,
//            'prepareValue' => $this->sqlBuilder->prepareValue(),
//        ]);
        // 执行SQL语句
        return $this->runSql(ModelData::DATA_TYPE_DELETE,$sql,$this->sqlBuilder->prepareValue());
    }


    /**
     * 设置更新或删除数据时无条件 - 您必须明确知道您的代码逻辑，以免数据被误删或更改！
     * @return $this
     */
    public function setNoWhereAndIKownWhatIDo(){
        $this->sqlBuilder->setNoWhereAndIKownWhatIDo();
        return $this;
    }

    // 删除数据


    /**
     * 设置选择唯一
     * @return $this
     */
    public function distinct(){
        $this->sqlBuilder->distinct();
        return $this;
    }

    /**
     * 查询单条数据
     * @return ModelData
     * @throws Exception
     */
    public function findOne():ModelData{
        $sql = $this->sqlBuilder->findOne();
        // 执行SQL语句
        return $this->runSql(ModelData::DATA_TYPE_QUERY,$sql,$this->sqlBuilder->prepareValue())->findOne();
    }

    // joinSelect
//        public function joinSelect(){
//            $sql = $this->sqlBuilder->select();
//            var_dump([
//                'sql' => $sql,
//                'prepareValue' => $this->sqlBuilder->prepareValue(),
//            ]);
//        }

    /**
     * 查询数据集
     * @return ModelData
     * @throws Exception
     */
    public function select() : ModelData{
        $sql = $this->sqlBuilder->select();
//        var_dump([
//            'sql' => $sql,
//            'prepareValue' => $this->sqlBuilder->prepareValue(),
//        ]);
        // 执行SQL语句
        return $this->runSql(ModelData::DATA_TYPE_QUERY,$sql,$this->sqlBuilder->prepareValue());
    }

    /**
     * 聚合函数 : 查询条数
     * @param string $field
     * @return ModelData
     * @throws Exception
     */
    public function count(string $field = '*'):ModelData{
        $sql = $this->sqlBuilder->count($field);
        // 执行SQL语句
        return $this->runSql(ModelData::DATA_TPPE_COUNT,$sql,$this->sqlBuilder->prepareValue());
    }

    /**
     * 聚合函数 : 返回数值列的平均值
     * @param string $field
     * @return ModelData
     * @throws Exception
     */
    public function avg(string $field) : ModelData{
        $sql = $this->sqlBuilder->avg($field);
        // 执行SQL语句
        return $this->runSql(ModelData::DATA_TPPE_AVG,$sql,$this->sqlBuilder->prepareValue());
    }

    /**
     * 聚合函数 : 返回指定列的最大值
     * @param string $field
     * @return ModelData
     * @throws Exception
     */
    public function max(string $field) : ModelData{
        $sql = $this->sqlBuilder->max($field);
        // 执行SQL语句
        return $this->runSql(ModelData::DATA_TPPE_MAX,$sql,$this->sqlBuilder->prepareValue());
    }

    /**
     * 聚合函数 : 返回指定列的最小值
     * @param string $field
     * @return ModelData
     * @throws Exception
     */
    public function min(string $field) : ModelData{
        $sql = $this->sqlBuilder->min($field);
        // 执行SQL语句
        return $this->runSql(ModelData::DATA_TPPE_MIN,$sql,$this->sqlBuilder->prepareValue());
    }

    /**
     * 聚合函数 : 返回数值列的总数
     * @param string $field
     * @return ModelData
     * @throws Exception
     */
    public function sum(string $field) : ModelData{
        $sql = $this->sqlBuilder->sum($field);
        // 执行SQL语句
        return $this->runSql(ModelData::DATA_TPPE_SUM,$sql,$this->sqlBuilder->prepareValue());
    }


    // 开启一个事务
    public function startTrans(){
        $writePdo = $this->getWritePdo();
        $writePdo->beginTransaction();
        $this->putWritePdo($writePdo);
    }
    // 提交一个事务
    public function commit(){
        // 检测会否在一个事务中
        $writePdo = $this->getWritePdo();
        if($writePdo->inTransaction()){
            $writePdo->commit();
        }else{
            Console::appPrint('无需回滚！ - 没有在事务中!',Console::COLOR_YELLOW);
        }
        $this->putWritePdo($writePdo);
    }
    // 回滚一个事务
    public function rollback(){
        // 检测会否在一个事务中
        $writePdo = $this->getWritePdo();
        if($writePdo->inTransaction()){
            $writePdo->rollback();
        }else{
            Console::appPrint('无需回滚！ - 没有在事务中!',Console::COLOR_YELLOW);
        }
        $this->putWritePdo($writePdo);
    }

    // 获取写pdo
    private function getWritePdo() : PDOProxy{
        return PdoManager::getPdoWithFd($this->fd)->pop();
    }
    // 放回写pdo
    private function putWritePdo(PDOProxy $writePdo){
        PdoManager::putPdoWithFd($this->fd,$writePdo);
    }

    /**
     * 执行SQL语句
     * @param int $dataType
     * @param string $sql
     * @param array $prepareValue
     * @return ModelData
     * @throws Exception
     */
    private function runSql(int $dataType,string $sql,array $prepareValue=[]) : ModelData{
        // 编译运行SQL
        $runSql = $sql;
        foreach ($prepareValue as $key => $value){
            if($this->sqlBuilder->prepareType() == SqlBuilder::PREPARE_TYPE_NUMBER){ // 数值数组
                $runSql = Str::stringReplaceOnce(SqlBuilder::PREPARE_SYMBOL,$value,$runSql);
            }else{ // 键值数组
                $runSql = str_replace($key,$value,$runSql);
            }
        }

        // 重置编译信息
        $this->sqlBuilder->reset();

        // 获取pdo - 判断是读还是写
        $isWritePdo = ModelData::isWrite($dataType);
        if($this->fd && $isWritePdo){ // 写操作
            $pdo = $this->getWritePdo();
        }else{ // 读操作
            $pdo = $this->readPdo;
        }

        // 预编译SQL
        $pdoStatement = $pdo->prepare($sql);

        // 执行SQL语句
        $startMicrotime = $this->recordSqlStart();
        // 默认变量
        $insertId = 0; // 插入id
        // 判断有没有预编译值
        try {
            // 执行SQL
            if($prepareValue){
                $result = $pdoStatement->execute($prepareValue);
            }else{
                $result = $pdoStatement->execute();
            }
            // 判断数据类型
            if($dataType == ModelData::DATA_TYPE_INSERT_ONE){
                // 获取最后的id
                $insertId = (Int)$pdo->lastInsertId();
            }
        }catch (Exception $exception){
            // 显示错误SQL
            Console::appPrint("runSql : $runSql");
            var_dump([
                'prepareSql' => $sql,
                'prepareValue' => $prepareValue,
            ]);

            // 抛出异常
            throw $exception;
        } finally {
            // 若果是写操作 - 把fdPdo放回去
            if($isWritePdo){
                $this->putWritePdo($pdo);
            }
        }


        // 记录sql
        $this->recordSql($runSql,$startMicrotime);

        // 判断是否执行成功
        if($result){ // 执行成功
            // 设置modelData
            $modelData = new ModelData(true,$dataType);
            // 判断数据类型
            switch ($dataType){
                case ModelData::DATA_TYPE_INSERT_ONE: // 插入一条数据
                    // 获取数据影响的行数
                    $rowCount = $pdoStatement->rowCount();
                    $modelData->setRowCount($rowCount);
                    // 设置插入的ID
                    $modelData->setInsertId($insertId);
                    break;
                case ModelData::DATA_TYPE_INSERT_MULTIPLE: // 批量插入数据
                    // 获取数据影响的行数
                    $rowCount = $pdoStatement->rowCount();
                    $modelData->setRowCount($rowCount);
                    break;
                case ModelData::DATA_TYPE_QUERY: // 查询数据
                    // 获取数据
                    $sourceData = $pdoStatement->fetchAll(PDO::FETCH_ASSOC);
                    $modelData->setSourceData($sourceData);
                    break;
                case ModelData::DATA_TYPE_UPDATE: // 更新数据
                    // 获取数据影响的行数
                    $rowCount = $pdoStatement->rowCount();
                    $modelData->setRowCount($rowCount);
                    break;
                case ModelData::DATA_TYPE_DELETE: // 删除数据
                    // 获取数据影响的行数
                    $rowCount = $pdoStatement->rowCount();
                    $modelData->setRowCount($rowCount);
                    break;
                case ModelData::DATA_TPPE_COUNT: // 聚合函数 - count
                    // 通用聚合函数处理
                    $this->currencyPolymerize($pdoStatement,$modelData,'count');
                    break;
                case ModelData::DATA_TPPE_AVG: // 聚合函数 - avg
                    // 通用聚合函数处理
                    $this->currencyPolymerize($pdoStatement,$modelData,'avg');
                    break;
                case ModelData::DATA_TPPE_MAX: // 聚合函数 - max
                    // 通用聚合函数处理
                    $this->currencyPolymerize($pdoStatement,$modelData,'max');
                    break;
                case ModelData::DATA_TPPE_MIN: // 聚合函数 - min
                    // 通用聚合函数处理
                    $this->currencyPolymerize($pdoStatement,$modelData,'min');
                    break;
                case ModelData::DATA_TPPE_SUM: // 聚合函数 - sum
                    // 通用聚合函数处理
                    $this->currencyPolymerize($pdoStatement,$modelData,'sum');
                    break;
                case ModelData::DATA_TYPE_SYSTEM: // 系统
                    // 获取数据
                    $sourceData = $pdoStatement->fetchAll(PDO::FETCH_ASSOC);
                    $modelData->setSourceData($sourceData);
                    break;
                default:
                    // Nothing...
                    break;
            }

        }else{
            $modelData = new ModelData(false,ModelData::DATA_TYPE_FAIL);
        }

        return $modelData;
    }

    // 通用聚合函数处理
    private function currencyPolymerize(PDOStatementProxy $pdoStatement,ModelData $modelData,string $field){
        // 获取数据
        $sourceData = $pdoStatement->fetch(PDO::FETCH_ASSOC);
        $modelData->setSourceData($sourceData);
        // 设置聚合数据
        $polymerizeField = $field.'Field';
        $setPolymerize = 'set'.ucfirst($field);
        $result = $sourceData[SqlBuilder::$polymerizeField()];
        $modelData->$setPolymerize($result);
    }


    // 查询表结构
    private function tableStruct(){
        $sql = $this->sqlBuilder->showColums();
        $modelData = $this->runSql(ModelData::DATA_TYPE_SYSTEM,$sql);
        // 获取SQL结果
        $data = $modelData->sourceData();
        // 设置表结构和表字段
        foreach ($data as $fieldsInfo){
            $field = $fieldsInfo['Field'];
            $type = $fieldsInfo['Type'];
            $allowNull = $fieldsInfo['Null'];
            $key = $fieldsInfo['Key'];
            $default = $fieldsInfo['Default'];
            $extra = $fieldsInfo['Extra'];
            // 设置表字段
            array_push($this->tableFields,$field);
            // 设置表结构
            $struct = [
                'field' => $field,
                'type' => $type,
                'allowNull' => $allowNull,
                'key' => $key,
                'default' => $default,
                'extra' => $extra,
            ];
            array_push($this->tableStruct,$struct);
        }
    }

    // 修改数据

    /**
     * 记录SQL执行开始时间
     * @return string 微妙 : 1609813507.54119500
     */
    private function recordSqlStart() : string{
        return Time::getMicrotime();
    }
    // 记录SQL执行
    private function recordSql(string $sql,string $startMicrotime){
        $runtime = bcsub(Time::getMicrotime(),$startMicrotime,8);
        // 记录SQL
        $record = "[fd:($this->fd) SQL - $runtime 秒] : $sql";
        Console::out($record);
        // 记录生命周期日志
        $this->requestManager->lifeLog()->record("Table ($this->tableName)SQL","(SQL耗时:$runtime 秒) : $sql");
    }

    // 释放pdo
    function __destruct(){
//        Console::out($this->tableName." 被析构!准备释放readPdo");
        // 把pdo手动放回连接池
        PdoManager::putPdo($this->readPdo);
    }


}