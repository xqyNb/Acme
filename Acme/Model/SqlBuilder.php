<?php


namespace Acme\Model;

use Acme\App;
use Acme\Lib\Method\Str;
use Acme\Lib\Util\Console;
use Exception;

/**
 * Class SqlBuilder - SQL编译器
 * @package Acme\Model
 * @author Billion
 * @time 2021-01-05 09:10:48
 */
class SqlBuilder {

    const UNION_AND = 'AND';
    const UNION_OR = 'OR';
    const ORDER_ASC = 'ASC';
    const ORDER_DESC = 'DESC';
    const WHERE_ORDER = 'WHERE_ORDER'; // ORDER : where条件
    const WHERE_ORDER_IN = 'IN'; // 条件 IN
    const WHERE_ORDER_BETWEEN = 'BETWEEN'; // 条件 BETWEEN

    const PREPARE_TYPE_ASSOC = 1; // 键值对参数
    const PREPARE_TYPE_NUMBER = 2; // 数值参数
    const PREPARE_SYMBOL = '?'; // 编译占位符号

    private string $tableName; // 表名
    private string $tableAlias = ''; // 表别名
    private array $tableFields = []; // 表字段
    private int $prepareType = self::PREPARE_TYPE_ASSOC; // 预编译类型

    private bool $checkWhereEmpty = true; // 更新或删除时硬性检查是否有条件
    private bool $distinct = false; // 选择唯一
    private array $fields = []; // 字段
    private array $where = []; // 条件
    private array $limit = []; // 数据限制 - [where,limit]
    private array $orderBy = []; // 排序
    private string $groupBy = ''; // Group By
    private array $join = []; // join
    private string $having = ''; // Having

    private array $prepareValue = []; // 预编译值 - 关联数组
    private array $prepareValueNumber = []; // 预编译值 - 数值数组


    /**
     * SqlBuilder constructor.
     * @param string $tableName
     */
    public function __construct(string $tableName) {
        $this->tableName = $tableName;
    }

    /**
     * 重置编译信息
     */
    public function reset() {
        $this->distinct = false;
        $this->fields = [];
        $this->where = [];
        $this->limit = [];
        $this->orderBy = [];
        $this->groupBy = '';
        $this->having = '';
        $this->join = [];
        $this->tableAlias = '';
        $this->prepareType = self::PREPARE_TYPE_ASSOC;
        $this->prepareValue = [];
        $this->prepareValueNumber = [];
    }

    /**
     * 设置选择唯一
     */
    public function distinct() {
        $this->distinct = true;
    }

    /**
     * 设置表字段
     * @param array $tableFields
     */
    public function setTableFields(array $tableFields) {
        $this->tableFields = $tableFields;
    }

    /**
     * 获取预编译类型
     * @return int
     */
    public function prepareType(): int {
        return $this->prepareType;
    }

    /**
     * 获取prepareValue
     * @return array
     */
    public function prepareValue(): array {
        // 判断类型
        if ($this->prepareType == self::PREPARE_TYPE_ASSOC) {
            return $this->prepareValue;
        }
        return $this->prepareValueNumber;
    }

    /**
     * 设置字段
     * @param string $fields
     */
    public function fields(string $fields) {
        $this->fields = array_unique(array_merge($this->fields, explode(',', $fields)));
    }

    /**
     * 设置字段 - 数组形式
     * @param array $fields
     */
    public function fieldsArray(array $fields) {
        $this->fields = array_unique(array_merge($this->fields, $fields));
    }

    /**
     * 条件 IN
     * @param string $field
     * @param array $values
     * @param string $union
     */
    public function whereIn(string $field, array $values, string $union = self::UNION_AND) {
        $prepareValues = [];
        foreach ($values as $index => $value) {
            // 预编译值 - 防止SQL注入
            $prepareValue = $this->buildPrepareValue($field, $index);
            // 设置编译值
            $this->setPrepareValue($prepareValue, $value);
            // 设置条件预编译列表
            array_push($prepareValues, $prepareValue);
        }
        // 追加到条件
        $this->where[$field] = [$union, [self::WHERE_ORDER, self::WHERE_ORDER_IN, $prepareValues]];
    }

