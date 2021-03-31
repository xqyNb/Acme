<?php


namespace Acme\Lib\Session;

use Acme\App;
use Acme\DataBase\Redis\RedisManager;
use Acme\Lib\Method\RandomMethod;
use Acme\Lib\Util\Console;

/**
 * Session管理器 SessionManager
 * @package Acme\Lib\Session
 * @author Billion
 * @time 2021-01-20 19:45:40
 */
class SessionManager {

    const SESSION_KEY = '_ga'; // session对应的浏览器cookie key
    const SESSION_TIME_KEY = 'SESSION_TIME'; // session时间key
    const SESSION_CLEAN_STATUS_KEY = 'Acme_SESSION_CLEAN_STATUS'; // session Clean状态

    static private ?string $cacheName = NULL; // 缓存名

    /**
     * 获取sessionIdKey
     * @return string
     */
    public static function getSessionIdKey() : string{
        [
            'sessionId' => $sessionId,
        ] = App::appConfigParam('SESSION');
        return $sessionId;
    }


    /**
     * 设置session
     * @param string $sessionId
     * @param string $key - sessionKey 请勿使用纯大写命名！[可能会出现命名冲突]
     * @param mixed $value
     * @return bool
     */
    public static function setSession(string $sessionId,string $key,mixed $value): bool{
        $cacheName = self::cacheName();

        // 初始化session
        $redis = RedisManager::getRedis();
        // 获取session内容
        $session = self::session($redis,$cacheName,$sessionId);

        // 设置session
        $session[$key] = $value;
        $session[self::SESSION_TIME_KEY] = time();

        // 保存session
        $result = $redis->hSet($cacheName,$sessionId,json_encode($session));

        // 释放redis
        RedisManager::putRedis($redis);

        return $result !== false;
    }

    /**
     * 获取session
     * @param string $sessionId
     * @param string $key
     * @return mixed
     */
    public static function getSession(string $sessionId,string $key): mixed{
        $cacheName = self::cacheName();

        // 获取redis
        $redis = RedisManager::getRedis();
        // 获取session内容
        $session = self::session($redis,$cacheName,$sessionId);
        // 判断是否存在指定的key
        if(isset($session[$key])){
            $value = $session[$key];
            // 更新session操作时间
            $session[self::SESSION_TIME_KEY] = time();
            // 保存session
            $redis->hSet($cacheName,$sessionId,json_encode($session));
        }else{
            $value = NULL;
        }

        // 释放redis
        RedisManager::putRedis($redis);
        // 自动清理session
        self::autoCleanSession();
        return $value;
    }

    /**
     * 删除sessionKey
     * @param string $sessionId
     * @param string $key
     * @return bool
     */
    public static function deleteSessionKey(string $sessionId,string $key):bool{
        $cacheName = self::cacheName();
        // 获取redis
        $redis = RedisManager::getRedis();
        // 获取session内容
        $session = self::session($redis,$cacheName,$sessionId);
        // 判断是否存在指定的key
        if(isset($session[$key])){
            unset($session[$key]);
            // 更新session操作时间
            $session[self::SESSION_TIME_KEY] = time();
            // 保存session
            $result = $redis->hSet($cacheName,$sessionId,json_encode($session));
            $deleteResult = $result !== false;
        }else{
            $deleteResult = true;
        }

        // 释放redis
        RedisManager::putRedis($redis);
        return $deleteResult;
    }

    /**
     * 清空session
     * @param string $sessionId
     * @return bool
     */
    public static function cleanSession(string $sessionId):bool{
        $cacheName = self::cacheName();
        // 获取redis
        $redis = RedisManager::getRedis();
        // 判断有没有
        if($redis->hExists($cacheName,$sessionId)){
            $delete = $redis->hDel($cacheName,$sessionId);
            $result = $delete !== false;
        }else{
            $result = true;
        }

        // 释放redis
        RedisManager::putRedis($redis);
        return $result;
    }


    // 自动清除过期session
    private static function autoCleanSession(){
        [
            'cacheName' => $cacheName,
            'autoCleanProbobility' => $probobility,
            'autoCleanProbobilityMax' => $probobilityMax,
            'autoCleanCount' => $autoCleanCount,
            'expireTime' => $expireTime,
        ] = App::appConfigParam('SESSION');
        // 获取redis
        $redis = RedisManager::getRedis();
        $cleanCount = $redis->hLen($cacheName) ?: 0;
        // 计算触发几率
        $trigger = RandomMethod::prabobilityTigger($probobility,$probobilityMax);
        if($cleanCount >= $autoCleanCount || $trigger){
            // 获取clean状态
            if($redis->exists(self::SESSION_CLEAN_STATUS_KEY)){ // 正在处理session
//                Console::out('已忽略-其他进程正在处理Session..');
                return;
            }

//            Console::out('触发清理Session');

            // 设置cleanStatus
            $redis->set(self::SESSION_CLEAN_STATUS_KEY,1);

            // 获取所有的sessionKey
            $sessionKeys = $redis->hKeys($cacheName);
            // 遍历清除过期的session
            foreach ($sessionKeys as $index => $sessionKey){
                // 获取session信息
                $session = self::session($redis,$cacheName,$sessionKey);
                if($session){
                    $sessionTime = $session[self::SESSION_TIME_KEY];
                    // 判断session时间
                    if(time() - $sessionTime >= $expireTime){ // 已超时 - 清除
//                        Console::out("清除session : [ $sessionKey ]");
                        $redis->hDel($cacheName,$sessionKey);
                    }

                }
            }

            // 清除cleanStatus
            $redis->del(self::SESSION_CLEAN_STATUS_KEY);

        }

        // 释放redis
        RedisManager::putRedis($redis);
    }


    // 获取session内容
    private static function session(\Redis $redis,string $cacheName,string $sessionId):array{
        // 判断有没有
        if($redis->hExists($cacheName,$sessionId)){
            $sessionString = $redis->hGet($cacheName,$sessionId);
            return json_decode($sessionString,true);
        }
        return [];
    }

    // 获取session cacheName
    private static function cacheName():string{
        if(self::$cacheName === NULL){
            [
                'cacheName' => $cacheName,
            ] = App::appConfigParam('SESSION');
            self::$cacheName = $cacheName;
        }
        return self::$cacheName;
    }

}