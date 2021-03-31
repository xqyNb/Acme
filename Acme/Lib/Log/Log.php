<?php


namespace Acme\Lib\Log;

use Acme\Lib\Method\FileMethod;
use Acme\Lib\Method\Time;
use Acme\Lib\Util\Console;
use Acme\Lib\Util\Friend;

/**
 * Class Log 日志
 * @package Acme\Lib\Log
 * @author Billion
 * @time 2021-01-21 16:11:39
 */
class Log {
    const LOG_TYPE_ACME = 'Acme';
    const LOG_TYPE_APP = 'App';
    const LOG_TYPE_CONSOLE = 'Console';
    const LOG_TYPE_REQUEST = 'Request';

    const LOG_SUFFIX = '.log';
    const LOG_DIR = 'Log';
    const LOG_LEVEL_FATAL = 'FATAL'; // 致命错误
    const LOG_LEVEL_ERROR = 'ERROR'; // 一般错误
    const LOG_LEVEL_WARN = 'WARN'; // 警告
    const LOG_LEVEL_INFO = 'INFO'; // 一般信息
    const LOG_LEVEL_DEBUG = 'DEBUG'; // 调试信息

    /**
     * 自定义日志
     * @param array|string $content - 日志内容
     * @param string $level -
     * @param string $logType
     * @param string $logName
     */
    public static function customeLog(string|array $content,string $level=self::LOG_LEVEL_INFO,string $logName=self::LOG_TYPE_APP,string $logType=self::LOG_TYPE_APP){
        // logType不能是 Acme
        if($logType != self::LOG_TYPE_ACME){ // 非Acme日志
            self::writeLog($content,$level,$logName,$logType);
        }else{
            Console::friendLogOut("Warnning! 日志类型不能是[".self::LOG_TYPE_ACME."] -> [ $logType ]");
        }
    }


    /**
     * Acme 框架日志
     * @param array|string $content - 日志内容
     * @param string $level - 日志级别
     * @param string $logName
     */
    public static function acmeLog(string|array $content,string $level=self::LOG_LEVEL_INFO,string $logName=self::LOG_TYPE_ACME){
        self::writeLog($content,$level,$logName);
    }

    /**
     * 友元调用控制台日志 - 仅允许Acme\Lib\Util\Console调用
     * @param string $logName
     * @param string $content
     * @param bool $enter
     */
    public static function friendConsoleLog(string $logName,string $content,bool $enter=true){
        // 友元调用
        $fiend = Friend::call(Console::class);
        if($fiend->isFriend()){
            if($enter){
                $content = $content."\r\n";
            }
            self::writeLog($content,self::LOG_LEVEL_INFO,$logName,self::LOG_TYPE_CONSOLE);
        }else{
            Console::friendLogOut($fiend->hint());
        }
    }


    /**
     * 写入日志
     * @param array|string $content
     * @param string $level
     * @param string $logName
     * @param string $logType
     */
    private static function writeLog(string|array $content,string $level=self::LOG_LEVEL_INFO,string $logName=self::LOG_TYPE_ACME,string $logType=self::LOG_TYPE_ACME){
        // 设置日志数据
        $logData = [
            'level' => $level,
            'data' => $content,
        ];
        // 设置文件名
        $fileName = $logType.ACME_DS.$logName;
        self::writeFile($fileName,json_encode($logData));
    }

    // 写入文件
    private static function writeFile(string $fileName,string $content){
        $dataTime = Time::getDate(NULL,'Y-m-d');
        $logFile = ACME_ROOT.ACME_DS.self::LOG_DIR.ACME_DS.$fileName." $dataTime".self::LOG_SUFFIX;
        $logPath = dirname($logFile);
        // 创建文件夹
        if(FileMethod::createDir($logPath)){ // 异步 - 记录日志
            $content = Time::getDate().' : '.$content."\r\n";
//            var_dump([
//                'logFile' => $logFile,
//                'logPath' => $logPath,
//                'content' => $content,
//            ]);
            // 异步记录日志
            go(function ()use($logFile,$content){
                FileMethod::createFile($logFile,$content);
            });
        }else{
            Console::friendLogOut("Warnning! 无法创建文件目录！请检查文件目录权限或文件路径！- [ $logPath ]");
        }
    }

}