    /**
     * 条件 BETWEEN
     * @param string $field
     * @param float|int $start
     * @param float|int $end
     * @param string $union
     */
    public function whereBetween(string $field, int|float $start, int|float $end, string $union = self::UNION_AND) {
        // 预编译值 - 防止SQL注入
        $prepareValue1 = $this->buildPrepareValue($field, 1);
        $prepareValue2 = $this->buildPrepareValue($field, 2);
        // 设置编译值
        $this->setPrepareValue($prepareValue1, $start);
        $this->setPrepareValue($prepareValue2, $end);
        // 追加到条件
        $this->where[$field] = [$union, [self::WHERE_ORDER, self::WHERE_ORDER_BETWEEN, [$prepareValue1, $prepareValue2]]];
    }

    /**
     * 设置条件 - 字段
     * @param string $field
     * @param float|int|string $value
     * @param string $symbol
     * @param string $union
     */
    public function where(string $field, string|int|float $value, string $symbol = '=', string $union = self::UNION_AND) {
        // 预编译值 - 防止SQL注入
        $prepareValue = $this->buildPrepareValue($field);
        // 设置编译值
        $this->setPrepareValue($prepareValue, $value);
        // 追加到条件
        $this->where[$field] = [$union, [$symbol, $prepareValue]];
    }

    /**
     * 设置条件 - 数组
     * @param array $where 语法示例
     * 1. ['id' => 1]
     * 2. ['id' => ['>',13,'and','<',20]]
     * @param string $symbol
     * @param string $union
     */
    public function whereArray(array $where, string $symbol = '=', string $union = self::UNION_AND) {
        // 追加条件
        foreach ($where as $field => $value) {

            // 判断value是否是数组
            if (is_array($value)) { // 是数组 - 直接追加
                // 预编译值 - 防止SQL注入
                $prepareValue1 = $this->buildPrepareValue($field, 1);
                $prepareValue2 = $this->buildPrepareValue($field, 2);
                // 设置编译值
                $this->setPrepareValue($prepareValue1, $value[1]);
                $this->setPrepareValue($prepareValue2, $value[4]);
                $value[1] = $prepareValue1;
                $value[4] = $prepareValue2;
                // 追加到条件
                $this->where[$field] = [$union, $value];

            } else { // 是值 - 赋值为 = : ['id' => 1]
                // 预编译值 - 防止SQL注入
                $prepareValue = $this->buildPrepareValue($field);
                // 设置编译值
                $this->setPrepareValue($prepareValue, $value);
                $this->where[$field] = [$union, [$symbol, $prepareValue]];
            }
        }
    }


    /**
     * 设置groupBy
     * @param string $field
     */
    public function groupBy(string $field) {
        $field = $this->prepareField($field);
        $this->groupBy = "GROUP BY $field";
    }

    /**
     * 设置排序
     * @param string $field
     * @param string $order
     */
    public function orderBy(string $field, string $order = self::ORDER_ASC) {
        $field = $this->prepareField($field);
        $this->orderBy[$field] = $order;
    }


    /**
     * 限制条数
     * @param int $limit
     */
    public function limit(int $limit) {
        $this->limit = ['skip' => 0, 'limit' => $limit];
    }

    /**
     * 限制条数跨过多少条
     * @param int $skip
     * @param int $limit
     */
    public function limitSkip(int $skip, int $limit) {
        $this->limit = ['skip' => $skip, 'limit' => $limit];
    }


    /**
     * 表别名
     * @param string $alias
     */
    public function alias(string $alias) {
        $this->tableAlias = $this->tableName . " AS $alias";
    }

    /**
     * LEFT JOIN
     * @param string $joinTable
     * @param string $joinAlias
     * @param string $onWhere
     */
    public function leftJoin(string $joinTable, string $joinAlias, string $onWhere) {
        $this->join('LEFT JOIN', $joinTable, $joinAlias, $onWhere);
    }

