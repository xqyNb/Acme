<?php


namespace Acme\DataBase\Pdo;

use Acme\App;
use Acme\Lib\Util\Console;
use Swoole\Coroutine\Channel;
use Swoole\Database\PDOConfig;
use Swoole\Database\PDOPool;
use Swoole\Database\PDOProxy;
use function Co\run;

/**
 * Class PdoManager pdo管理器 - 使用swoole pdo连接池x
 * @package Acme\DataBase\Pdo
 * @author Billion
 * @time 2021-01-04 14:23:50
 */
class PdoManager {

    private static bool $iniPdo = false; // 初始化
    private static array $pdoConfig = []; // pdo配置
    private static PDOPool $pdoPool; // pdo连接池
    private static array $fdPdoPool = []; // fd-pdo池子

    // 初始化
    public static function iniPdo(){
        if(self::$iniPdo){
            return;
        }
        self::$iniPdo = true;
        // 读取PDO配置
        [
            'driver' => $driver,
            'host' => $host,
            'port' => $port,
            'db' => $db,
            'charset' => $charset,
            'userName' => $userName,
            'password' => $password,
            'poolSize' => $poolSize,
        ] = self::$pdoConfig = App::appConfigParam('PDO');
        $pdoConfig = (new PDOConfig())->withHost($host)
//            ->withUnixSocket('/tmp/mysql.sock')
            ->withDriver($driver)
            ->withPort($port)
            ->withDbname($db)
            ->withCharset($charset)
            ->withUsername($userName)
            ->withPassword($password);

        self::$pdoPool = new PDOPool($pdoConfig,$poolSize);
    }

    /**
     * 获取pdo - 从连接池手动获取
     * @return PDOProxy
     */
    public static function getPdo():PDOProxy{
        return self::$pdoPool->get();
    }

    // 获取pdo - 从fd中（有效的保障了并发请求时或多携程写数据不会出现问题）
    public static function getPdoWithFd(int $fd) : Channel{
//        Console::out("获取pdo - Fd($fd)");
        if(!isset(self::$fdPdoPool[$fd])){ // 不存在 - 创建一个channel
            self::$fdPdoPool[$fd] = new Channel(1);
            self::$fdPdoPool[$fd]->push(self::getPdo());
        }
        return self::$fdPdoPool[$fd];
    }
    // 回收pdo - 从fd中
    public static function putPdoWithFd(int $fd,PDOProxy $pdo){
//        Console::out("放回pdo - Fd($fd)");
        self::$fdPdoPool[$fd]->push($pdo);
    }

    // 回收fd的pdo
    public static function releaseFdPdo(int $fd){
        // 判断fd是否存在
        if(isset(self::$fdPdoPool[$fd])){
//            Console::out("释放pdo - Fd($fd)");
            $pdo = (self::$fdPdoPool[$fd])->pop();
            // 放回pdo
            self::putPdo($pdo);
            // 删除fd
            unset(self::$fdPdoPool[$fd]);
        }

    }

    /**
     * PDO手动放回连接池 - 注意：这个PDO不应该是从 pdo() 里获取来的！
     * @param PDOProxy $pdo
     */
    public static function putPdo(PDOProxy $pdo){
//        Console::out('pdo被释放!');
        self::$pdoPool->put($pdo);
    }

    /**
     * 获取pdo - 自动放回连接池
     * @return PDOProxy
     */
    public static function pdo():PDOProxy{
        $pdo = self::$pdoPool->get();
        // defer
        defer(function ()use($pdo){
            self::$pdoPool->put($pdo);
        });
        return $pdo;
    }




}