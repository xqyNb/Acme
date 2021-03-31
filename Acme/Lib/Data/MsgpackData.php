<?php


namespace Acme\Lib\Data;

use Acme\Lib\Util\Console;

/**
 * Class MsgpackData 数据类型
 * @package Acme\Lib\Data
 * @author Billion
 * @time 2021-01-23 14:56:10
 */
class MsgpackData {

    const DATA_TYPE_OBJECT = 1; // 数据类型:对象
    const DATA_TYPE_BINARY = 2; // 数据类型:二进制流
    const FIELD_TYPE = 't'; // 字段 - 数据类型
    const FIELD_DATA = 'd'; // 字段 - 数据
    const FIELD_CONTROLLER = 'c'; // 字段 - 控制器(前端、后端)
    const FIELD_ACTION = 'a'; // 字段 - 方法(前端、后端)
    const FIELD_MESSAGE = 'm'; // 字段 - 消息
    const FIELD_SUCCESS_CODE = 's'; // 字段 - 是否成功（1成功,其他数字为错误！）

    const DEFAULT_CONTROLLER = 'default'; // 客户端/服务器默认处理器
    const DEFAULT_ACTION = 'default'; // 客户端/服务器默认方法


    private string $controller; // 控制器
    private string $action; // 方法
    private int $type; // 数据类型
    private ResponseData $responseData; // 响应数据

    // 构造函数
    public function __construct(ResponseData $responseData,
                                string $controller=self::DEFAULT_CONTROLLER,
                                string $action=self::DEFAULT_ACTION,
                                int $type=self::DATA_TYPE_OBJECT){
        $this->responseData = $responseData;
        $this->controller = $controller;
        $this->action = $action;
        $this->type = $type;
    }


    /**
     * 获取responseData
     * @return ResponseData
     */
    public function responseData():ResponseData{
        return $this->responseData;
    }

    /**
     * 是否有控制器和方法
     * @return bool
     */
    public function hasControllerAndAction():bool{
        return $this->controller && $this->action;
    }

    /**
     * 获取控制器和方法
     * @return array
     */
    public function getControllerAndAction():array{
        return [
            'controller' => $this->controller,
            'action' => $this->action,
        ];
    }

    /**
     * 解析客户端MsgpackData数据
     * @param array $data
     * @return MsgpackData|null
     */
    public static function parseClientMsgpackData(array $data): ?MsgpackData{
        // 检测数据是否符合MsgpackData规范
        if(self::isMsgpackDataRule($data)){
            $responseData = ResponseData::successInstance($data[self::FIELD_DATA]);
            return new MsgpackData($responseData,$data[self::FIELD_CONTROLLER],
                $data[self::FIELD_ACTION],$data[self::FIELD_TYPE]);
        }
        return NULL;
    }

    // 检测数据是否符合MsgpackData规范
    public static function isMsgpackDataRule(array $data):bool{
        if(isset($data[self::FIELD_CONTROLLER]) && isset($data[self::FIELD_ACTION])
            && isset($data[self::FIELD_TYPE]) && isset($data[self::FIELD_DATA])
        ){
            return true;
        }
        return false;
    }


    /**
     * 获取服务器响应数据
     * @return array
     */
    public function serverResponseData():array{
        return [
            self::FIELD_CONTROLLER => $this->controller,
            self::FIELD_ACTION => $this->action,
            self::FIELD_TYPE => $this->type,

            self::FIELD_SUCCESS_CODE => $this->responseData->code(),
            self::FIELD_MESSAGE => $this->responseData->message(),
            self::FIELD_DATA => $this->responseData->data(),
        ];
    }





}