<?php

namespace Acme;

use Acme\DataBase\Pdo\PdoManager;
use Acme\DataBase\Redis\RedisManager;
use Acme\Lib\Async\FdManager;
use Acme\Lib\Data\Msgpack;
use Acme\Lib\Data\MsgpackData;
use Acme\Lib\Log\Log;
use Acme\Lib\Method\Time;
use Acme\Lib\Request\RequestManager;
use Acme\Lib\Request\RequestWsManager;
use Acme\Lib\Util\Console;
use Acme\Lib\Util\Loader;
use Acme\Lib\Util\Php;
use Swoole\Http\Request;
use Swoole\Http\Response;
use Swoole\Http\Server;
use \Swoole\Http\Server as SwooleHttp;
use function Co\run;


// 服务管理类
class App {

    const APP_VERSION = 'V1.0.0'; // 框架版本
    const APP_AUTHOR = 'Billion'; // 作者
    const APP_ENV_DEV = 'dev'; // 开发环境
    const APP_ENV_PRODUCE = 'produce'; // 生产环境
    const APP_NAMESPACE = 'App\\'; // app的命名空间
    const APP_FRAME_NAME = 'Acme'; // 框架名称
    const SERVER_TYPE_HTTP = 'http'; // web服务
    const SERVER_TYPE_WEBSOCKET = 'websocket'; // websocket服务

    private static string $appEnv = ''; // app当前环境
    private static array $appConfig = []; // app配置
    private static SwooleHttp $swooleHttp; // 服务器

    // 开始服务
    public static function Start() {
        // 启用一键协程
        \Co::set(['hook_flags'=> SWOOLE_HOOK_ALL]); // v4.4+版本使用此方法。
        // 解析命令
        self::parseCommand();
    }

    // 获取App的命名空间
    public static function namespace(): string {
        return self::APP_NAMESPACE;
    }
    // 获取App控制器的命名空间
    public static function controllerNamespace(): string{
        return self::namespace().'Controller\\';
    }
    // 获取App websocket控制器命名空间
    public static function wsControllerNamespace(): string{
        return self::namespace().'WsController\\';
    }

    // 获取App版本
    public static function version(): string {
        return self::APP_VERSION;
    }

    // 获取当前的app环境
    public static function appEnv(): string {
        return self::$appEnv;
    }

    // 判断是否是生成环境
    public static function envIsProduce() : bool{
        return self::$appEnv == self::APP_ENV_PRODUCE;
    }

    // 获取当前框架的名称
    public static function frameName(): string {
        return self::APP_FRAME_NAME;
    }

    // 获取app的配置文件
    public static function appConfig(): array {
        return self::$appConfig;
    }

    /**
     * 获取app配置参数
     * @param string $key - 数组索引键
     * @return mixed
     */
    public static function appConfigParam(string $key): mixed {
        $config = self::appConfig();
        return $config[$key] ?? '';
    }

