<?php


namespace Acme\Lib\Method;

/**
 * Class FileMethod 文件操作相关方法
 * @package Acme\Lib\Method
 * @author Billion
 * @time 2021-01-20 15:49:17
 */
class FileMethod {

    /**
     * createDir 创建文件夹
     * @time 2020-01-03 12:27:05
     * @author Billion <443283829@qq.com>
     * @param string $path
     * @param integer $model
     * @return boolean
     */
    public static function createDir(string $path,int $model=0777) : bool{
        if(!is_dir($path)){
            return mkdir($path, $model, true);
        }
        return true;
    }

    /**
     * createFile 同步创建文件（非异步）
     * @time 2020-01-03 12:27:59
     * @author Billion <443283829@qq.com>
     * @param string $fileName
     * @param [type] $fileData
     * @param int $flag
     * @return boolean
     */
    public static function createFile(string $fileName , $fileData, int $flag=FILE_APPEND) : bool{
        $result = file_put_contents($fileName, $fileData,$flag);
        return $result !== false;
    }

    /**
     * 文件大小转换 MB -> KB(1024) -> B(1024)
     * @param int $mb
     * @return float|int
     */
    public static function sizeMbToB(int $mb){
        return $mb * 1024 * 1024;
    }

    /**
     * 文件大小转换 GB -> MB (1024) -> KB(1024) -> B(1024)
     * @param int $gb
     * @return int
     */
    public static function sizeGbToB(int $gb) : int{
        return $gb * 1024 * 1024 * 1024;
    }

    /**
     * 获取文件的大小和单位 - 最大EB
     * @param float $size
     * @param int $position
     * @return array
     */
    public static function getFileSize(float $size,int $position=0) : array{
        $units = ['B','KB','MB','GB','TB','PB','EB'];
        if($size > 1024){
            $size = $size/1024;
            return self::getFileSize($size,++$position);
        } else {
            $size = round($size,2);
            $unit = $units[$position];
            return [
                'size' => $size,
                'unit' => $unit,
                'display' => $size.$unit,
            ];
        }
    }

    /**
     * 获取文件信息
     * @param string $fileName - 文件名（绝对路径、相对路径）
     * @return array
     */
    public static function getFileInfo(string $fileName) : array{
        $fileInfo = [];
        // 判断文件是否存在
        if(file_exists($fileName)){
            $fileInfo['path'] = dirname($fileName); // 文件路径
            $fileInfo['fileName'] = basename($fileName); // 文件名
            $fileInfo['lastVisitTime'] = fileatime($fileName); // 上次访问时间
            $fileInfo['modifyTime'] = self::getFileModifyTime($fileName); // 文件修改时间
            $fileInfo['owner'] = posix_getpwuid(fileowner($fileName)); // 文件所有者
            $fileInfo['fileAuth'] = substr(sprintf('%o', fileperms($fileName)), -4); // 文件权限
            $fileInfo['fileType'] = filetype($fileName); // 文件类型：目录、文件
            $fileInfo['fileMime'] = mime_content_type($fileName); // 文件mime
            $fileInfo['fileSize'] = filesize($fileName); // 文件大小
            // 清除文件状态缓存!
            clearstatcache();
        }

        return $fileInfo;
    }

    // 读取文件的修改时间
    public static function getFileModifyTime(string $fileName):int|false{
        // 判断文件是否存在
        if(file_exists($fileName)){
            $modifyTime = filemtime($fileName); // 文件修改时间
            // 清除文件状态缓存!
            clearstatcache();
            return $modifyTime;
        }
        return false;
    }


}