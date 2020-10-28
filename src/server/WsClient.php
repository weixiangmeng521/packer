<?php
declare (strict_types = 1);
namespace packer\server;


use Workerman\Worker;
use Workerman\Connection\AsyncTcpConnection;
use Workerman\Lib\Timer;


class WsClient{
    /**
     * @var int 服务端口
     */
    private int $port;

    /**
     * @var Listener 连接的handle
     */
    private Listener $listener;

    /**
     * @var HttpServer http对象
     */
    private HttpServer $http;

    /**
     * 初始化工作进程
     * @param integer $port    启动端口
     * @param Worker $ws       websocket的handle
     * @param HttpServer $http http服务器
     * @param string $dir      监听的路径
     */
    public function __construct(int $port, string $dir, HttpServer $http){
        $this->port     = $port;
        $this->listener = new Listener($dir);
        $this->http     = $http;
        $worker = new Worker();
        $worker->count = 1;
        $worker->onWorkerStart = function($worker){
            $this->onWorkerStart($worker);
        };
    }


    /**
     * 当worker开始工作时
     * @param Worker $worker
     * @return void
     */
    private function onWorkerStart(Worker $worker){
        $client = new AsyncTcpConnection('ws://127.0.0.1:'.$this->port);
        $client->onConnect = function($con) {
            $this->onConnect($con);
        };
        $client->onMessage = function($con, $data) {
            $this->onMessage($con, $data);
        };
        $client->onError = function($con, $code, $msg){
            $this->onError($con, $code, $msg);
        };
        $client->onClose = function($con){
            $this->onClose($con);
        };
        $client->connect();
    }



    /** 
     * 当连接成功
     * @param AsyncTcpConnection $conn
     * @return void
     */
    private function onConnect(AsyncTcpConnection $con){
        // 心跳包
        Timer::add(55, function() use ($con){
            $data = [
                "code" => 1,
                "type" => "info",
                "msg"  => "heartbeat",
            ];
            $con->send(json_encode($data));
        });

        // 启动Listener
        $this->listener->run($con, $this->http);
    }

    /** 
     * 当接收到消息
     * @param AsyncTcpConnection $con
     * @param string $data
     * @return void
     */
    private function onMessage(AsyncTcpConnection $con, string $data){
        // echo "\033[1;37m> " . $data . "\n";
    }


    /** 
     * 当接error时
     * @param AsyncTcpConnection $con
     * @param int $code
     * @param string $data
     * @return void
     */
    private function onError(AsyncTcpConnection $con, int $code, string $msg){
        echo "Websocket client abort err: $msg\n";
    }


    /** 
     * 当接收到消息
     * @param AsyncTcpConnection $con
     * @param int $code
     * @param string $data
     * @return void
     */
    private function onClose(AsyncTcpConnection $con){
        echo "Websocket client's connection closed\n";
    }








}
