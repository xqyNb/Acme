<?php

namespace Acme\Lib\Util;

use Acme\App;
use Acme\Lib\Log\Log;

/**
 * Console 命令行操作类
 * @author Billion <443283829@qq.com>
 * @time 2021-01-02 18:42:14
 */
class Console{

    const COLOR_RED = 31; // 红色
    const COLOR_GREEN = 32; // 绿色
    const COLOR_YELLOW = 33; // 黃色
    const COLOR_BLUE = 34; // 蓝色
    const COLOR_PURPLE = 35; // 紫红色
    const COLOR_CYAN_BLUE = 36; // 青蓝色
    const COLOR_WHITE = 37; // 白色

    /**
     * 获取命令行参数
     * @param string $shortopts 短选项 -x
     * @param array $longopts 长选项 --xxxx
     * @return array
     */
    public static function command(string $shortopts='',array $longopts=[]) : array{
        return getopt($shortopts,$longopts);
    }


    /**
     * APPPrint - 框架调用
     * @param string $content - 打印内容
     * @param integer $color - 打印颜色
     * @param boolean $enter - 是否换行
     * @return void
     */
    public static function appPrint(string $content,int $color=self::COLOR_RED,bool $enter=true){
        // 判断是否是生产环境
        if(App::envIsProduce()){ // 生产环境 - 记录日志
            Log::friendConsoleLog(__FUNCTION__,$content,$enter);
        }else{ // 开发环境
            $content = App::frameName().' : '.$content;
            self::print($content,$color,$enter);
        }
    }

    /**
     * devPrint - 开发者调用
     * @param string $content
     * @param int $color
     * @param bool $enter
     */
    public static function devPrint(string $content,int $color=self::COLOR_RED,bool $enter=true){
        // 判断是否是生产环境
        if(App::envIsProduce()){ // 生产环境 - 记录日志
            Log::friendConsoleLog(__FUNCTION__,$content,$enter);
        }else{
            self::print($content,$color,$enter);
        }
    }

    /**
     * 原样输出 - 框架、开发者均可方便使用
     * @param string $content - 打印内容
     * @param boolean $enter - 打印颜色
     * @return void
     */
    public static function out(string $content,bool $enter=true){
        // 判断是否是生产环境
        if(App::envIsProduce()){ // 生产环境 - 记录日志
            Log::friendConsoleLog(__FUNCTION__,$content,$enter);
        }else{
            echo $content;
            if($enter){
                echo PHP_EOL;
            }
        }

    }


    /**
     * 友元回调 - 仅Acme\Lib\Log类允许调用!
     * @param string $content
     * @param int $color
     * @param bool $enter
     */
    public static function friendLogOut(string $content,int $color=self::COLOR_RED,bool $enter=true){
        // 友元回调
        $friend = Friend::call(Log::class);
        if($friend->isFriend()){
            self::print($content,$color,$enter);
        }else{
            self::print($friend->hint());
        }

    }

    /**
     * 控制台打印
     * @param string $content - 打印内容
     * @param integer $color - 打印颜色
     * @param boolean $enter - 是否换行
     * @return void
     */
    private static function print(string $content,int $color=self::COLOR_RED,bool $enter=true){
        echo "\033[1;{$color}m{$content} \e[0m";
        if($enter){
            echo PHP_EOL;
        }
    }




}