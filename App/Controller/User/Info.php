<?php

namespace App\Controller\User;

use Acme\Controller\AcmeController;
use Acme\Lib\Request\RequestManager;
use Acme\Lib\Util\Console;

/**
 * Info
 */
class Info extends AcmeController {

    // 页面
    public function page(){
        $this->display('User/page');
    }

}