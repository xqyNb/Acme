<?php


namespace App\WsController;

use Acme\Controller\AcmeWsController;
use Acme\DataBase\Redis\RedisManager;
use App\Model\Test\TestModel;

/**
 * Class index websocket控制器
 * @package App\WsController
 * @author Billion
 * @time 2021-01-23 00:39:58
 */
class index extends AcmeWsController {

    // 默认 - 控制页面(如果连接成功，客户端的默认通讯消息会发送到该控制器！)
    public function default(){
        // TODO:
        $data = $this->requestData();
        var_dump([
            'action' => __FUNCTION__,
            'data' => $data,
        ]);
    }

    // 测试页面
    public function page(){
        $redis = RedisManager::redis();
        $master = $redis->get('master');
        var_dump([
            'master' => $master,
        ]);

        // TODO:
        $data = $this->requestData();

        var_dump([
            'action' => __FUNCTION__,
            'data' => $data,
        ]);

        $this->request()->responseSuccessMessage('I am page!');
    }

}