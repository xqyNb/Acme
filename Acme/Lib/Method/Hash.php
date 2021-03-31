<?php


namespace Acme\Lib\Method;

/**
 * Class Hash 方法
 * @package Acme\Lib\Method
 * @author Billion
 * @time 2021-01-03 21:45:41
 */
class Hash {
    /**
     * sha256 sha256加密
     * @time 2020-01-03 12:25:01
     * @param string $content
     * @return string
     * @author Billion <443283829@qq.com>
     */
    public static function sha256(string $content): string {
        return hash('sha256', $content);
    }

    /**
     * uniqueString 获取一个唯一值
     * @time 2020-01-03 12:30:34
     * @return string
     * @author Billion <443283829@qq.com>
     */
    public static function uniqueString(): string {
        $random = 'uniqid_' . mt_rand(10000, 99999);
        return uniqid($random, true) . time() . uniqid(mt_rand(10000, 99999), true);
    }

    /**
     * uniqueHashString 获取一个hash唯一值
     * @time 2020-01-03 12:32:20
     * @param string $format
     * @return string
     * @author Billion <443283829@qq.com>
     */
    public static function uniqueHashString(string $format = 'SHA256'): string {
        $string = self::uniqueString();
        switch (strtoupper($format)) {
            case 'SHA256':
                $string = self::sha256($string);
                break;
            case 'MD5':
                $string = md5($string);
                break;
            default: // sha256
                $string = self::sha256($string);
                break;
        }
        return $string;
    }
}