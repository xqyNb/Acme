<?php


namespace App\Controller\Error;

use Acme\Lib\Request\RequestManager;

/**
 * Class Error
 * @package App\Error
 * @author Billion
 * @time 2021-01-03 17:12:54
 */
class Error {

    // 404页面
    public function notFound(RequestManager $requestManager){
        $requestManager->html404ToClient('错误!您要访问的页面没有找到!');
    }

}