<?php

namespace Acme;

// 核心类
use Acme\Lib\Method\FileMethod;
use Acme\Lib\Util\Console;

class Core{

    const PID_PATH = ACME_ROOT.ACME_DS.'Pid';
    const PID_FILE_NAME = self::PID_PATH.ACME_DS.'pid.txt';

    /**
     * 保存主进程pid
     * @param int $pid
     */
    public static function saveMasterPid(int $pid){
        // 创建文件夹
        if(FileMethod::createDir(self::PID_PATH)){
            // 创建文件
            FileMethod::createFile(self::PID_FILE_NAME,$pid,0);
        }else{
            Console::appPrint("主进程Pid文件夹无法创建！请检查是否有权限! path -> ");
        }
    }

    /**
     * 读取主进程Pid
     * @return false|int - 读取失败或文件不存在 返回false
     */
    public static function readMasterPid():int|false{
        if(file_exists(self::PID_FILE_NAME)){
            return file_get_contents(self::PID_FILE_NAME);
        }
        return false;
    }

}