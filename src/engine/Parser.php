<?php
declare (strict_types = 1);
namespace packer\engine;


use packer\contract\ParserInterface;
use Symfony\Component\Mime\MimeTypes;



class Parser extends Driver implements ParserInterface{
    /**
     * @var string 资源的路径
     */
    private string $resourcePath = "";

    /**
     * @var Template 读取的模版
     */
    private Template $tpl;



    /**
     * @var array 允许的文件后缀名
    */
    private static $allowExt = [
        "html", "tpl", "htm",
        "css", "js", "ts",
        "png", "gif", "jpeg", "jpg",
    ];

    /**
     * @var Compressor 压缩器
     */
    private Compressor $tinyer;


    // initialize
    public function __construct(){
        $this->tinyer = new Compressor();
    }
    

    /**
     * 获取template
     * @return Template
     */
    public function getTpl(){
        return $this->tpl;
    }


    /**
     * 获取resource的路径 [绝对路径]
     * @return string
     */
    public function getResourcePath(){
        if(empty($this->resourcePath)){
            return __DIR__ . DIRECTORY_SEPARATOR . ".." . DIRECTORY_SEPARATOR . "resource" . DIRECTORY_SEPARATOR;
        }
        return $this->resourcePath;
    }



    /**
     * 设置resource的路径 [绝对路径]
     * @return string
     */
    public function setResourcePath(string $path){
        $this->resourcePath = $path;
    }


    /**
     * TODO: 获取文件中的varibles
     * @param string $html
     * @return array
     */
    public function getVaribles(string $html):array{
        return [];
    }





    /**
     * 模版是否存在
     * @param  string $filename 地址
     * @return boolean 
     */
    public function exists(string $filename):bool{
        // 判断ext是否合法
        $ext = pathinfo($filename)["extension"];
        if(!in_array($ext, self::$allowExt)){
            return false;
        }
        return is_file($filename);
    }


    /**
     * 读取文件
     * @param string $filename 文件路径
     * @return Parser 把文件读成template
     */
    public function reader(string $path):Parser{
        if(!file_exists($path)){
            throw new \Exception("File: ". $path ." doesnt exit.");
        }
        $html = file_get_contents($path);//将整个文件内容读入到一个字符串中
        $html = str_replace("\r\n", "", $html);

        $this->tpl = new Template($path);
        $this->tpl->setPath($path);
        $this->tpl->setHtml($html);
        $this->tpl->analyze();
        return $this;
    }




    /**
     * 是不是有效的资源
     * @param string $path
     * @return boolean
     */
    private function isAvailableSource(string $path):bool{
        if(!$this->exists($path)){
            return false;
        }
        $data = file_get_contents($path);
        $data = trim($data);
        if(empty($data)){
            return false;
        }
        return true;
    }
    

    /**
     * 是不是本地资源
     * @param string $path
     * @return boolean
     */
    private function isLocalSource(string $path):bool{

        return true;
    }




    /**
     * 删除不必要的css和js连接
     * [一些无效的连接 | 一些内容为空的连接]
     * @return Parser
     */
    public function shake(){
        $jsDep = [];
        foreach($this->tpl->getJsDep() as $js){
            if($this->isAvailableSource($js["val"])){
                array_push($jsDep, $js);
            }
        }

        $cssDep = [];
        foreach($this->tpl->getCssDep() as $css){
            if($this->isAvailableSource($css["val"])){
                array_push($cssDep, $css);
            }
        }

        $this->tpl->setJsDep($jsDep);
        $this->tpl->setCssDep($cssDep);
        return $this;
    }



    /**
     * html里面把图片嵌入到html里面
     * @return Parser
     */
    public function inlineImg():Parser{
        $dep = $this->tpl->getImgDep();
        // 检查是不是可靠路径
        foreach($dep as $img){
            if(!is_file($img["val"])){
                throw new \Exception("inline Image must shake first.");
            }
        }
        $doc = $this->tpl->getDoc();

        // 替换img
        $imgDep = $this->tpl->getImgDep();
        foreach($imgDep as $img){
            $addr = $img["key"];
            $tags = $doc->find("img[src*=$addr]");
            foreach($tags as $tag){
                $b64 = $this->fileToBase64($img["val"]);
                $tag->setAttribute("src", $b64);
            }
        }

        $html = $doc->format()->html();
        // 清空依赖
        $doc->loadHtml($html);
        $this->tpl->setImgDep([]);
        $this->tpl->setHtml($html);
        $this->tpl->setDoc($doc);
        return $this;
    }


    /** 
     * 文件转base64输出
     * @param String $file 文件路径
     * @return String base64 string
     */
    private static function fileToBase64(string $file):string{
        $base64File = '';
        if(!file_exists($file)){
            throw new \Exception($file . " desnt exsist");
        }
        $mimeTypes = new MimeTypes();
        $mimeType = $mimeTypes->guessMimeType($file);

        $base64Data = base64_encode(file_get_contents($file));
        $base64File = 'data:'.$mimeType.';base64,'.$base64Data;
        return $base64File;
    }



    /**
     * css合并加优化，注入header
     * @return Parser
     */
    public function mergeCss():Parser{   
        $doc = $this->tpl->getDoc();
        $deps = $this->tpl->getCssDep();
        $css = [];
        foreach($deps as $dep){
            // 获取数据
            printf("%s\n", "\033[1;37m> CSS inline image.");      
            $pice = $this->tinyer->inlineImage($dep["val"]);
            $pice = $this->tinyer->tinyCSS($pice);
            array_push($css, $pice);
        }    
        printf("%s\n", "\033[1;37m> CSS inject."); 
        // 注入css;
        $this->tpl->injectCss($css);
        // 删除所有link标签
        $tags = $doc->find("link[rel=stylesheet]");
        foreach($tags as $tag){
            $tag->remove();
        }
        // refresh document;
        $html = $doc->format()->html();
        $doc->loadHtml($html);
        $this->tpl->setHtml($html);
        $this->tpl->setDoc($doc);
        $this->tpl->setCssDep([]);
        return $this;
    }



    /**
     * 注入javascript到模版
     */
    public function mergeJs():Parser{
        printf("%s\n", "\033[1;37m> Compress & merge javascript.");        
        $doc = $this->tpl->getDoc();
        $deps = $this->tpl->getJsDep();
        $script = [];
        foreach($deps as $dep){
            printf("%s\n", "\033[1;37m> Javascript compressing."); 
            // 获取数据
            $js = file_get_contents($dep["val"]);
            $js = \JShrink\Minifier::minify($js, []);
            array_push($script, $js);
        }
        
        // 移除空格换行
        // $script = str_replace(" ", "", $script);
        // $script = str_replace("\n", "", $script);

        // 删除所有script标签
        $tags = $doc->find("script");
        foreach($tags as $tag){
            $tag->remove();
        }
        // 注入js;
        printf("%s\n", "\033[1;37m> Javascript inject."); 
        $this->tpl->injectJs($script);
        // refresh document;
        $html = $doc->format()->html();
        $doc->loadHtml($html);
        $this->tpl->setHtml($html);
        $this->tpl->setDoc($doc);
        $this->tpl->setJsDep([]);
        return $this;
    }



    /**
     * 压缩 html
     * @return Parser
     */
    public function tinyHtml():Parser{
        printf("%s\n", "\033[1;37m> Compress HTML.");
        $doc = $this->tpl->getDoc();
        $html = $this->tpl->getHtml();

        $html = $this->tinyer->tinyHtml($html);

        // refresh document;
        $doc->loadHtml($html);
        $this->tpl->setHtml($html);
        $this->tpl->setDoc($doc);
        return $this;


    }








}



