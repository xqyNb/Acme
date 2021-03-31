<?php


namespace Acme\Lib\Async;

use Acme\App;
use Swoole\Table;

/**
 * Class FdManager - fd管理器-用于多进程共享数据
 * @package Acme\Lib\Async
 * @author Billion
 * @time 2021-01-08 14:01:03
 */
class FdManager {

    private static ?Table $table = NULL;

    // 初始化table
    public static function iniTable(){
        if(self::$table === NULL){
            [
                'FD_TABLE_SIZE' => $fdTableSize,
            ] = App::appConfigParam('MAIN_SERVER');

            self::$table = new Swoole\Table($fdTableSize);
            // 设置列
            self::$table->column('fd',Table::TYPE_INT);
        }
    }

    // 注册fd
    public static function registeFd(int $fd){
        if(self::$table !== NULL){
            self::$table->set($fd,['fd' => $fd,'mysqlWritePdo' => 0]);
        }
    }

    // 注销fd
    public static function deleteFd(int $fd) : bool{
        if(self::fdExist()){
            self::$table->del($fd);
        }
        return false;
    }

    // 判断fd是否存在
    public static function fdExist(int $fd) : bool{
        if(self::$table !== NULL && self::$table->exist($fd)){
            return true;
        }
        return false;
    }

    // 设置fd field值
    public static function setFdValue(int $fd,string $field,mixed $value):bool{
        if(self::fdExist($fd)){
            $fdInfo = self::getFd($fd);
            if($fdInfo !== NULL){
                $fdInfo[$field] = $value;
                return self::$table->set(fd,$fdInfo);
            }
        }
        return false;
    }

    // 获取fd field值
    public static function getFdValue(int $fd,string $field) : ?mixed{
        if(self::fdExist($fd)){
            $fdInfo = self::getFd($fd);
            if($fdInfo !== NULL){
                return $fdInfo[$field] ?? NULL;
            }
        }
        return NULL;
    }


    // 获取fd
    public static function getFd(int $fd):array|bool{
        if(self::fdExist($fd)){
            return self::$table->get($fd);
        }
        return false;
    }






}