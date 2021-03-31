<?php


namespace Acme\Controller;


use Acme\Lib\Language\Language;
use Acme\Lib\Method\Hash;
use Acme\Lib\Request\RequestManager;
use Acme\Lib\Session\SessionManager;
use Acme\Lib\Util\Console;
use Acme\Lib\Util\Php;
use Acme\View\View;

/**
 * 框架控制器 AcmeController
 * @package Acme\Controller
 * @author Billion
 * @time 2021-01-20 18:56:03
 */
class AcmeController {
    private ?View $view = NULL;
    private RequestManager $requestManager;

    // 构造函数
    public function __construct(RequestManager $requestManager){
        $this->requestManager = $requestManager;

        // 回调子类初始化
        $this->_ini();

    }

    // 子类的构造初始化
    protected function _ini(){}

    // 获取语言工具
    protected function language():Language{
        return $this->request()->language();
    }


    /**
     * 设置cookie
     * @param string $key
     * @param string $value
     * @param int $expire - 默认 : 0 会话结束删除
     */
    protected function setCookie(string $key,string $value,int $expire=0){
        $this->request()->setCookie($key,$value,$expire);
    }

    /**
     * 设置session
     * @param string $key - sessionKey 请勿使用纯大写命名！[可能会出现命名冲突]
     * @param mixed $value
     * @return bool
     */
    protected function setSession(string $key,mixed $value):bool{
        $sessionId = $this->request()->sessionId();
        if($sessionId){
            return SessionManager::setSession($sessionId,$key,$value);
        }
        return false;
    }

    /**
     * 获取session
     * @param string $key
     * @return mixed
     */
    protected function getSession(string $key) : mixed{
        $sessionId = $this->request()->sessionId();
        return SessionManager::getSession($sessionId,$key);
    }


    /**
     * 删除sessionKey
     * @param string $key
     * @return bool
     */
    protected function deleteSessionKey(string $key): bool{
        $sessionId = $this->request()->sessionId();
        return SessionManager::deleteSessionKey($sessionId,$key);
    }

    /**
     * 清空session
     * @return bool
     */
    protected function cleanSession():bool{
        $sessionId = $this->request()->sessionId();
        return SessionManager::cleanSession($sessionId);
    }


    /**
     * 分配数据
     * @param string $key - 对于模板变量名
     * @param string $value - 模板的值【应该为一个处理好的字符串】
     */
    protected function assign(string $key,string $value){
        $this->view()->assign($key,$value);
    }

    /**
     * 显示数据到浏览器
     * @param string $templateName 模板名称（默认为控制器方法名）
     */
    protected function display(string $templateName=''){
        $caller = Php::caller();
        $templateName = $templateName ?: $caller['function'];
        // 判断是否支持语言
        if($this->language()->isSupport()){
            $this->assign('LANG',$this->language()->getLang());
            $html = $this->view()->getDisplay($templateName,$this->language());
        }else{
            $html = $this->view()->getDisplay($templateName);
        }

        // 输出模板内容到浏览器
        $this->request()->htmlToClient($html);
    }

    // 获取请求控制器
    protected function request():RequestManager{
        return $this->requestManager;
    }


    // 获取view
    private function view() : View{
        if($this->view === NULL){
            $this->view = new View();
        }
        return $this->view;
    }

}