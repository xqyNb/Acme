<?php


namespace Acme\AcmeInterface;

use Acme\Lib\Log\LifeLog;

/**
 * Class IRequestManager 接口: 请求管理器
 * @package Acme\AcmeInterface
 * @author Billion
 * @time 2021-01-23 00:53:23
 */

/**
 * Interface IRequestManager 接口: 请求管理器
 * @package Acme\AcmeInterface
 */
interface IRequestManager {

    // 返回fd
    public function fd():int;

    // lifeLog
    public function lifeLog():LifeLog;

}