<?php

// 开发环境配置
return [
    'SERVER_NAME' => 'Acme', // 服务器名称
    // 服务类型
    'SERVER_TYPE' => 'websocket', // http or websocket
    // 服务器配置
    'MAIN_SERVER' => [
        'LISTEN_ADDRESS' => '0.0.0.0',
        'LISTEN_PORT' => 8888,
        'FD_TABLE_SIZE' => 8192, // fd - 使用多协程Tabe共享内存实现（内存足够的情况应尽量调大!）
    ],

    // 路由配置
    'ROUTER' => [
        // 路径 => ['控制器class名','方法名']
        '/userPage' => ['User\Info','page'],
    ],
    // 自定义404控制器(警告！如果设置此项你必须要保证该控制器是存在的!) - 格式与路由配置相同
    'NOT_FOUND_ACTION' => ['Error\Error','notFound'],

    // 语言功能
    'LANGUAGE' => [ // 语言功能
        'support' => true, // 是否支持语言功能 - 如果开启该功能（则路由解析会以为 xx.com/language为最优先级）
        'default' => 'zh-cn', // 默认语言包
        'langList' => [ // 为了避免命名冲突，请不要再控制器路径里加"-"
            'zh-cn' => '中文简体', // 中文简体
            'zh-tw' => '中文繁體', // 中文繁体
            'en-us' => 'English(US)', // 美式英语
            'ja-jp' => '日本語', // 日语
        ],
    ],

    // 模板配置
    'TEMPLATE' => [
        'cache' => true, // 是否开启模板缓存
        'cacheName' => 'Acme_template',// 缓存名称
        'leftOperator' => '{$', // 模板引擎的左操作符
        'rightOperator' => '}', // 模板引擎的右操作符
        'suffix' => '.html',
        'variables' => [ // 模板变量 - 默认给每个模板都自动分配的变量
            'VERSION' => 'v1.0.0',
        ],
    ],

    // Session配置 - 框架继承Redis储存
    'SESSION' => [
        'sessionId' => 'Acme_id', // 出于安全考虑浏览器已不再自发发送cookieId,所以需要需要服务端自己设定SessionId
        'cacheName' => 'Acme_session',
        'autoCleanProbobility' => 1, // 自动清除的几率（1/autoCleanProbobilityMax）
        'autoCleanProbobilityMax' => 1000, // 自动清除的几率（autoCleanProbobility/1000）
        'expireTime' => 1800, // session过期时间，超过这个时间session回收时会删除session（秒）
        'autoCleanCount' => 5000, // 自动清理数量 - 当session值达到该值时会触发强制触发自动清理
    ],

    // redis配置
    'REDIS' => [
        'host'          => '192.168.0.106',
        'port'          => 6379,
        'auth'          => 'c155b750a771d3452be3c943b54e883e',
        'db'            => 0,
        'timeout'  => 1.0, // 超时连接 1秒
        'poolSize' => 64, // 连接池的redis数量(注意:由于是使用Swoole原生的协程，争抢问题不会有！
        // 但是你必须保证一个请求所用到的最大redis个数足够！否则就会无限等待！)
    ],
    // pdo配置
    'PDO' => [
        'driver' => 'mysql', // 驱动mysql - 需要确保安装了pdo_mysql
        'host' => '192.168.0.106',
        'port' => 3306,
        'db' => 'myself',
        'charset' => 'utf8mb4',
        'userName' => 'root',
        'password' => 'a97213569bbdea2f854c005ebf92e735',
        'poolSize' => 64, // 连接池的连接数
    ],

];
