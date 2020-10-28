<?php
declare (strict_types = 1);
namespace packer\engine;

require_once __DIR__ . DIRECTORY_SEPARATOR . ".." .DIRECTORY_SEPARATOR . "tools". DIRECTORY_SEPARATOR . "TinyHtmlMinifer.php";

use packer\contract\CompressorInterface;
use packer\tools\TinyHtmlMinifier;
use packer\tools\TinyCSS;

class Compressor extends Driver implements CompressorInterface{
    

    /**
     * 压缩html
     * @param string $html html文件的内容
     * @return string
     */    
    public function tinyHtml(string $data):string{
        $opt = [
            'collapse_whitespace' => true,
        ];
        $obj = new TinyHtmlMinifier($opt);
        return $obj->minify($data);
    }

    

    /**
     * 压缩css带优化
     * @param string $data css的内容
     * @return string
     */
    public function tinyCSS(string $data):string{
        return TinyCSS::tiny($data);
    }


    /**
     * 将css里面的url转化为base64，并且嵌入css中
     * @param string $path      CSS文件的当前路径
     * @return string
     */
    public function inlineImage(string $path):string{
        return TinyCSS::inlineImage($path);
    }


    /**
     * 压缩html
     * @param string $html html文件的内容
     * @return string
     */    
    public function tinyJS(string $data):string{


        return $data;
    }








}



