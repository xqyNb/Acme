<?php

namespace Acme\Lib\Util;

/**
 * 加载器
 */
class Loader{

    /**
     * 加载一个php配置文件
     * @param string $filePath
     * @return array
     */
    public static function loadPhpConfig(string $filePath) : array{
        return require $filePath;
    }

}