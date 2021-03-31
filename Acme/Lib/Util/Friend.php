<?php


namespace Acme\Lib\Util;

/**
 * Class Friend 友元需求类
 * @package Acme\Lib\Util
 * @author Billion
 * @time 2021-01-21 18:04:22
 */
class Friend {

    private bool $isFriend;
    private string $callerName;
    private string $friendName;

    // 构造函数
    public function __construct(bool $isFriend,string $callerName,string $friendName) {
        $this->isFriend = $isFriend;
        $this->callerName = $callerName;
        $this->friendName = $friendName;
    }

    /**
     * 是否是友元
     * @return bool
     */
    public function isFriend():bool{
        return $this->isFriend;
    }

    /**
     * 获取调用者类名
     * @return string
     */
    public function callerName():string{
        return $this->callerName;
    }

    /**
     * 获取提示信息
     * @return string
     */
    public function hint():string{
        return '[ '.$this->callerName().' ] 不允许调用! 它不是该类的友元类！';
    }


    /**
     * 友元调用 - 实现只允许某个类调用判断
     * @param mixed $friend
     * @return Friend
     */
    public static function call(mixed $friend):Friend{
        $caller = Php::caller(3);
        $callerName = $caller['class'];
        // 反射对比
        try {
            $ref = new \ReflectionClass($friend);
            $friendName = $ref->getName();
            $isFriend = $callerName == $friendName;
            $friend = new Friend($isFriend,$callerName,$friendName);
        } catch (\ReflectionException $e) {
            $friend = new Friend(false,$callerName,'Reflection fail!');
        }

        return $friend;
    }


}