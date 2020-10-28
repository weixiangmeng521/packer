<?php
declare (strict_types = 1);
namespace packer\engine;

use packer\contract\TemplateInterface;

class Writer extends Driver implements TemplateInterface{
    private $entry         = ""; //入口文件
    private $dist          = ""; //输出文件
    private $distDir       = ""; //输出的文件夹
    private $fileName      = ""; //文件名
    private array $pathArr = []; //路径组成的array

    /**
     * 编译模版
     * @param string $entry 入口hmtl
     * @param string $dist  打包到
     */
    public function __construct(string $entry, string $dist = ""){
        $this->entry = $entry;
        $this->getFileName();
        if($dist === ""){
            $dstArr = $this->pathArr;
            $dstArr[count($dstArr) - 2] = "dist";
            $this->distDir = "dist";
            $this->dist = implode(DIRECTORY_SEPARATOR, $dstArr);
            return;
        }
        $dstArr = $this->pathArr;
        $this->distDir = $dstArr[count($dstArr) - 2];
        $this->dist = $dist;
    }

    /**
     * 编译
     * @param string $path 编译路径
     * @return void
     */
    public function compile(){
        $this->clearHistory();

        $parser = new \packer\engine\Parser();
        $tpl = $parser->reader($this->entry)->getTpl();
        $tpl->printInfo();
        $tpl = $parser->shake()->tinyHtml()->inlineImg()->mergeCss()->mergeJs()->getTpl();
        @mkdir($this->distDir, 0777);
        file_put_contents($this->dist, $tpl->getHtml());

        printf("%s\n", "\033[1;32m> Compile complete.");   
    }

    
    /**
     * 获取文件名字
     */
    protected function getFileName(){
        $DS = DIRECTORY_SEPARATOR;
        $entry = $this->entry;
        str_replace("\\", $DS, $entry);
        str_replace("/", $DS, $entry);
        $arr = explode($DS, $entry);
        $this->fileName = $arr[count($arr) - 1];
        $this->pathArr = $arr;
    }


    /**
     * 删除之前打包的文件
     */
    protected function clearHistory(){
        if(is_dir($this->distDir)){
            $this->delCache($this->distDir);
        }
    }


    // 删除文件夹
    protected function delCache(string $dirname):bool{
        $result = false;
        if (!is_dir($dirname)) {
            echo " $dirname is not a dir!";
            exit(0);
        }
        $handle = opendir($dirname); //打开目录
        while (($file = readdir($handle)) !== false) {
            if ($file != '.' && $file != '..') {
                //排除"."和"."
                $dir = $dirname .'/' . $file;
                is_dir($dir) ? $this->delCache($dir) : unlink($dir);
            }
        }
        closedir($handle);
        $result = rmdir($dirname) ? true : false;
        return $result;
    }


}

