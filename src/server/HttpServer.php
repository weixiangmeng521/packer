<?php
declare (strict_types = 1);
namespace packer\server;

use Workerman\Connection\TcpConnection;
use Workerman\Protocols\Http\Request;
use Workerman\Protocols\Http\Response;
use Workerman\Worker;

class HttpServer{
    /**
     * @var Worker http服务对象
     */
    private Worker $http;

    /**
     * @var int 服务端口
     */
    private int $port;

    /**
     * @var string 监听的文件路径
     */
    private string $targetFile = "";

    /**
     * @var string web的根目录
     */
    private string $rootPath = "";

    /**
     * @var string 模版
     */
    private string $template = "";

    /**
     * @var string 模板引擎普通标签开始标记
     */
    private string $tpl_begin = "{{";

    /**
     * @var string 模板引擎普通标签结束标记
     */
    private string $tpl_end   = "}}";

    /**
     * @var array 给模版注入的数据
     */
    private array $tpl_data = [];

    /**
     * 启动端口
     * @param integer $port
     */
    public function __construct(int $port){
        $this->port = $port;
        // 建立http服务
        $http = new Worker('http://0.0.0.0:' . $this->port);
        $http->count = 1;
        $http->onMessage = function(TcpConnection $connection, Request $request){
            $this->onMessage($connection, $request);
        };

        $this->http = $http;
    }


    /**
     * 监听的文件路径
     * @param string $path
     * @return void
     */
    public function listen(string $path){
        $this->targetFile = $path;
        $this->getRootPath($path);
    }


    /**
     * 给模版页面注入数据
     * @return void
     */
    public function setTplData(array $data){
        $this->tpl_data = $data;
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
     * 接收到请求
     * @param TcpConnection $connection
     * @param Request $request
     * @return void
     */
    private function onMessage(TcpConnection $connection, Request $request){
        $path = $request->path();

        if ($path === '/') {
            $connection->send($this->execFile($this->targetFile));
            return;
        }

        $file = realpath($this->rootPath. $path);


        if (false === $file) {
            $connection->send(new Response(404, array(), '<h3>404 Not Found</h3>'));
            return;
        }

        // Security check! Very important!!!
        if (strpos($file, $this->rootPath) !== 0) {
            $connection->send(new Response(400));
            return;
        }
        if (\pathinfo($file, PATHINFO_EXTENSION) === 'php') {
            $connection->send($this->execFile($file));
            return;
        }
    
        $if_modified_since = $request->header('if-modified-since');
        if (!empty($if_modified_since)) {

            // Check 304.
            $info = \stat($file);
            $modified_time = $info ? \date('D, d M Y H:i:s', $info['mtime']) . ' ' . \date_default_timezone_get() : '';
            if ($modified_time === $if_modified_since) {
                $connection->send(new Response(304));
                return;
            }
        }


        $ext = pathinfo($path)['extension'];
        $allows = ["html", "php", "htm"];
        if(in_array($ext, $allows)){
            $connection->send($this->execFile($path));
            return;
        }
        $connection->send((new Response())->withFile($file));
    }



    /**
     * 执行php程序
     * @param string $file 文件路径
     * @return void
     */
    private function execFile(string $file) {
        \ob_start();
        // Try to include php file.
        try {
            // 注入client；
            if($file === $this->targetFile){
                echo $this->injectWsClient($file)->render($this->tpl_data);
            }
            
        } catch (\Exception $e) {
            echo $e->getMessage();
        }
        return \ob_get_clean();
    }

    /**
     * 设置http对象
     * @param Worker $ws
     * @return void
     */
    public function setHttp(Worker $http){
        $this->http = $http;
    }



    /**
     * 获取ws对象
     * @return Worker
     */
    public function getHttp(){
        return $this->http;
    }


    /**
     * 注入javascript的websocket客户端
     * @param string $path 文件路径
     * @return HttpServer $this
     */
    private function injectWsClient(string $path):HttpServer{
        $html = file_get_contents($path);
        if(strpos($html, "</body>") === false){
            return "<h3>Invalid HTML format.</h3>";
        }
        $data = "<script>" . $this->getJsClient() . "</script></body>";
        $this->template = str_replace("</body>", $data, $html);
        return $this;
    }


    /**
     * 渲染模版中的变量
     * @param array $map
     * @return void
     */
    private function render(array $map){
        $template = $this->template;
        if(empty($template)){
            throw new \Exception("None template select.");
        }
        // 置换标签
        $template = str_replace($this->tpl_begin, "<?php echo", $template);
        $template = str_replace($this->tpl_end, "; ?>", $template);

        extract($map, EXTR_OVERWRITE);
        try{
            eval('?>' . $template);
        }catch(\Exception $e){
            echo $e->getMessage();
        }
    }





    /**
     * 获取要注入的js脚本
     * @return string
     */
    private function getJsClient():string{
        $script = <<<EOF
        const url = "ws://localhost:9528";
        let ws;
        let hasConnect = false;

        function getwebsocket(){
            ws = new WebSocket(url);
            ws.onerror = function(){
                reconnect(url);
            }
            ws.onopen = function(){
                console.log("connected.");
            }
            ws.onmessage = function(e){
                let res = JSON.parse(e.data);
                if(typeof res === "string" && res.indexOf("reload") !== -1){
                    window.location.reload();
                }
            }
            ws.onclose = function(){
                console.log("disconnect.");
                reconnect(url);
            }
        }

        getwebsocket(url);

        function reconnect(url) {
            if (hasConnect) return;
            hasConnect = true;
            setTimeout(() => {
                console.log("reconnecting...");
                getwebsocket();
                hasConnect = false;
            }, 1000);
        }

        const heartbeat = setInterval(() => {
            let msg = {
                code: 1,
                type: "info",
                msg: "heartbeat",
            }
            ws.send(JSON.stringify(msg));
        }, 1000 * 55);
        EOF;
        return $script;
    }





}
