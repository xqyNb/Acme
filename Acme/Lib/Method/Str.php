<?php


namespace Acme\Lib\Method;

/**
 * Class Str 字符串处理
 * @package Acme\Lib\Method
 * @author Billion
 * @time 2021-01-05 19:38:42
 */
class Str {
    /**
     * repeatString 获取重复的字符串
     * @time 2020-01-03 12:49:11
     * @param string $base
     * @param integer $count
     * @return string
     * @author Billion <443283829@qq.com>
     */
    public static function repeatString(string $base, int $count = 1): string {
        $string = '';
        for ($i = 0; $i < $count; $i++) {
            $string .= $base;
        }
        return $string;
    }

    /**
     * stringAddCharactor 字符补位
     * @time 2020-01-10 02:58:17
     * @param string $string
     * @param integer $count
     * @param string $charector
     * @param boolean $addFront
     * @return string
     * @author Billion <443283829@qq.com>
     */
    public static function stringAddCharactor(string $string, int $count, string $charector = '0', bool $addFront = true): string {
        // 循环
        for ($i = 0; $i < $count; $i++) {
            // 判断是否添加到字符前面
            if ($addFront) { // 添加到字符前面
                $string = $charector . $string;
            } else { // 添加到字符后面
                $string = $string . $charector;
            }
        }
        return $string;
    }

    /**
     * 清除连续的空格
     * @param string $content
     * @return string
     */
    public static function mergeSpaces(string $content) : string{
        return preg_replace("/\s(?=\s)/","\\1",$content);
    }


    /**
     * 隐藏姓名 - 王二狗 => 王**
     * @param string $name
     * @return string
     */
    public static function hiddenName(string $name): string {
        $hiddenName = mb_substr($name, 0, 1);
        return $hiddenName . self::repeatString('*', mb_strlen($name) - 1);
    }

    /**
     * hiddenIdcard 隐藏身份证号码
     * @time 2020-01-14 04:18:51
     * @param string $idcard
     * @param integer $startBit
     * @param integer $endBit
     * @return string
     * @author Billion <443283829@qq.com>
     */
    public static function hiddenIdcard(string $idcard, int $startBit = 3, int $endBit = 4): string {
        $start = mb_substr($idcard, 0, $startBit);
        $hidden = self::repeatString('*', mb_strlen($idcard) - $endBit);
        $end = mb_substr($idcard, -$endBit, $endBit);
        return $start . $hidden . $end;
    }

    /**
     * 隐藏字符串
     * @param string $content 字符串内容
     * @param int $startBit 开始位置
     * @param int $endBit 结束位置
     * @param string $letter 替换符号 默认:*
     * @return string
     */
    public static function hiddenString(string $content, int $startBit, int $endBit, string $letter = '*'): string {
        $start = mb_substr($content, 0, $startBit);
        $hidden = self::repeatString($letter, mb_strlen($content) - $endBit);
        $end = mb_substr($content, -$endBit, $endBit);
        return $start . $hidden . $end;
    }

    /**
     * stringToBoolean 转换字符串为布尔值(视false字符串为false)
     * @time 2020-01-23 08:24:58
     * @param string $str
     * @return boolean
     * @author Billion <443283829@qq.com>
     */
    public static function stringToBoolean(string $str): bool {
        if ($str == 'false') {
            return false;
        }
        return (boolean)$str;
    }

    /**
     * 字符串替换 - 替换1次
     * @param string $needle
     * @param string $replace
     * @param string $haystack
     * @return string
     */
    public static function stringReplaceOnce(string $needle,string $replace,string $haystack):string {
        $pos = strpos($haystack, $needle);
        if ($pos === false) {
            return $haystack;
        }
        return substr_replace($haystack, $replace, $pos, strlen($needle));
    }
}