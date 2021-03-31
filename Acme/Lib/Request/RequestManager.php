<?php

namespace Acme\Lib\Request;

use Acme\AcmeInterface\IRequestManager;
use Acme\App;
use Acme\Lib\Language\Language;
use Acme\Lib\Log\LifeLog;
use Acme\Lib\Method\Hash;
use Acme\Lib\Session\SessionManager;
use Acme\Lib\Util\Console;
use ReflectionClass;
use ReflectionException;
use \Swoole\Http\Request as Request;
use \Swoole\Http\Response as Response;

/**
 * 请求管理器
 * @author Billion <443283829@qq.com>
 * @time 2021-01-02 14:25:57
 */
class RequestManager implements IRequestManager {

    const REQUEST_METHOD_GET = 'GET';
    const REQUEST_METHOD_POST = 'POST';
    const REQUEST_METHOD_PUT = 'PUT';
    const REQUEST_METHOD_OPTIONS = 'OPTIONS';

    const HTTP_STATUS_CODE_200 = 200; // 成功
    const HTTP_STATUS_CODE_301 = 301; // 资源（网页等）被永久转移到其它URL
    const HTTP_STATUS_CODE_404 = 404; // 请求的资源（网页等）不存在
    const HTTP_STATUS_CODE_500 = 500; // 内部服务器错误

    private Request $request;
    private Response $response;

    private bool $response404 = false; // 404响应
    private string $sessionId = '';
    private Language $language; // 语言工具
    private LifeLog $lifeLog; // 生命周期日志工具

    // 构造函数
    public function __construct(Request $request,Response $response){
        $this->request = $request;
        $this->response = $response;

        // var_dump($request);

        // 初始化工具
        $this->language = new Language();
        // 初始化生命周期日志
        $this->lifeLog = new LifeLog($this);
    }

    /**
     * 获取语言工具
     * @return Language
     */
    public function language():Language{
        return $this->language;
    }

    /**
     * 获取生命周期日志工具
     * @return LifeLog
     */
    public function lifeLog():LifeLog{
        return $this->lifeLog;
    }


    /**
     * 获取fd 
     * @return integer
     */
    public function fd() : int{
        return $this->request->fd;
    }

    /**
     * 获取header
     * @return array
     */
    public function header() : array{
        return $this->request->header;
    }

    /**
     * 获取header参数
     * @param string $key 数组参数键
     * @return string|null
     */
    public function headerParam(string $key) : ?string{
        $header = $this->header();
        return $header[$key] ?? NULL;
    }

    /**
     * 获取server function
     * @return array
     */
    public function server() : array{
        return $this->request->server;
    }

    /**
     * 获取server参数
     * @param string $key 数组参数键
     * @return string|null
     */
    public function serverParam(string $key) : ?string{
        $server = $this->server();
        return $server[$key] ?? NULL;
    }

    /**
     * 获取文件
     * @return array|null
     */
    public function files() : ?array{
        return $this->request->files;
    }

    /**
     * 获取临时文件
     * @return array|null
     */
    public function tmpfiles() : ?array{
        return $this->request()->tmpfiles;
    }

    /**
     * 设置cookie
     * @param string $key
     * @param string $value
     * @param int $expire - 默认 : 0 会话结束删除
     */
    public function setCookie(string $key,string $value,int $expire=0){
        $this->response()->cookie($key,$value,$expire);
    }


    /**
     * 获取cookie - key => value
     * @return array|null
     */
    public function cookie() : ?array{
        return $this->request->cookie;
    }

    /**
     * 获取sessionId - 如果没有，会自动初始化并返回
     * @return string
     */
    public function sessionId() : string{
        // 判断是否已设置sessionId
        if($this->sessionId){
            return $this->sessionId;
        }
        // 从cookie中获取
        $cookie = $this->cookie();
        $sessionIdKey = SessionManager::getSessionIdKey();
        if($cookie && isset($cookie[$sessionIdKey])){
            $this->sessionId = $cookie[$sessionIdKey];
            return $this->sessionId;
        }
        // 没有sessionId
        $this->sessionId = Hash::uniqueHashString();
        // 设置cookie
        $this->setCookie($sessionIdKey,$this->sessionId);
        return $this->sessionId;
    }

    /**
     * 获取get参数
     * @return array|null
     */
    public function get() : ?array{
        return $this->request->get;
    }

    /**
     * 获取post参数
     * @return array|null
     */
    public function post() : ?array{
        return $this->request->post;
    }

