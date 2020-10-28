<?php
declare (strict_types = 1);
namespace packer\server;

use Workerman\Worker;
use Workerman\Lib\Timer;

/**
 * 开发两个端口
 * http      服务为 9527
 * websocket 服务为 9528
 * 不满意可以自己换。
 */
class LiveServer{

    /**
     * 服务开放的端口
     * @var int
     */
    private $port = 9527;

    /**
     * @var Worker websocket连接对象
     */
    private Worker $ws;

    /**
     * @var HttpServer http对象
     */
    private HttpServer $http;


    private $colors = [
        'debug'      => "\033[0;35m",
        'error'      => "\033[1;31m",
        'section'    => "\033[1;37m",
        'subsection' => "\033[1;33m",
        'ok'         => "\033[1;32m"
    ];

    /**
     * @var string 监听的路径
     */
    private string $dir = "";


    /**
     * 欲监听的文件路径
     * @param string $dir
     */
    public function __construct(string $dir){
        $this->dir = $dir;
        // 启动http
        $http = new HttpServer($this->port);
        $http->listen($dir);
        $this->http = $http;
        $this->initTplData();

        // 启动websocket
        $ws = new WsServer($this->port + 1);
        // 启动ws客户端
        $this->wsClient = new WsClient($this->port + 1, $dir, $http);

        echo "\033[1;32mServer started at: http://localhost:" . $this->port . "\n";
        Worker::runAll();
    }


    /**
     * 获取tpl的数据
     * @return void
     */
    public function initTplData(){
        $name = basename($this->dir);
        $arr = include(__DIR__. DIRECTORY_SEPARATOR . ".." . DIRECTORY_SEPARATOR . ".." . DIRECTORY_SEPARATOR . "mock" . DIRECTORY_SEPARATOR . "Mock.php");
        $this->http->setTplData($arr[$name]);
    }












}


