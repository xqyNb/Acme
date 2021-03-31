<?php


namespace Acme\Lib\Method;

/**
 * 随机方法 RandomMethod
 * @package Acme\Lib\Method
 * @author Billion
 * @time 2021-01-20 20:52:27
 */
class RandomMethod {
    /**
     * prabobilityTigger 几率触发器 - 默认 : 1/最大几率
     * @time 2020-01-03 13:00:53
     * @author Billion <443283829@qq.com>
     * @example 1,100 表示1%的几率
     * @example 1,1000 表示千分之一的几率
     * @example 1,10000 表示万分之一的几率 -> 以此类推
     * @param integer $probobility
     * @param integer $probobilityMax
     * @return boolean
     */
    public static function prabobilityTigger(int $probobility=1,int $probobilityMax=100) : bool{
        if($probobility >= 1 && $probobility <= $probobilityMax){
            mt_srand();
            $num = mt_rand(1,$probobilityMax);
            if($num <= $probobility){
                return true;
            }
        }
        return false;
    }

    /**
     *  randomByBit 生成指定位数的随机数
     * @time 2020-01-17 07:13:06
     * @author Billion <443283829@qq.com>
     * @param integer $bit
     * @return string
     */
    public static function randomByBit(int $bit) : string{
        $bitNumber = '';
        for ($i=0; $i < $bit; $i++) {
            mt_srand();
            $bitNumber .= mt_rand(0,9);
        }

        return $bitNumber;
    }
}