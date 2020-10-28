<?php
declare (strict_types = 1);
namespace packer\engine;

use packer\contract\TemplateInterface;
use DiDom\Document;
use DiDom\Element;


// 把html和依赖资源变成一个模版对象
class Template extends Driver implements TemplateInterface{
    /**
     * @var string 模版路径
    */
    private string $path = "";

    /**
     * @var array css 依赖
     */
    private $cssDep = [];

    /**
     * @var array javascript 依赖
    */   
    private $jsDep  = [];

    /**
     * @var array image 依赖
    */
    private $imgDep = [];

    /**
     * @var string html文件的内容
     */
    private $html = "";

    /**
     * @var array 模版中的变量
     */    
    private $variables = [];

    /**
     * @var Document document的分析器
     */
    private $doc;

    public function __construct(string $path){
        $this->doc = new Document($path, true);
    }

    /**
     * 设置css依赖
     * @param array $arr
     */ 
    public function setCssDep(array $arr){
        $this->cssDep = $arr;
    }

    /**
     * 获取css依赖
     * @return array
     */ 
    public function getCssDep():array{
        return $this->cssDep;
    }


    /**
     * 设置JS依赖
     * @param array $arr
     */ 
    public function setJsDep(array $arr){
        $this->jsDep = $arr;
    }

    /**
     * 获取JS依赖
     * @return array
     */ 
    public function getJsDep():array{
        return $this->jsDep;
    }


    /**
     * 设置html
     * @param array $arr
     */ 
    public function setHtml(string $html){
        $this->html = $html;
    }

    /**
     * 获取html
     * @return string
     */ 
    public function getHtml():string{
        return $this->html;
    }


    /**
     * 设置img
     * @param array $arr
     */ 
    public function setImgDep(array $arr){
        $this->imgDep = $arr;
    }

    /**
     * 获取img
     * @return array $arr
     */ 
    public function getImgDep():array{
        return $this->imgDep;
    }


    /**
     * 设置variable
     * @param array $arr
     */ 
    public function setVariables(array $arr){
        $this->variables = $arr;
    }

    /**
     * 获取variable
     * @return array $arr
     */ 
    public function getVariables():array{
        return $this->variables;
    }


    /**
     * 设置模版路径
     * @param string
     */ 
    public function setPath(string $path){
        $this->path = $path;
    }

    /**
     * 获取模版路径
     * @return string
     */ 
    public function getPath():string{
        return $this->path;
    }


    /**
     * 设置模版解析器
     * @param string
     */ 
    public function setDoc(Document $doc){
        $this->doc = $doc;
    }

    /**
     * 获取模版解析器
     * @return string
     */ 
    public function getDoc():Document{
        return $this->doc;
    }



    /**
     * 分析模版
     * @return Template
     */
    public function analyze(){
        $dir = $this->getTemplateDir();

        $doc = new Document($this->path, true);
        // 分析css
        $cssDep = $doc->find('link[href$=css]');
        foreach($cssDep as $css){
            $addr = $css->getAttribute("href", "");
            $map = [
                "key" => $addr,
                "val" => $dir.$addr, 
            ];
            array_push($this->cssDep, $map);
        }
        
        // 分析javascript
        $jsDep = $doc->find('script[src$=js]');
        foreach($jsDep as $js){
            $addr = $js->getAttribute("src", "");
            $map = [
                "key" => $addr,
                "val" => $dir.$addr, 
            ];
            array_push($this->jsDep, $map);
        }        

        // 分析img
        $imgDep = $doc->find('img');
        foreach($imgDep as $img){
            $addr = $img->getAttribute("src", "");
            $map = [
                "key" => $addr,
                "val" => $dir.$addr, 
            ];
            array_push($this->imgDep, $map);
        }
        return $this;
    }


    /**
     * 输出分析信息
     * @return void
     */
    public function printInfo(){
        $colors = [
            'debug'      => "\033[0;35m",
            'error'      => "\033[1;31m",
            'section'    => "\033[1;37m",
            'subsection' => "\033[1;33m",
            'ok'         => "\033[1;32m"
        ];
        
        $char = [
            'base_indentation'     => "\033[0;30m┆\033[0m   ",
            'indent_tag'           => '+++',
        ];

        $indent = $char['base_indentation'];

        printf("%s\n", $colors['section'] . "Template imports files analysing...");
        printf("%s\n", $indent . $colors['subsection'] . "CSS fils:");
        foreach($this->cssDep as $css){
            printf("%s\n", $indent . $indent . $colors['ok'] . $css["key"]);
        }

        printf("%s\n", $indent . $colors['subsection'] . "Javascript fils:");
        foreach($this->jsDep as $js){
            printf("%s\n", $indent . $indent . $colors['ok'] . $js["key"]);
        }

        printf("%s\n", $indent . $colors['subsection'] . "Image fils:");
        foreach($this->imgDep as $img){
            printf("%s\n", $indent . $indent . $colors['ok'] . $img["key"]);
        }
    }



    /**
     * 获取模版所在目录
     * @return string
     */
    public function getTemplateDir(){
        $path = $this->path;
        // 修复盘符
        $path = str_replace("\\", DIRECTORY_SEPARATOR, $path);
        $path = str_replace("/", DIRECTORY_SEPARATOR, $path);
        // 获取文件的位置
        $arr = explode(DIRECTORY_SEPARATOR, $path);
        $path = implode(DIRECTORY_SEPARATOR, array_splice($arr, 0, count($arr) - 1));
        $path .= DIRECTORY_SEPARATOR;
        return $path;
    }







    /**
     * 注入CSS内容
     * @param array $data css文件的内容，组成的数组
     * @return void
     */
    public function injectCss(array $arr):Template{
        $html = $this->doc->format()->html();
        $html = str_replace("</head>", "<injectCSS></injectCSS></body>", $html);

        $data = "";
        foreach($arr as $val){
            $data .= "<style>".$val."</style>";
        }

        $html = str_replace("<injectCSS></injectCSS>", $data, $html);

        $this->doc->loadHtml($html);
        $this->tpl = $html;
        return $this;
    }


    /**
     * 注入JS内容
     * @param array $data js文件的内容，组成的数组
     * @return void
     */
    public function injectJs(array $arr):Template{
        $html = $this->doc->format()->html();
        $html = str_replace("</body>", "<injectJs></injectJs></body>", $html);

        $data = "";
        foreach($arr as $val){
            $data .= "<script>".$val."</script>\n";
        }

        $html = str_replace("<injectJs></injectJs>", $data, $html);

        $this->doc->loadHtml($html);
        $this->tpl = $html;
        return $this;
    }

    


    


}