    /**
     * 获取请求控制器
     * @return Request
     */
    public function request() : Request{
        return $this->request;
    }

    /**
     * 获取响应控制器
     * @return Response
     */
    public function response() : Response{
        return $this->response;
    }

    /**
     * 获取host
     * @return string
     */
    public function host() : string{
        return $this->headerParam('host');
    }

    /**
     * 获取userAgent
     * @return string
     */
    public function userAgent() : string{
        return $this->headerParam('user-agent');
    }

    /**
     * 获取请求方法
     * @return string
     */
    public function requestMethod() : string{
        return $this->serverParam('request_method');
    }

    /**
     * 获取Post请求参数
     * @return array
     */
    public function postParams():array{
        return $this->request()->post ?: [];
    }

    /**
     * 获取Get请求参数
     * @return array
     */
    public function getParams():array{
        return $this->request()->get ?: [];
    }

    /**
     * 获取请求参数
     * @return array
     */
    public function Params():array{
        $getParams = $this->getParams();
        $postParams = $this->postParams();
        // 判断请求方式
        if($this->isPost()){ // post请求
            return array_merge($getParams,$postParams);
        }else{
            return array_merge($postParams,$getParams);
        }
    }

    /**
     * 判断是否是get
     * @return boolean
     */
    public function isGet() : bool{
        return $this->requestMethod() == self::REQUEST_METHOD_GET;
    }

    /**
     * 判断是否是post
     * @return boolean
     */
    public function isPost() : bool{
        return $this->requestMethod() == self::REQUEST_METHOD_POST;
    }

    /**
     * 判断是否是options
     * @return boolean
     */
    public function isOptions() : bool{
        return $this->requestMethod() == self::REQUEST_METHOD_OPTIONS;
    }

    /**
     * 获取请求路径
     * @return string
     */
    public function requestPath() : string{
        return $this->serverParam('path_info');
    }

    /**
     * 获取请求时间
     * @return integer
     */
    public function requestTime() : int{
        return $this->serverParam('request_time');
    }

    /**
     * 获取请求时间
     * @return float 返回浮点数 精确到小数点后6位
     */
    public function requestTimeFloat() : float{
        return $this->serverParam('request_time_float');
    }

    /**
     * 获取客户端Ip地址
     * @return string
     */
    public function clientIp() : string{
        return $this->serverParam('remote_addr');
    }

    /**
     * 获取客户端端口
     * @return integer
     */
    public function clientPort() : int{
        return $this->serverParam('remote_port');
    }

    /**
     * 获取控制器和方法信息
     * 支持多层级 - aa/bb/cc/dd
     * @return array
     */
    public function controllerInfo() : array{
        $pathList = explode('/',trim($this->requestPath(), '/'));
        // var_dump($pathList);
        // App\aa\bb
        // 判断路径长度
        $lenth = count($pathList);
        if($lenth == 1){ // www.xx.com || www.xx.com/aa
            $controller = $pathList[0] ? ucfirst($pathList[0]) : 'Index';
            $method = 'index';
        }else if($lenth == 2){ // www.xx.com/aa/bb
            $controller = ucfirst($pathList[0]);
            $method = $pathList[1];
        }else{ // www.xx.com/aa/bb/cc/dd...
            $method = array_pop($pathList);
            $pathList = array_map(function($value){
                return ucfirst($value);
            },$pathList);
            $controller = implode('\\',$pathList);
        }
        // var_dump([
        //     'controller' => $controller,
        //     'method' => $method,
        // ]);

        // 过滤掉后缀 xx.html
        [$action] = explode('.',trim($method, '.'));

        return [
            'controller' => $controller,
            'action' => $action,
        ];
    }

    /**
     * 请求处理
     * @throws ReflectionException
     */
    public function controller(){
        // 过滤浏览器的 - 图标请求
        if ($this->serverParam('path_info') == '/favicon.ico' || $this->serverParam('request_uri') == '/favicon.ico') {
            $this->response()->end();
            return;
        }
        // 路由解析
        $routerController = Router::controllerInfo($this->requestPath());
        // 判断是否有路由
        if($routerController != null){ // 有路由
            [$controller,$action] = $routerController;
        }else{
            // 解析路径
            [
                'controller' => $controller,
                'action' => $action,
            ] = $this->controllerInfo();
        }

        // 路由解析完毕 - 记录请求信息
        $this->lifeLog()->record("路由解析完毕",json_encode($this->requestInfo()));

        // 判断是否支持语言解析
        if($this->language->isSupport()){
            $controller = $this->language->parseController($controller);
        }

        // 控制器加上命名空间
        $controller = App::controllerNamespace().$controller;

        // 开始调度控制器
        $this->lifeLog()->record("开始调度控制器","controller: $controller,action: $action");
        // 执行控制器
        $this->runController($controller,$action);
        // 控制器调度结束
        $this->lifeLog()->record("控制器调度结束","controller: $controller,action: $action");
    }

