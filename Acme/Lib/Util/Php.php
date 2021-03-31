<?php


namespace Acme\Lib\Util;

/**
 * Class Php
 * @package Acme\Lib\Util
 * @author Billion
 * @time 2021-01-03 17:56:07
 */
class Php {

    /**
     * 版本号
     * @param string|null $extension 如果指定了可选参数 extension，phpversion()会返回该扩展的版本。
     * 如果没有对应的版本信息，或者该扩展未启用，则返回 FALSE。
     * @return string|null
     */
    public static function version(string $extension=null) : ?string{
        return phpversion($extension);
    }

    /**
     * 回溯调用者
     * @param int $position - 回调的位置 默认：2 即使用者的前一个调用者
     * @return array
     */
    public static function caller(int $position=2) : array{
        $callerList = debug_backtrace();
        return $callerList[$position];
    }

}