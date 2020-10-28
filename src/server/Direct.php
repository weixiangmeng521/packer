<?php
declare (strict_types = 1);
namespace packer\server;

class Direct extends Dispatch implements DirectInterface{

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
            "type" => "direct",
            "msg"  => $msg,
        ];
        return json_encode($arr);
    }


    /**
     * 重新加载指令
     * @return string
     */
    public function reload():string{
        return $this->send(__FUNCTION__);
    }




    




}