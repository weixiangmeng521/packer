<?php
declare (strict_types = 1);
namespace packer\server;

class Message extends Dispatch implements MessageInterface{
    
    public function __construct(){
        
    }

    /**
     * 下发指令
     * @param string $msg
     * @return string
     */
    private function send(string $msg):string{
        $arr = [
            "code" => 1,
            "type" => "info",
            "msg"  => $msg,
        ];
        return json_encode($arr);
    }

    

    // 下发心跳信息
    public function heartbeat():string{
        return $this->send(__FUNCTION__);
    }



}