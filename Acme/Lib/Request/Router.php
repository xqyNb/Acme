<?php

namespace Acme\Lib\Request;

use Acme\App;

/**
 * Router 路由解析器
 * @author Billion <443283829@qq.com>
 * @time 2021-01-03 12:19:52
 */
class Router{

    // 获取路由控制器
    public static function controllerInfo(string $pathInfo) : ?array{
        // 清除后缀
        [$path] = explode('.',trim($pathInfo, '.'));
        // 读取路由配置
        $routerConfig = App::appConfigParam('ROUTER');
        // 判断有没有路由
        if(isset($routerConfig[$path])){
            return $routerConfig[$path];
        }
        return null;
    }
    

}