    /**
     * INNER JOIN
     * @param string $joinTable
     * @param string $joinAlias
     * @param string $onWhere
     */
    public function innerJoin(string $joinTable, string $joinAlias, string $onWhere) {
        $this->join('INNER JOIN', $joinTable, $joinAlias, $onWhere);
    }

    /**
     * RIGHT JOIN
     * @param string $joinTable
     * @param string $joinAlias
     * @param string $onWhere
     */
    public function rightJoin(string $joinTable, string $joinAlias, string $onWhere) {
        $this->join('RIGHT JOIN', $joinTable, $joinAlias, $onWhere);
    }

    /**
     * FULL OUTER JOIN
     * @param string $joinTable
     * @param string $joinAlias
     * @param string $onWhere
     */
    public function fullOuterJoin(string $joinTable, string $joinAlias, string $onWhere) {
        $this->join('FULL OUTER JOIN', $joinTable, $joinAlias, $onWhere);
    }


    /**
     * 聚合having
     * @param string $having
     * @return string
     */
    public function having(string $having): string{
        $this->having = $having;
    }

    /**
     * 获取count字段
     * @return string
     */
    public static function countField():string{
        return App::frameName().'_count';
    }
    // 获取avg字段
    public static function avgField():string{
        return App::frameName().'_avg';
    }
    // 获取max字段
    public static function maxField():string{
        return App::frameName().'_max';
    }
    // 获取min字段
    public static function minField():string{
        return App::frameName().'_min';
    }
    // 获取sum字段
    public static function sumField():string{
        return App::frameName().'_sum';
    }

    /**
     * 聚合函数 : 查询条数
     * @param string $field
     * @return string
     */
    public function count(string $field = '*') : string{
        return $this->currencyPolymerize('COUNT',self::countField(),$field);
    }

    /**
     * 聚合函数 : 返回数值列的平均值
     * @param string $field
     * @return string
     */
    public function avg(string $field) : string{
        return $this->currencyPolymerize('AVG',self::avgField(),$field);
    }

    /**
     * 聚合函数 : 返回指定列的最大值
     * @param string $field
     * @return string
     */
    public function max(string $field) : string{
        return $this->currencyPolymerize('MAX',self::maxField(),$field);
    }

    /**
     * 聚合函数 : 返回指定列的最小值
     * @param string $field
     * @return string
     */
    public function min(string $field) : string{
        return $this->currencyPolymerize('MIN',self::minField(),$field);
    }

    /**
     * 聚合函数 : 返回数值列的总数
     * @param string $field
     * @return string
     */
    public function sum(string $field) : string{
        return $this->currencyPolymerize('SUM',self::sumField(),$field);
    }

    /**
     * 选择数据集
     * @return string
     */
    public function select(): string {
        // 设置SQL - (Warnning : 编译顺序不能变!)
        $distinct = $this->buildDistinct();
        $fields = $this->buildFields();
        [
            'tableName' => $tableName,
            'join' => $join,
            'where' => $where,
            'orderBy' => $orderBy,
            'having' => $having,
            'limit' => $limit,
        ] = $this->getPolymerizeSql();
        $sql = "SELECT $distinct $fields FROM $tableName $join $where $this->groupBy $having $orderBy $limit";

        return Str::mergeSpaces($sql);
    }

    // 更新自增
    public static function updateInc(int $step=1):array{
        return ['+',$step];
    }

    // 更新自减
    public static function updateDec(int $step=1):array{
        return ['-',$step];
    }

    // 字段表达式
    public static function updateFieldExpression(string $fieldExpresiion):array{
        return ['',$fieldExpresiion];
    }

