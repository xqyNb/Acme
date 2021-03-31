<?php


namespace Acme\Lib\Data;


/**
 * Class ResponseData 响应对象
 * @package Acme\Lib\Data
 * @author Billion
 * @time 2021-01-23 21:48:10
 */
class ResponseData {
    const SUCCESS_CODE = 200; // 成功响应码

    private int $code;
    private string $message;
    private mixed $data;

    // 设置列表
    private array $codeMessage = [
        self::SUCCESS_CODE => 'ok',
    ];

    // 构造函数 - 私有
    private function __construct(int $code=self::SUCCESS_CODE,mixed $data){
        $this->code = $code;
        $this->message = $this->getMessageWithCode($code);
        $this->data = $data;
    }

    /**
     * 获取成功实例
     * @param mixed $data - 成功数据
     * @return ResponseData
     */
    public static function successInstance(mixed $data):ResponseData{
        return new ResponseData(self::SUCCESS_CODE,$data);
    }

    /**
     * 获取失败实例
     * @param int $code - 失败码
     * @param mixed $data - 失败的数据
     * @return ResponseData
     */
    public static function failInstance(int $code,mixed $data):ResponseData{
        return new ResponseData($code,$data);
    }


    // 通过code获取message
    public function getMessageWithCode(int $code):string{
        if(isset($this->codeMessage[$code])){
            return $this->codeMessage[$code];
        }
        return '';
    }

    /**
     * 指定成功message
     * @param string $message
     * @return $this
     */
    public function setSuccessMessage(string $message):ResponseData{
        $this->message = $message;
        return $this;
    }

    /**
     * 设置数据
     * @param mixed $data
     * @return $this
     */
    public function setData(mixed $data):ResponseData{
        $this->data = $data;
        return $this;
    }

    /**
     * 获取code
     * @return int
     */
    public function code():int{
        return $this->code;
    }

    /**
     * 获取message
     * @return string
     */
    public function message():string{
        return $this->message;
    }

    /**
     * 获取数据
     * @return array
     */
    public function data():mixed{
        return $this->data;
    }


}