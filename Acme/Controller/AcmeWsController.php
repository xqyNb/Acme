<?php


namespace Acme\Controller;

use Acme\Lib\Request\RequestWsManager;

/**
 * Class AcmeWsController websocket控制器
 * @package Acme\Controller
 * @author Billion
 * @time 2021-01-24 13:36:42
 */
class AcmeWsController {

    private RequestWsManager $requestWsManager;

    // 构造函数
    public function __construct(RequestWsManager $requestWsManager) {
        $this->requestWsManager = $requestWsManager;

        // 回调子类初始化
        $this->_ini();
    }

    // 子类的构造初始化
    protected function _ini(){}

    // 返回请求管理器
    protected function request():RequestWsManager{
        return $this->requestWsManager;
    }

    // 获取请求的数据
    protected function requestData():mixed{
        return $this->request()->msgpackData()->responseData()->data();
    }


}