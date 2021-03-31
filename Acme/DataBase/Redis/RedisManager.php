<?php


namespace Acme\DataBase\Redis;

use Acme\App;
use Acme\Lib\Util\Console;
use Redis;
use Swoole\Database\RedisConfig;
use Swoole\Database\RedisPool;

/**
 * Class RedisManager redis管理器 - 使用swoole redis连接池
 * @package Acme\DataBase\Redis
 * @author Billion
 * @time 2021-01-03 18:13:19
 */
class RedisManager {

    private static bool $iniRedis = false; // 初始化
    private static array $redisConfig = []; // redis配置
    private static RedisPool $redisPool; // redis连接池

    /**
     * 初始化redis
     */
    public static function iniRedis() {
        if (self::$iniRedis) {
            return;
        }
        self::$iniRedis = true;
        // 读取redis配置
        ['host' => $host, 'port' => $port, 'auth' => $auth, 'db' => $db, 'timeout' => $timeout, // 超时连接 1秒
            'poolSize' => $poolSize, // 连接池超的数量
        ] = self::$redisConfig = App::appConfigParam('REDIS');
        // 初始化连接池
        $redisConfig = (new RedisConfig())->withHost($host)->withPort($port)->withAuth($auth)->withDbIndex($db)->withTimeout($timeout);
        self::$redisPool = new RedisPool($redisConfig, $poolSize);
    }

    /**
     * 手动获取redis - 使用该方法后一定要释放Redis(业务逻辑建议直接调用redis自动释放)
     * @return Redis
     */
    public static function getRedis():Redis{
        return self::$redisPool->get();
    }

    /**
     * 手动释放redis
     * @param Redis $redis
     */
    public static function putRedis(Redis $redis){
        self::$redisPool->put($redis);
    }


    /**
     * 获取redis - 该redis会自动释放
     * Warnning : 除非您的业务只用到HTTP短连接应用！否则您应该使用getRedis手动获取和释放！以提高性能！
     *  否则您的长连接会不断的获取连接导致无连接可用！- 长连接中自动释放会在连接断开后触发!
     * @return Redis
     */
    public static function redis(): Redis {
        $redis = self::$redisPool->get();
        // defer - 用完自动回收
        defer(function () use ($redis) {
//            Console::out('Defer redis 自动释放!');
            self::$redisPool->put($redis);
        });
        return $redis;
    }

}