    /**
     * 更新数据
     * @param array $updateData
     * @return string
     * @throws Exception
     */
    public function update(array $updateData):string {
        // 检测是否有条件
        $this->checkWhereEmpty();
        // 编译更新数据
        $setSql = '';
        foreach ($updateData as $field => $value){
            $field = $this->prepareField($field);
            // 1. =值 2. 字段表达式 3. 自增、自减
            // 判断是否是数组
            if(is_array($value)){ // 2. 字段表达式 3. 自增、自减
                [$symbol,$expression] = $value;
                // 是不是字段引用
                if($symbol){ // 字段引用
                    $setValue = "=($field".$symbol.$expression.')';
                }else{
                    $setValue = "=(".$expression.')';
                }
            }else{ // 值
                $setValue = '='.$value;
            }
            // 判断前面有没有
            if($setSql){
                $setSql .= ','.$field.$setValue;
            }else{
                $setSql = $field.$setValue;
            }
        }

        // 设置SQL - (Warnning : 编译顺序不能变!)
        $tableName = $this->buildTableName();
        $where = $this->buildWhere();
        $sql = "UPDATE $tableName SET $setSql $where";

        return Str::mergeSpaces($sql);
    }

    /**
     * 删除数据
     * @return string
     * @throws Exception
     */
    public function delete():string{
        // 检测是否有条件
        $this->checkWhereEmpty();
        // 设置SQL - (Warnning : 编译顺序不能变!)
        $tableName = $this->buildTableName();
        $where = $this->buildWhere();
        $sql = "DELETE FROM $tableName $where";

        return Str::mergeSpaces($sql);
    }


    /**
     * 设置更新或删除数据时无条件 - 您必须明确知道您的代码逻辑，以免数据被误删或更改！
     */
    public function setNoWhereAndIKownWhatIDo(){
        $this->checkWhereEmpty = false;
    }


    /**
     * 插入一条数据
     * @param array $data
     * @return string
     */
    public function insertOne(array $data): string {
        [$fieldSql, $valueSql] = $this->buildInsertData($data);
        // 设置SQL
        $tableName = $this->buildTableName();
        $sql = "INSERT INTO $tableName ($fieldSql) VALUES ($valueSql)";
        return Str::mergeSpaces($sql);
    }

    /**
     * 批量插入数据
     * @param string $fields 字段 : name,age
     * @param array $dataList 数据列表 [["李狗蛋",18,2000.00],["王胜",22,1800.00]]
     * @return string
     */
    public function insertMultiple(string $fields, array $dataList): string {
        $fieldsList = explode(',', $fields);
        // 编译值列表
        $valueListSql = '';
        $dataListCount = count($dataList);
        // 循环数据列表
        foreach ($dataList as $index => $data) {
            $valueSql = '';
            // 循环数据
            foreach ($data as $fieldIndex => $value) {
                $prepareValue = $this->buildPrepareValue($fieldsList[$fieldIndex], $index);
                // 设置编译值
                $this->setPrepareValue($prepareValue, $value);
                // 设置SQL
                if ($valueSql) {
                    $valueSql .= ',' . $prepareValue;
                } else {
                    $valueSql = $prepareValue;
                }
            }
            // 设置括号
            if ($index + 1 == $dataListCount) { // 后面没有了
                $valueListSql .= "($valueSql)";
            } else { // 后面还有
                $valueListSql .= "($valueSql),";
            }
        }

        // 设置SQL
        $tableName = $this->buildTableName();
        $sql = "INSERT INTO $tableName ($fields) VALUES $valueListSql";
        return Str::mergeSpaces($sql);
    }


    /**
     * 选择单条数据
     * @return string
     */
    public function findOne(): string {
        $this->limit(1);
        return $this->select();
    }

    // 通用聚合
    private function currencyPolymerize(string $polymerizeField,string $polymerizeAlias,string $field) : string{
        // 设置SQL - (Warnning : 编译顺序不能变!)
        [
            'tableName' => $tableName,
            'join' => $join,
            'where' => $where,
            'orderBy' => $orderBy,
            'having' => $having,
            'limit' => $limit,
        ] = $this->getPolymerizeSql();
        $sql = "SELECT $polymerizeField($field) AS $polymerizeAlias FROM $tableName $join $where $this->groupBy $having $orderBy $limit";

        return Str::mergeSpaces($sql);
    }

