<?php


namespace Acme\Lib\Request;

use Acme\AcmeInterface\IRequestManager;
use Acme\App;
use Acme\Lib\Data\Msgpack;
use Acme\Lib\Data\MsgpackData;
use Acme\Lib\Data\ResponseData;
use Acme\Lib\Log\LifeLog;
use Acme\Lib\Util\Console;
use Swoole\Http\Request as Request;
use Swoole\Http\Response as Response;

/**
 * Class RequestWsManager websocket请求管理器
 * @package Acme\Lib\Request
 * @author Billion
 * @time 2021-01-23 01:00:46
 */
class RequestWsManager implements IRequestManager {

    private Request $request;
    private Response $response;
    private MsgpackData $msgpackData;
    private bool $connected = true; // 是否连接
    private bool $actionClose = false; // 控制器是否需要断开连接 - 默认不断开
    private LifeLog $lifeLog; // 生命周期日志工具

    // 构造函数
    public function __construct(Request $request,Response $response){
        $this->request = $request;
        $this->response = $response;

        // 初始化生命周期日志
        $this->lifeLog = new LifeLog($this);
    }

    /**
     * 设置连接已关闭
     */
    public function setConnectedClose(){
        $this->connected = false;
    }

    /**
     * 控制器断开连接
     */
    public function actionCloseConnect(){
        $this->actionClose = true;
    }

    /**
     * 获取actionClose
     * @return bool
     */
    public function actionClose():bool{
        return $this->actionClose;
    }

    /**
     * 获取msgpackData
     * @return MsgpackData
     */
    public function msgpackData():MsgpackData{
        return $this->msgpackData;
    }


    /**
     * 控制器处理
     * @param MsgpackData $msgpackData
     * @return bool - 服务器是否需要主动断开
     * @throws \ReflectionException
     */
    public function controller(MsgpackData $msgpackData):bool{
        $this->msgpackData = $msgpackData;
        // 判断是否有控制器和方法
        if($msgpackData->hasControllerAndAction()){
            [
                'controller' => $controller,
                'action' => $action,
            ] = $msgpackData->getControllerAndAction();
            // 控制器加上命名空间
            $controller = App::wsControllerNamespace().$controller;
            // 判断类是否存在
            if(class_exists($controller)){
                // 反射
                // 检测方法是否存在
                $class = new \ReflectionClass($controller);
                $classMethods = $class->getMethods();
                $hasMethod = false;
                // 遍历是否有请求的方法
                foreach ($classMethods as $reflectionMethod) {
                    // 判断是否有该方法
                    if($reflectionMethod->name == $action){ // 找到了该方法
                        $hasMethod = true;
                        // 判断方法是否是public
                        if($reflectionMethod->isPublic()){
                            // 控制权交给控制器 TODO:
                            (new $controller($this))->$action();
                            // 如果控制器不返回是否断开 - 默认不断开
                            return $this->actionClose();
                        }else{
                            // 提示请求的方法不存在!
                            $message = "请求的Action[ $action ]必须是public!";
                            Console::appPrint($message);
                            $this->responseSuccessMessage($message);
                        }
                        break;
                    }
                }
                // 判断有没有
                if(!$hasMethod){
                    // 提示请求的方法不存在!
                    $message = "请求的Action[ $action ]不存在!";
                    Console::appPrint($message);
                    $this->responseSuccessMessage($message);
                }

            }else{
                $message = "请求的控制器[ $controller ]不存在!";
                Console::appPrint($message);
                $this->responseSuccessMessage($message);
            }

        }
        // 主动断开
        return true;
    }

    /**
     * 发送成功消息 - 单纯的发送一条消息给默认的客户端处理器
     * @param string $message
     */
    public function responseSuccessMessage(string $message){
        $this->responseSuccess(MsgpackData::DEFAULT_CONTROLLER,
            MsgpackData::DEFAULT_ACTION,
            MsgpackData::DATA_TYPE_OBJECT,
            [],
            $message,
        );
    }

    /**
     * 成功响应
     * @param string $controller
     * @param string $action
     * @param int $type
     * @param array $data
     * @param string $message
     */
    public function responseSuccess(string $controller=MsgpackData::DEFAULT_CONTROLLER,
                                    string $action=MsgpackData::DEFAULT_ACTION,
                                    int $type=MsgpackData::DATA_TYPE_OBJECT,array $data=[],string $message=''){
        $responseData = ResponseData::successInstance($data);
        // 判断是否有成功消息
        if($message){
            $responseData->setSuccessMessage($message);
        }
        // 获取msgpackData
        $msgpackData = new MsgpackData($responseData,$controller,$action,$type);
        // 发送数据给客户端
        $this->responseToClient($msgpackData);
    }


    /**
     * 响应数据给客户端
     * @param MsgpackData $msgpackData
     */
    public function responseToClient(MsgpackData $msgpackData){
        // 判断连接是否断开
        if($this->connected){
            $msgpack = Msgpack::serializeData($msgpackData);
            $this->response->push($msgpack,WEBSOCKET_OPCODE_BINARY); // 发送opcode为数据流(否则前端无法接收到数据流！)
        }else{
            Console::out('无法发送数据!客户端连接已断开!');
        }
    }

    /**
     * 返回fd
     * @return int
     */
    public function fd(): int {
        return $this->request->fd;
    }


    /**
     * 获取生命周期日志工具
     * @return LifeLog
     */
    public function lifeLog():LifeLog{
        return $this->lifeLog;
    }
}