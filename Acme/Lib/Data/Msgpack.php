<?php


namespace Acme\Lib\Data;

use Acme\Lib\Util\Console;

/**
 * Class Msgpack 二进制数据格式
 * @package Acme\Lib\Data
 * @author Billion
 * @time 2021-01-22 22:17:24
 */
class Msgpack {

    const UNPACK_WARNNING = 'UNPACK_WARNNING';


    /**
     * 封包数据 MsgpackData
     * @param MsgpackData $msgpackData
     * @return mixed
     */
    public static function serializeData(MsgpackData $msgpackData): mixed{
        return self::serialize($msgpackData->serverResponseData());
    }

    /**
     * 解包数据 MsgpackData
     * @param mixed $msgpack
     * @return MsgpackData|null
     */
    public static function deserializeData(mixed $msgpack): ?MsgpackData{
        // 解包数据
        $data = self::deserialize($msgpack);
        // 判断是否解包成功
        if($data !== false && is_array($data)){
            // 解析为MsgpackData
            return MsgpackData::parseClientMsgpackData($data);
        }
        Console::out("数据解包失败! : $msgpack");
        var_dump($msgpack);
        return NULL;
    }


    /**
     * msgpack封包
     * @param mixed $data
     * @return mixed
     */
    public static function serialize(mixed $data):mixed{
        return msgpack_pack($data);
    }

    /**
     * msgpack解包
     * @param mixed $msgpack
     * @return mixed
     */
    public static function deserialize(mixed $msgpack):mixed{
        try {
            @trigger_error(self::UNPACK_WARNNING, E_USER_WARNING);
            $data = msgpack_unpack($msgpack); // 如果不是pack数据会抛出一个 PHP Warning
            $error = error_get_last();
            // 判断是否是
            if($error['message'] != self::UNPACK_WARNNING){
                throw new \Exception('Unpack失败!数据类型错误！');
            }
        }catch (\Exception $e){
//            $data = $msgpack; // 如果有异常 - 返回原格式数据
            $data = false; // 如果有异常 - 返回false
        }
        return $data;
    }

}