    // 获取聚合SQL
    private function getPolymerizeSql() : array{
        // 设置SQL - (Warnning : 编译顺序不能变!)
        $tableName = $this->buildTableName();
        $join = $this->buildJoinSql();
        $where = $this->buildWhere();
        $orderBy = $this->buildOrder();
        $having = $this->buildHaving();
        $limit = $this->buildLimit();
        return [
            'tableName' => $tableName,
            'join' => $join,
            'where' => $where,
            'orderBy' => $orderBy,
            'having' => $having,
            'limit' => $limit,
        ];
    }

    // 编译having
    private function buildHaving(): string{
        return $this->having;
    }

    /**
     * 检测是否有条件
     * @throws Exception
     */
    private function checkWhereEmpty() {
        // 检测条件
        if($this->checkWhereEmpty && empty($this->where)){
            Console::appPrint("框架检测机制 - 除非您手动设置不需要条件！否则更新或删除数据时框架不允许没有条件执行!");
            Console::appPrint("手动设置不需要条件 - 调用 setNoWhereAndIKownWhatIDo() 方法!",Console::COLOR_YELLOW);
            throw new Exception("检测到数据变更没有条件!为了保障数据安全!框架不允许此类操作!");
        }
    }

    // 编译插入数据
    private function buildInsertData(array $data): array {
        $fieldSql = '';
        $valueSql = '';
        foreach ($data as $field => $value) {
            // 判断字段是否在表字段中 - 自动过滤表字段
            if (in_array($field, $this->tableFields)) {
                // 预编译字段
                $prepareValue = $this->buildPrepareValue($field);
                // 添加预编译数据
                $this->setPrepareValue($prepareValue, $value);
                // 判断前面有没有
                if ($fieldSql) { // 前面有
                    $fieldSql .= ",$field";
                    $valueSql .= ",$prepareValue";
                } else { // 前面没有
                    $fieldSql = $field;
                    $valueSql = $prepareValue;
                }
            }
        }
        return [$fieldSql, $valueSql];
    }

    // 编译prepareValue
    private function buildPrepareValue(string $field, ?int $index = NULL): string {
        // 判断预编译模式
        if ($this->prepareType == self::PREPARE_TYPE_ASSOC) {
            // 判断是否有索引
            if ($index === NULL) {
                return ":$field";
            }
            return ":$field" . '_' . $index;
        }
        return self::PREPARE_SYMBOL;
    }

    // 设置prepareValue
    private function setPrepareValue(string $prepareValue, string $value) {
        // 判断编译值是否是占位符
        if ($prepareValue != self::PREPARE_SYMBOL) {
            $this->prepareValue[$prepareValue] = $value;
        }
        array_push($this->prepareValueNumber, $value);
    }



    // 过滤表字段
    //    private function filterTableFields(array $data) : array{
    //        foreach ($data as $field => $value){
    //
    //        }
    //    }

    // 编译join
    private function buildJoinSql(): string {
        // join
        //        $sql = "SELECT * FROM a AS A
        //                LEFT JOIN b AS B ON A.id = B.a_id
        //                WHERE";
        $joinSql = '';
        // 编译join
        foreach ($this->join as $joinTable => $join) {
            ['joinType' => $joinType, 'joinAlias' => $joinAlias, 'onWhere' => $onWhere,] = $join;
            $sql = "$joinType $joinTable AS $joinAlias ON $onWhere";
            // 判断前面有没有
            if ($joinSql) { // 前面有
                $joinSql .= ' ' . $sql;
            } else { // 前面没有
                $joinSql = $sql;
            }
        }

        return $joinSql;
    }

    // join
    private function join(string $joinType, string $joinTable, string $joinAlias, string $onWhere) {
        // 设置编译类型为数值
        $this->prepareType = self::PREPARE_TYPE_NUMBER;
        $this->join[$joinTable] = ['joinType' => $joinType, 'joinAlias' => $joinAlias, 'onWhere' => $onWhere,];
    }

    // 获取编译表名 - 优先使用表别名
    private function buildTableName(): string {
        return $this->tableAlias ?: $this->tableName;
    }

    // 编译distinct
    private function buildDistinct(): string {
        return $this->distinct ? 'DISTINCT' : '';
    }