    // 获取请求信息
    public function requestInfo() : array{
        return [
            'clienIp' => $this->clientIp(),
            'userAgent' => $this->userAgent(),
            'requestMethod' => $this->requestMethod(),
            'requestPath' => $this->requestPath(),
            'getParams' => $this->getParams(),
            'postParams' => $this->postParams(),
        ];
    }

    /**
     * 执行控制器
     * @param string $controller - 控制器名称（需要以命名空间开头）
     * @param string $action - 控制器对于的响应方法(必须是公开的)
     * @throws ReflectionException
     */
    private function runController(string $controller,string $action){
        // 判断类是否存在
        if(class_exists($controller)){
            // 检测方法是否存在
            $class = new ReflectionClass($controller);
            $classMethods = $class->getMethods();
            $hasMethod = false;
            // 遍历是否有请求的方法
            foreach ($classMethods as $reflectionMethod) {
                // 判断是否有该方法
                if($reflectionMethod->name == $action){ // 找到了该方法
                    $hasMethod = true;
                    // 判断方法是否是public
                    if($reflectionMethod->isPublic()){
                        // 控制权交给控制器
                        (new $controller($this))->$action();
                    }else{
                        // 提示请求的方法不存在!
                        Console::appPrint("请求的Action[ $action ]必须是public!");
                        $this->defaultResponse404();
                    }
                    break;
                }
            }
            // 判断有没有
            if(!$hasMethod){
                // 提示请求的方法不存在!
                Console::appPrint("请求的Action[ $action ]不存在!");
                $this->defaultResponse404();
            }

        }else{ // 控制器不存在!
            Console::appPrint("请求的控制器[ $controller ]不存在!");
            $this->defaultResponse404();
        }
    }

    /**
     * 响应给客户端
     * @param string $content
     * @return void
     */
    public function htmlToClient(string $content){
        $this->response()->header("Content-Type", "text/html; charset=utf-8");
        $this->responseContent($content);
    }

    /**
     * json响应给客户端
     * @param array $jsonArray - json信息（您可以方便的定义自己需要的code,msg,data）
     */
    public function jsonToClient(array $jsonArray){
        $this->response()->header("Content-Type", "application/json");
        $this->responseContent(json_encode($jsonArray));
    }


    /**
     * 302跳转
     * @param string $url 需要跳转的链接地址
     * @param int $httpCode http响应码 默认 : 302
     */
    public function redirect(string $url, int $httpCode = 302){
        $this->response()->redirect($url,$httpCode);
    }

    /**
     * 设置404响应内容
     * @param string $content
     */
    public function html404ToClient(string $content){
        $this->responseStatus(self::HTTP_STATUS_CODE_404);
        $this->htmlToClient($content);
    }

    /**
     * 默认响应 404
     * @return void
     * @throws ReflectionException
     */
    public function defaultResponse404(){
        // 判断有没有设置404控制器
        $action404 = App::appConfigParam('NOT_FOUND_ACTION');
        if($this->response404 || empty($action404)){ // 没有设置 - 默认的响应!
            // 如果是设置的404控制器错误 - 及时提示开发者!
            if($this->response404){
                Console::appPrint('配置文件设置的404控制器有错误！请及时检查并更改!');
            }
            $this->responseStatus(self::HTTP_STATUS_CODE_404);
            $this->htmlToClient(APP::frameName().' : 404NotFond!');
        }else{ // 有设置 - 执行自定义的404响应
            $this->response404 = true;
            [$controller,$action] = $action404;
            $this->runController(App::namespace().$controller,$action);
        }
    }

    /**
     * 设置响应码
     * @param int $httpStatusCode 响应码必须为合法的 HttpCode!
     * 如 200、502、301、404 等，否则会设置为 200 状态码
     * 此调用必须在responseToClient之前！
     */
    public function responseStatus(int $httpStatusCode){
        $this->response()->status($httpStatusCode);
    }

    /**
     * 设置框框架服务头 （自动设置框架头信息）
     * @param string $content 响应内容
     */
    private function responseContent(string $content){
        $this->response()->header("Server", App::frameName().'-'.App::version());
        $this->response()->end($content);
    }

}