    /**
     * 解析命令
     */
    private static function parseCommand() {
        // 短选项 -
        $shortopts = 's:'; // 服务命令
        $shortopts .= 'e:'; // 环境命令
        $shortopts .= 'v'; // 版本命令
        $shortopts .= 'h'; // 帮助命令
        // 长选项 --
        $longopts = ['version', // 版本命令
            'help', // 帮助命令
        ];

        // 获取命令行参数
        $commands = Console::command($shortopts, $longopts);
        // var_dump($commands);

        $appVersion = self::APP_VERSION;
        $appAuthor = self::APP_AUTHOR;
        // 判断是否是版本号
        if (isset($commands['v']) || isset($commands['version'])) { // 版本命令
            $version = <<<EOF
        当前Acme 版本 : $appVersion
        作者 : $appAuthor
      EOF;
            Console::appPrint($version, Console::COLOR_GREEN);
        } else if (isset($commands['h']) || isset($commands['help'])) { // 帮助命令
            $help = <<<EOF
        Acme致力于开发高性能的PHP应用!采用最新的PHP8与swoole为基础开发!
        当前版本号 : $appVersion
        作者 : $appAuthor
        邮箱 : 443283829@qq.com
        命令模式下可以使用以下命令 :
        -s 服务命令: start(启动服务),stop(停止服务),restart(重启服务)
        -e 环境命令: dev(开发环境),produce(生产环境)
        -h/--help 帮助命令 : 可以查看当前帮助信息
        -v/--version 版本命令 : 查看当前Acme的版本信息
      EOF;
            Console::out($help);
        } else if (isset($commands['s'])) { // 是否为启动命令
            // 匹配命令
            switch ($commands['s']) {
                case 'start': // 启动服务
                    // 获取环境
                    $env = $commands['e'] ?? '';
                    if ($env) {
                        // 判断环境是否匹配
                        if ($env == self::APP_ENV_DEV || $env == self::APP_ENV_PRODUCE) {
                            self::$appEnv = $env;
                            // 启动服务
                            self::startServer();
                        } else {
                            Console::appPrint('环境命令 -e 环境参数错误!您可以输入 -h 或 --help 以查看帮助文档!');
                        }
                    } else {
                        Console::appPrint('启动命令缺少 -e 环境参数!您可以输入 -h 或 --help 以查看帮助文档!');
                    }
                    break;

                default:
                    Console::appPrint('未知的启动服务参数!您可以输入 -h 或 --help 以查看帮助文档!');
                    break;
            }
        } else {
            Console::appPrint('未匹配到任何参数!您可以输入 -h 或 --help 以查看帮助文档!');
        }

    }

    // 启动服务
    private static function startServer(){
        // 加载配置文件
        $configFile = ACME_ROOT . '/Config/'.self::$appEnv.'.php';
        // 判断配置文件是否存在
        if (file_exists($configFile)){
            // 读取配置信息
            [
                'SERVER_TYPE' => $serverType,
            ] = self::$appConfig = Loader::loadPhpConfig($configFile);
            // 判断是HTTP服务还是Websocket服务 TODO:
            switch ($serverType){
                case self::SERVER_TYPE_HTTP: // http服务
                    self::startHttpServer();
                    break;
                case self::SERVER_TYPE_WEBSOCKET: // websocket服务
                    \Co\run(function (){
                        self::startWebsocketServer();
                    });
                    break;
                default:
                    Console::appPrint("服务类型错误!未知的服务类型:[ $serverType ]");
                    break;
            }


        }else{
            Console::appPrint('配置文件不存在!');
        }

    }