    // 编译排序
    private function buildOrder(): string {
        if (empty($this->orderBy)) {
            return '';
        }
        // 编译排序
        $orderSql = '';
        foreach ($this->orderBy as $field => $order) {
            // 判断前面有木有
            if ($orderSql) {
                $orderSql .= ",$field $order";
            } else {
                $orderSql = "ORDER BY $field $order";
            }
        }
        return $orderSql;
    }

    /**
     * 编译条件
     * @return string
     */
    private function buildWhere(): string {
        if (empty($this->where)) {
            return '';
        }

        $sql = '';
        // 编译条件
        foreach ($this->where as $field => $whereInfo) {
            [$union, $where] = $whereInfo;
            $whereCount = count($where);
            // 编译当前条件
            if ($whereCount == 2) { // 2 : ['age' => ['AND',['>',13]] => age > 13
                $compare = $where[0];
                $value = $where[1];
                $currentSql = "$field $compare $value";
            } else if ($whereCount == 3) { // 3 : ['id' => ['AND',['WHERE_ORDER','IN',[1,2,3,4,5,6]]]]
                // 暂时无其他 - 如果有则继续判断
                [$whereOrder, $order, $whereData] = $where;
                // 判断WhereOrder
                if ($whereOrder == self::WHERE_ORDER) {
                    // 判断条件类型
                    switch ($order) {
                        case self::WHERE_ORDER_IN: // IN
                            $inSql = '';
                            foreach ($whereData as $inValue) {
                                if ($inSql) {
                                    $inSql .= ',' . $inValue;
                                } else {
                                    $inSql = $inValue;
                                }
                            }
                            $currentSql = "$field IN ($inSql)";
                            break;
                        case self::WHERE_ORDER_BETWEEN: // BETWEEN
                            [$start, $end] = $whereData;
                            $currentSql = "$field BETWEEN $start AND $end";
                            break;
                        default: // 提示 : 不用抛异常
                            Console::appPrint("Where编译异常!未知的 order 错误!");
                            var_dump($order);
                            break;
                    }
                } else { // 提示 : 不用抛异常
                    Console::appPrint("Where编译异常! WhereOrder 错误!");
                    var_dump($where);
                }

            } else { // 4 : ['age' => ['AND',['>',13,'and','<',20]]] => (age > 13 and age < 20)
                $compare1 = $where[0];
                $value1 = $where[1];
                $subUnion = $where[2];
                $compare2 = $where[3];
                $value2 = $where[4];
                $currentSql = "($field $compare1 $value1 $subUnion $field $compare2 $value2)";
            }

            // 判断前面有没有
            if ($sql) { // 前面有条件
                $sql .= " $union $currentSql";
            } else {
                $sql .= " $currentSql";
            }
        }
        return 'WHERE' . $sql;
    }

    /**
     * 编译限制条数
     * @return string
     */
    private function buildLimit(): string {
        if (empty($this->limit)) {
            return '';
        }
        ['skip' => $skip, 'limit' => $limit] = $this->limit;
        // 判断skip
        if ($skip > 0) {
            return "LIMIT $skip,$limit";
        } else {
            return "LIMIT $limit";
        }
    }

    /**
     * 编译字段
     * @return string
     */
    private function buildFields(): string {
        // 检测是否有字段
        $fieldCount = count($this->fields);
        if ($fieldCount > 0) {
            $sql = '';
            // 编译字段
            for ($i = 0; $i < $fieldCount; $i++) {
                // 预处理字段
                $field = $this->prepareField($this->fields[$i]);
                // 判断后面还有没有
                if ($i + 1 < $fieldCount) { // 后面还有
                    $sql .= $field . ',';
                } else { // 后面没有了
                    $sql .= $field;
                }
            }

            return $sql;
        } else {
            return '*';
        }
    }


    /**
     * 表结构SQL
     * @return string
     */
    public function showColums(): string {
        $tableName = $this->prepareField($this->tableName);
        return "SHOW COLUMNS FROM `$tableName`";
    }

    /**
     * 预处理字段 - 防止SQL注入
     * @param string $field
     * @return string
     */
    public function prepareField(string $field): string {
        return str_replace(["'", '"', '`'], '', $field);
    }

}