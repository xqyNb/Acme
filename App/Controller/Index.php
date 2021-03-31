<?php

namespace App\Controller;

use Acme\Controller\AcmeController;
use Acme\Core;
use Acme\DataBase\Pdo\PdoManager;
use Acme\DataBase\Redis\RedisManager;
use Acme\Lib\Log\Log;
use Acme\lib\Request\RequestManager;
use Acme\Lib\Util\Console;
use Acme\Model\BaseModel;
use Acme\Model\SqlBuilder;
use Acme\View\View;
use App\Model\Test\TestModel;

// index
class Index extends AcmeController {

    protected function _ini() {
        $this->request()->lifeLog()->record('index','子类初始化!');
    }

    // 首页
    public function index() {
        $urlList = [
            'https://iwtf1.caching.ovh/to/that/2020/11/23/QQ202011231852315adea69ecd54dd18.jpg',
            'https://iwtf1.caching.ovh/to/that/2020/11/23/QQ202011232117191e69f96470482d2b.jpg',
            'https://iwtf1.caching.ovh/to/that/2020/11/23/QQ20201123211852145bae213373ed34.jpg',
            'https://iwtf1.caching.ovh/to/that/2020/11/23/QQ202011232118159040b278410667d8.jpg',
        ];

        // 处理模板数据
        $urlTpl = '';
        foreach ($urlList as $url){
            $urlTpl .= '<img src="'.$url.'" width="400" height="300">';
        }

        $name = $this->getSession('name');
        // 判断有没有
        if($name){
            $this->assign('userName',$name);
        }else{
            $this->assign('userName','尚未登录!');
        }

        $modelData = (new TestModel($this->request()))->whereId(1)->findOne();
        if($modelData->success()){
//            var_dump($modelData->toArray());
        }

        Log::customeLog('首页被访问了!');

        $this->assign('lang',$this->language()->lang());
        $this->assign('urlTpl',$urlTpl);
        $this->assign('currentLanguage',$this->language()->word(7));
        $this->display();

    }



    // 登录
    public function login() {
        $this->setSession('name','大帅逼');

        $this->request()->htmlToClient('已设置登录');
    }

    // 响应json
    public function json(RequestManager $requestManager){
        $jsonArray = [
            'code' => 200,
            'msg' => '成功',
            'data' => [
                'user_name' => 'billion',
                'age' => 20,
            ],
        ];
        $requestManager->jsonToClient($jsonArray);
    }

    // model操作
    private function modelTest(){
        //        $name = '大帅逼';
        //        RedisManager::redis()->set('name',$name);
        //        // 读取设置的值
        //        $this->readName();
        //
        //        $requestManager->htmlToClient("设置成功 $name");

        // 插入一条数据
        //        $modelData = (new TestModel())->insertOne([
        //            'name' => '王二狗',
        //            'age' => 33,
        //            'money' => 3000.00,
        //        ]);
        //
        //        var_dump([
        //            'id' => $modelData->insertId(),
        //            'rowCount' => $modelData->rowCount(),
        //        ]);

        // 批量插入数据
        //        $modelData = (new TestModel())->insertMultiple('name,age,money',[
        //            ["李狗蛋",18,2000.00],
        //            ["王胜",22,1800.00],
        //            ["草不动",23,2100.00],
        //        ]);
        //
        //        var_dump([
        //            'success' => $modelData->success(),
        //            'rowCount' => $modelData->rowCount(),
        //        ]);

        // 查询一条数据
        //        $modelData = (new TestModel())
        //                ->fields('id,name,age')
        //                ->where('age',18,'>')
        //                ->findOne();
        //
        //        var_dump($modelData->toArray());

        // 连表查询
        //        $modelData = (new TestModel())->alias('T')
        //            ->innerJoin('wife_relation','WR','T.id = WR.test_id')
        //            ->leftJoin('girl','G','WR.girl_id = G.id')
        //            ->fieldsArray([
        //                'T.id AS t_id',
        //                'T.name AS name',
        //                'T.age AS age',
        //                'T.money AS money',
        //                'G.id AS g_id',
        //                'G.name AS wife_name',
        //                'G.age AS wife_age',
        //                'G.level AS wife_level',
        //            ])
        //            ->whereArray([
        //                'T.id' => ['>',1,'AND','<',20],
        //            ])
        //            ->where('G.age',18,'>')
        //            ->limit(2)
        //            ->select();

        //        $modelData = (new TestModel())->fields('money')->distinct()->select();
        //
        //        var_dump([
        //            'success' => $modelData->success(),
        //            'data' => $modelData->toArray(),
        //        ]);

        // 更新数据
        //        $modelData = (new TestModel())->whereId(11)->update(['money' => 1580,'age' => 36]);
        //        var_dump([
        //            'success' => $modelData->success(),
        //            'data' => $modelData->toArray(),
        //            'rowCount' => $modelData->rowCount(),
        //        ]);

        // 删除数据
        //        $modelData = (new TestModel())->whereId(14)->delete();
        //        var_dump([
        //            'success' => $modelData->success(),
        //            'rowCount' => $modelData->rowCount(),
        //        ]);

        // 事务
//        $testModel = new TestModel($requestManager->fd());
//        //        go(function (){
//        //            Console::out('go开始...');
//        //            sleep(2);
//        //            Console::out('go结束!');
//        //        });
//
//        $testModel2 = new TestModel($requestManager->fd());
//
//        $testModel->startTrans();
//
//        $modelData = $testModel->whereId('11')->update(['age' => 22]);
//        // 2更新money
//        $testModel2 = $testModel2->whereId('11')->update(['money' => 2000]);
//
//        // 判断是否成功
//        if($modelData->success() && $testModel2->success()){
//            Console::out('2个model更新成功!');
//            $testModel->commit();
//        }else{
//            Console::out('更新失败 - 事务回滚!');
//            $testModel->rollback();
//        }

    }

}
