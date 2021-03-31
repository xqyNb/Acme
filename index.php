<?php
//
//namespace test{
//    class A{
//        public static function a(){
//
//        }
//    }
//}
//namespace run{
//
//    use test\A as Aclass;
//
//
//    $ref = new \ReflectionClass(Aclass::class);
//    $name = $ref->getName();
//    $namespace = $ref->getNamespaceName();
//    echo $namespace.PHP_EOL;
//    echo $name.PHP_EOL;
//}




require_once __DIR__.'/vendor/autoload.php';

// 定义常量
// 根目录
defined('ACME_ROOT') or define('ACME_ROOT',realpath(getcwd()));
// 目录分隔符
defined('ACME_DS') or define('ACME_DS',DIRECTORY_SEPARATOR);
// 项目目录
defined('ACME_APP') or define('ACME_APP',ACME_ROOT.ACME_DS.'App');


// 启动服务
Acme\App::Start();