    // 启动websocket发送
    private static function startWebsocketServer(){
        // 获取服务器信息
        ['LISTEN_ADDRESS' => $listenAddress, 'LISTEN_PORT' => $listenPort,] = self::$appConfig['MAIN_SERVER'];
        // 创建easaySwoole
        $websocketServer = new \Co\Http\Server($listenAddress, $listenPort,false);
        // handle地址
        $websocketServer->handle('/websocket',function (Request $request,Response $ws){
            $ws->upgrade(); // 握手成功
            $first = false;
            // 初始化requestWsManager
            $requestWsManager = new RequestWsManager($request,$ws);

            // 循环监听
            while(true){
                $frame = $ws->recv(); // 调用 recv 方法时会挂起当前协程，等待数据到来时再恢复协程的执行
                // 判断frame结果
                if($frame === ''){ // 连接关闭
                    Console::out("[ $ws->fd ] - 客户端主动断开!");
                    $requestWsManager->setConnectedClose();
                    $ws->close(); // 关闭服务器链接 - 并退出循环
                    break;
                }else if($frame === false){ // 连接错误
                    $errorNumber = swoole_last_error();
                    // 忽略字节流
                    if($errorNumber != 60){ // 错误
                        Console::out("连接错误!错误码 : $errorNumber");
                        $requestWsManager->setConnectedClose();
                        break; // 断开连接
                    }
                }else{ // 连接成功
                    // 判断发送内容 是否为关闭操作
                    if($frame->data === '' || get_class($frame) === Swoole\WebSocket\CloseFrame::class){
                        Console::out("[ $ws->fd ] - 客户端要求断开!");
                        $requestWsManager->setConnectedClose();
                        $ws->close(); // 关闭服务器链接 - 并退出循环
                        break;
                    }

                    // 提示连接成功
                    if($first === false){
                        $first = true;
                        Console::out("客户端连接成功 - fd : $ws->fd");
                        // 发送成功消息
                        $requestWsManager->responseSuccessMessage('Acme: hello ^_^');
                    }

                    // 解包msgpack数据
                    $msgpackData = Msgpack::deserializeData($frame->data);
                    // 判断是否解析成功
                    if($msgpackData !== NULL){ // 解析成功

                        // 输出客户端发送来的数据
//                        Console::out("[ $ws->fd ] - 客户端发来数据:");
//                        var_dump([
//                            'msgpackData' => $frame->data,
//                        ]);

                        try {

                            // 数据处理交接给 RequestWsManager 控制器处理
                            $requestWsManager->lifeLog()->record('客户端发来数据','开始转给控制器!');
                            // 控制器处理
                            $close = $requestWsManager->controller($msgpackData);
                            // 控制器处理结束 - 计算处理事件写入生命周期日志
                            $startMicrotime = $requestWsManager->lifeLog()->startTime();
                            $useTime = Time::microtimeDiff(Time::getMicrotime(),$startMicrotime);
                            $requestWsManager->lifeLog()->record('控制器处理结束',"[请求耗时 - $useTime 秒]控制器结束!");
                            $requestWsManager->lifeLog()->endRecord();
                            // 判断是否主动断开
                            if($close === true){ // 服务器主动断开
                                $requestWsManager->setConnectedClose();
                                $ws->close();
                                break;
                            }

                        }catch (\Exception $e){
                            $code = $e->getCode();
                            $message = $e->getMessage();
                            $file = $e->getFile();
                            $line = $e->getLine();

                            // 设置错误信息
                            $errorMessage = <<<EOF
                控制器异常未处理!
                Code : $code
                File : $file
                Line : $line
                Message : $message

EOF;
                            Console::appPrint($errorMessage);

                        }

                    }else{ // 解析错误 - 强制退出(要求客户端格式必须为 msgpack)
                        // 发送成功消息
                        $requestWsManager->responseSuccessMessage('Acme: hello ^_^');
                        $requestWsManager->setConnectedClose();
                        $ws->close(); // 关闭服务器链接 - 并退出循环
                        break;
                    }

                }
            }

        });

        Console::out('websocket启动!');

        // 初始化Redis管理器
        RedisManager::iniRedis();
        // 初始化Pdo管理器
        PdoManager::iniPdo();

        // 启动服务
        $websocketServer->start();
    }



