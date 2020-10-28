<?php
declare (strict_types = 1);
namespace packer\server;

use Workerman\Connection\AsyncTcpConnection;
use Workerman\Lib\Timer;

class Listener{
    /**
     * @var string 入口文件
     */
    private string $target;

    /**
     * @var string 项目根目录
     */
    private string $rootPath;

    /**
     * @var array 项目的路径映射
     */
    private array $mapper;


    /**
     * @var AsyncTcpConnection $con ws的连接
     */
    private AsyncTcpConnection $con;


    /**
     * @var string 最近一次文件的md5
     */
    private string $lastMD5 = "";




    /**
     * 需要监听的文件
     * @param string $dir
     */
    public function __construct(string $dir){
        $this->target = $dir;
        $this->getRootPath($dir);
        $this->lastMD5 = md5(json_encode($this->scan($this->rootPath)));
    }

    /**
     * 获取项目根目录
     * @param string $path 文件路径
     * @return void
     */
    private function getRootPath(string $path){
        $path = str_replace("/", DIRECTORY_SEPARATOR, $path);
        $path = str_replace("\\", DIRECTORY_SEPARATOR, $path);
        $arr = explode(DIRECTORY_SEPARATOR, $path);
        array_pop($arr);
        $this->rootPath = implode(DIRECTORY_SEPARATOR, $arr);
    }



    /**
     * 开始监听
     * @return void
     */
    public function run(AsyncTcpConnection $con, HttpServer $http){
        $this->con = $con;
        Timer::add(.3, function() use ($con, $http){
            $digist = md5(json_encode($this->scan($this->rootPath)));
            // echo $digist . "\n";
            if($digist !== $this->lastMD5){
                // 广播
                $data = [
                    "code" => 1,
                    "type" => "direct",
                    "msg"  => "reload",
                    "kind" => "boardcast",
                ];
                $con->send(json_encode($data));
                echo "触发更新\n";
                // 记录更改后的md5
                $this->lastMD5 = $digist;

                // TODO: mock的数据热更新
                // $name = basename($this->target);
                // $arr = include(__DIR__. DIRECTORY_SEPARATOR . ".." . DIRECTORY_SEPARATOR . ".." . DIRECTORY_SEPARATOR . "mock" . DIRECTORY_SEPARATOR . "Mock.php");
                // $http->setTplData($arr[$name]);

            }
            usleep(30000);
        });
    }





    /**
     * 获取文件的映射map
     * @return void
     */
    private function scan(string $path){
        $path = str_replace("\\", DIRECTORY_SEPARATOR, $path);
        $path = str_replace("/", DIRECTORY_SEPARATOR, $path);
        $mapper = [];
        $arr = scandir($path);
        foreach($arr as $name){
            if($name === ".." || $name === "." || $name === ".DS_Store")continue;
            $realpath = $path . DIRECTORY_SEPARATOR . $name;
            if(is_dir($realpath)){
                $mapper[$name] = [
                    "lastmodify" => filectime($realpath),
                    "type"       => "dir",
                    "realpath"   => $realpath,
                    "children"   => $this->scan($realpath),
                ];
            }else{
                $mapper[$name] = [
                    "lastmodify" => filectime($realpath),
                    "type"       => "file",
                    "realpath"   => $realpath,
                ];
            }
        }
        return $mapper;
    }



    /**
     * 打印目录
     * @return void
     */
    private function tree(){
        $map    = $this->mapper;
        function recur(array $arr, array $stack){
            if(count($arr) === 0){
                return;
            }
            // $idt = "\033[0;30m┆\033[0m   ";
            $str = implode("", $stack);

            $space = "|-";
            for($i = 0; $i < strlen($str); $i++){
                $space .= "-";
            };
            foreach($arr as $k => $v){
                echo $space . $k. "\n";
                if($v["type"] === "dir"){
                    array_push($stack, $k);
                    recur($v["children"], $stack);
                    array_pop($stack);
                }
            }
        }
        recur($map, []);
    }










}