<?php
declare (strict_types = 1);
namespace packer\server;

use Workerman\Connection\TcpConnection;
use Workerman\Protocols\Http\Request;
use Workerman\Worker;

class WsServer{

    /**
     * @var Worker websocket服务对象
     */
    private Worker $ws;

    /**
     * @var Dispatch 分发器
     */
    private Dispatch $dispatch;

    /**
     * @var int 服务端口
     */
    private int $port;

    /**
     * @var map 客户端连接的id
     */
    private array $clients;

    /**
     * 这是构造函数，大家都懂
     * @param integer $port开放的端口
     */
    public function __construct(int $port){
        $this->port = $port;
        $this->dispatch = new Dispatch(new Direct(), new Message());

        // 建立http服务
        $ws = new Worker('websocket://0.0.0.0:' . $this->port);
        $ws->count = 1;

        $ws->onWorkerStart = function($worker){
            $this->onWorkerStart($worker);
        };
        $ws->onConnect = function($connection){
            $this->onConnect($connection);
        };
        $ws->onMessage = function($connection, $data){
            $this->onMessage($connection, $data);
        };
        $ws->onClose = function($connection){
            $this->onClose($connection);
        };

        $this->clients = [];
        $this->ws = $ws;
    }



    /**
     * 设置ws对象
     * @param Worker $ws
     * @return void
     */
    public function setWs(Worker $ws){
        $this->ws = $ws;
    }

    /**
     * 获取ws对象
     * @return Worker
     */
    public function getWs():Worker{
        return $this->ws;
    }

 
    /**
     * 进程启动的回调函数
     * @param Worker $worker
     * @return void
     */
    private function onWorkerStart(Worker $worker){
        
    }



    /**
     * 连接到的回调
     * @param TcpConnection $connection
     * @return void
     */
    private function onConnect(TcpConnection $conn){
        // 记录连接
        $this->clients[$conn->id] = $conn;
        $num = 0;
        foreach($this->clients as $k => $v){
            $num++;
        }
        // echo "\033[1;33mClinets' number: " . $num."\n";
    }


    /**
     * 接收到信息
     * @param TcpConnection $connection
     * @param Request $request
     * @return void
     */
    private function onMessage(TcpConnection $conn, string $data){
        $map = json_decode($data);
        // 广播消息
        if(isset($map->kind) && $map->kind === "boardcast"){
            $msg = $this->dispatch->trigger($map, $conn->id);
            $this->boardcast($msg);
            return;
        }
        // 单点响应
        $msg = $this->dispatch->trigger($map, $conn->id);
        $conn->send($msg);
        return;
    }


    /**
     * 关闭连接的回调
     * @param TcpConnection $connection
     * @return void
     */
    private function onClose(TcpConnection $conn){
        unset(($this->clients)[$conn->id]);
    }




    /**
     * 广播消息
     * @param string $map 欲发送的消息
     * @return void
     */
    public function boardcast(string $data){
        foreach($this->clients as $client){
            $client->send(json_encode($data));
        }
    }





}