    /**
     * 启动http服务
     * @throws \Exception
     */
    private static function startHttpServer() {

        // 初始化fdManager table（用于共享内存）
//            FdManager::iniTable();

        // var_dump($appConfig);
        // 获取服务器信息
        ['LISTEN_ADDRESS' => $listenAddress, 'LISTEN_PORT' => $listenPort,] = self::$appConfig['MAIN_SERVER'];

        // 创建easaySwoole
        self::$swooleHttp = new SwooleHttp($listenAddress, $listenPort);
        self::$swooleHttp->on('request', function (Request $request,Response $response) {
            // 该回调函数中的所有操作已协程化！
            defer(function ()use($request){
                $fd = $request->fd;
                // fd回收 - pdo
                PdoManager::releaseFdPdo($fd);
                // 释放fd
//                    FdManager::deleteFd($fd);
            });

            // 注册fd
//                FdManager::registeFd($request->fd);

            // 交给请求管理器处理
            try{

                $requestManager = new RequestManager($request, $response);
                $requestManager->lifeLog()->record('请求到来','请求开始解析!');
                // 控制器处理
                $requestManager->controller();
                // 控制器处理结束 - 计算处理事件写入生命周期日志
                $startMicrotime = $requestManager->lifeLog()->startTime();
                $useTime = Time::microtimeDiff(Time::getMicrotime(),$startMicrotime);
                $requestManager->lifeLog()->record('请求结束',"[请求耗时 - $useTime 秒]控制器结束!");
                $requestManager->lifeLog()->endRecord();

            }catch (\Exception $e){
                $code = $e->getCode();
                $message = $e->getMessage();
                $file = $e->getFile();
                $line = $e->getLine();

                // 设置错误信息
                $errorMessage = <<<EOF
                控制器异常未处理!
                Code : $code
                File : $file
                Line : $line
                Message : $message

EOF;
                Console::appPrint($errorMessage);
                // 输出服务器繁忙给客户端!
                $response->header("Content-Type", "text/html; charset=utf-8");
                $response->end(APP::frameName().'</br><h1>服务器繁忙!请稍后再试!</h1></br><h1>Server is very busy! Please try again later!</h1>');
            }
        });

        // 服务启动
        self::$swooleHttp->on('start',function (Server $server){
            // 输出启动信息
            self::printRunInfo();

//                var_dump([
//                    'main_pid' => $server->master_pid,
//                    'manager_pid' => $server->manager_pid,
//                ]);

            // 记录主进程Pid
            Core::saveMasterPid($server->master_pid);
            // 记录日志
            Log::acmeLog('Acme is start!');
        });

        // 服务停止回调 - 注意: 强制 kill 进程不会回调 onShutdown，如 kill -9
        // 应该使用kill -15 master_pid 发送 SIGTERM 信号到主进程才能按照正常的流程终止
        self::$swooleHttp->on('Shutdown',function (Server $server){
            Console::out('Acme is normal exit! Bye ^_^!');
        });


        // TODO: 测试
        // 初始化Redis管理器
        RedisManager::iniRedis();
        // 初始化Pdo管理器
        PdoManager::iniPdo();



        // 启动easaySwoole
        self::$swooleHttp->start();


    }

    // 输出启动信息
    private static function printRunInfo(){
        // 输出启动信息
        $env = self::appEnv();
        $logo = self::getLogo();
        $appVersion = self::APP_VERSION;
        $appAuthor = self::APP_AUTHOR;
        $phpversion = Php::version();
        $swooleVersion = SWOOLE_VERSION;
        // 获取服务器信息
        ['LISTEN_ADDRESS' => $listenAddress, 'LISTEN_PORT' => $listenPort,] = self::$appConfig['MAIN_SERVER'];

        // 判断是否是生产环境
        if(self::envIsProduce()){
            $startInfo = [
                'Acme' => 'start',
                'version' => $appVersion,
                'authoer' => $appAuthor,
                'phpVersion' => $phpversion,
                'swooleVersion' => $swooleVersion,
                'env' => $env,
                'listenAddress' => $listenAddress,
                'listenPort' => $listenPort,
            ];
            Log::acmeLog($startInfo);
        }else{ // 开发环境
            // 设置开始信息
            $startInfo = <<<EOF
            $logo
            Acme启动!
            当前Acme 版本 : $appVersion
            作者 : $appAuthor
            PHP版本 : $phpversion
            Swoole版本号 : $swooleVersion
            环境 : $env
            监听地址 : $listenAddress
            端口号 : $listenPort
          EOF;
                Console::appPrint($startInfo, Console::COLOR_GREEN);
        }

    }

    // 停止Http服务器
    public static function stopHttpServer(){
        self::$swooleHttp->stop();
    }

    // 获取LOGO
    private static function getLogo(): string {
        return <<<EOF

    ___                             
   / _ \                            
  / /_\ \   ___   _ __ ___     ___  
  |  _  |  / __| | '_ ` _ \   / _ \
  | | | | | (__  | | | | | | |  __/
  \_| |_/  \___| |_| |_| |_|  \___|
EOF;
    }


}
