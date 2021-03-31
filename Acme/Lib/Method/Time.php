<?php


namespace Acme\Lib\Method;

/**
 * Class Time 时间相关方法
 * @package Acme\Lib\Method
 * @author Billion
 * @time 2021-01-05 10:20:53
 */
class Time {

    /**
     * getToDayTime 获取今日的起始日期
     * @time 2020-01-03 11:00:53
     * @return array
     * @author Billion <443283829@qq.com>
     */
    public static function getToDayTime(): array {
        return ['startTime' => strtotime(date('Y-m-d 0:0:0')), 'endTime' => strtotime(date('Y-m-d 23:59:59')),];
    }

    /**
     * getDate 获取日期
     * @time 2020-01-03 12:20:05
     * @param int|null $timestamp
     * @param string $format
     * @return Str
     * @author Billion <443283829@qq.com>
     */
    public static function getDate(?int $timestamp = NULL, string $format = 'Y-m-d H:i:s'): string {
        $timestamp = $timestamp ?? time();
        return date($format, $timestamp);
    }

    /**
     * getMicrotime 获取微妙时间
     * @time 2020-01-03 12:34:57
     * @return string
     * @author Billion <443283829@qq.com>
     */
    public static function getMicrotime(): string {
        ['microtime' => $microtime] = self::getMicrotimeInfo();
        return $microtime;
    }

    /**
     * 计算microtime时间差
     * @param float $firstTime - 这个时间应该比secondTime大
     * @param float $secondTime
     * @param int $bit
     * @return float
     */
    public static function microtimeDiff(float $firstTime,float $secondTime,int $bit=8):float{
        return bcsub($firstTime,$secondTime,$bit);
    }

    /**
     * getMicrotimeInfo 获取微妙时间信息
     * @time 2020-01-03 12:36:07
     * @return array
     * @author Billion <443283829@qq.com>
     */
    public static function getMicrotimeInfo(): array {
        list($microsecond, $second) = explode(" ", microtime());
        $microtime = bcadd($second, $microsecond, 8);
        return ['second' => $second, // 秒
            'millisecond' => substr($microsecond, 2, 3), // 毫秒
            'microsecond' => $microsecond, // 微妙
            'microtime' => $microtime, // 微妙
        ];
    }

}