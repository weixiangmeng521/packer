<?php
declare (strict_types = 1);
namespace packer\server;
/**
 *  响应格式
 *  {
 *      "code" => 1,
 *      "type" => "direct",    
 *      "msg"  => "reload",
 *  }
 * 
 *  请求格式
 *  {
 *      "code" => 1,
 *      "type" => "info",      [info|direct]
 *      "msg"  => "handshake",
 *  }
 *  
 */
class Dispatch{
    // 操作指令
    protected array $direct;
    // 上传信息
    protected array $message;
    // 屏蔽的消息
    protected array $ignoreMsg = [
        "heartbeat",
    ];
    // 是否现实打印信息
    protected bool $isShowLog = false;

    /**
     * 绑定事件
     * @param DirectInterface $dir
     * @param MessageInterface $msg
     */
    public function __construct(DirectInterface $dir, MessageInterface $msg){
        $this->direct = [
            "reload"     => $dir->reload(),

        ];
        $this->message = [
            "heartbeat"  => $msg->heartbeat(),
        ];

    }


    /**
     * 触发消息
     * @param array $msg map结构的json
     * @param int   $cid 客户端的id
     * @return string
     */
    public function trigger(object $map, int $cid):string{
        if(!in_array($map->msg, $this->ignoreMsg) && $this->isShowLog){
            $data = json_encode($map);
            echo("\033[1;37mReceive[$cid]> $data\n");
        }

        try{
            $this->validate($map);
        }catch(\Exception $e){
            echo "\033[1;31mclient[$cid] err: ". $e->getMessage() ."\n";
            echo "\033[1;31m" . var_dump($map) . "\n";
            return $this->onError($e->getMessage());
        }

        $response = "";
        // 指令适配
        if($map->type === "direct"){
            if(!isset(($this->direct)[$map->msg])){
                echo "\033[1;31mInvalid direct method $map->msg.\n";
                return $this->onError("Invalid direct method $map->msg");
            }
            $response = ($this->direct)[$map->msg];
        }
        // 消息适配
        if($map->type === "info"){
            if(!isset(($this->message)[$map->msg])){
                echo "\033[1;31mInvalid message method $map->msg.\n";
                return $this->onError("Invalid message method $map->msg");
            }
            $response = ($this->message)[$map->msg];
        }
        
        if(!in_array($map->msg, $this->ignoreMsg) && $this->isShowLog){
            echo("\033[1;37mResponse[$cid]> $response\n");
        }

        
        return $response;
    }
    


    /**
     * 检查数据
     * @param object $map
     * @throws \Exception 异常
     * @return void
     */
    private function validate(object $map){
        if(!isset($map) || empty($map)){
            throw new \Exception("Invalid request message: Cannot be emtpy.");
        }
        if(!isset($map->code) || !isset($map->type) || !isset($map->msg)){
            throw new \Exception("Invalid request JSON format.");
        }
    }


    /**
     * 当产生错误时候的响应
     * @param string $msg 错误信息
     * @return string
     */
    protected function onError(string $msg):string{
        return json_encode([
            "code" => -1,
            "type" => "error",
            "msg"  => $msg,
        ]);
    }



    






}