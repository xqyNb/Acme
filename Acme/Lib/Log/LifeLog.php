<?php


namespace Acme\Lib\Log;

use Acme\AcmeInterface\IRequestManager;
use Acme\App;
use Acme\Lib\Method\Time;
use Acme\Lib\Request\RequestManager;
use Acme\Lib\Util\Console;

/**
 * Class LifeLog 生命周期日志
 * @package Acme\Lib\Log
 * @author Billion
 * @time 2021-01-21 22:07:05
 */
class LifeLog {

    private array $logDataList = [];
    private float $startTime = 0;
    private IRequestManager $requestManager;

    // 构造函数
    public function __construct(IRequestManager $requestManager){
        $this->requestManager = $requestManager;
    }


    /**
     * 获取开始时间
     * @return float
     */
    public function startTime():float{
        return $this->startTime;
    }

    // 记录日志
    public function record(string $nodeName,string $content){
        $microtime = Time::getMicrotime();
        // 判断是否有开始时间
        if($this->startTime == 0){
            $this->startTime = $microtime;
        }
        // 计算时间差
        if($microtime == $this->startTime){
            $useTime = 0;
        }else{
            $useTime = Time::microtimeDiff($microtime,$this->startTime);
        }
        // 设置content
        $fd = $this->requestManager->fd();
        $nodeNumber = count($this->logDataList) + 1;
        $content = "(fd:($fd) 节点($nodeNumber):$nodeName) - <耗时:$useTime 秒> | $content";
        // 记录日志
        array_push($this->logDataList,[
            'nodeName' => $nodeName,
            'content' => $content,
            'microtime' => $microtime,
            'useTime' => $useTime,
        ]);
    }

    // 结束记录
    public function endRecord(){
        // 判断当前的环境 TODO:
        if(App::envIsProduce()){ // 生产模式
            // 记录请求日志
            Log::acmeLog($this->logDataList,Log::LOG_LEVEL_INFO,Log::LOG_TYPE_REQUEST);
        }else{ // 开发环境
            // 循环输出至控制台
            foreach ($this->logDataList as $logData){
                [
                    'content' => $content,
                ] = $logData;
                Console::out($content);
            }
        }



    }


}