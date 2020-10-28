<?php
declare (strict_types = 1);
namespace packer\tools;

use Sabberworm\CSS\Parser;
use Symfony\Component\Mime\MimeTypes;

class TinyCSS{


    public function __construct(){
        
    }

    
    /**
     * CSS 压缩
     * @param string $filedata
     * @return string
     */
    public static function compress(string $filedata) {
        $filedata = str_replace(array("\r\n", "\r", "\n", "\t", '  ', '    ', '    '), '', $filedata);
        $filedata = preg_replace('!/\*[^*]*\*+([^/][^*]*\*+)*/!', '', $filedata);
        $filedata = str_replace('{ ', '{', $filedata);
        $filedata = str_replace(' }', '}', $filedata);
        $filedata = str_replace('; ', ';', $filedata);
        $filedata = str_replace(', ', ',', $filedata);
        $filedata = str_replace(' {', '{', $filedata);
        $filedata = str_replace('} ', '}', $filedata);
        $filedata = str_replace(': ', ':', $filedata);
        $filedata = str_replace(' ,', ',', $filedata);
        $filedata = str_replace(' ;', ';', $filedata);	
        return $filedata;
    }
    

    /**
     * 将css里面的url转化为base64，并且嵌入css中
     * TODO: 只有个一文件inline了。
     * @param string $path      CSS文件的当前路径
     * @return string
     */
    public static function inlineImage(string $path):string{
        $parser = new Parser(file_get_contents($path));
        $oCss = $parser->parse();
        foreach($oCss->getAllValues() as $val) {
            if($val instanceof \Sabberworm\CSS\Value\URL) {
                $addr = $val->getURL()->getString();
                // 如果这里的url已经是base64了，那么不做改变
                if(self::isBase64Str($addr))continue;

                // 获取文件的位置
                $addr = str_replace("/", DIRECTORY_SEPARATOR, $addr);
                $arr = explode(DIRECTORY_SEPARATOR, $path);
                $path = implode(DIRECTORY_SEPARATOR, array_splice($arr, 0, count($arr) - 1));
                $path .= DIRECTORY_SEPARATOR . $addr;

                // 将base64写入到url中
                $base64 = self::fileToBase64($path);
                $ss = new \Sabberworm\CSS\Value\CSSString($base64);
                $val->setURL($ss);
            }
        }
        // 移除charset
        $filedata = $oCss->render();
        $filedata = str_replace('@charset "utf-8";', "", $filedata);
        return $filedata;
    }




    /**
     * CSS 压缩
     * 这里依赖 nodejs，没有nodejs自行下载
     * @param string $filedata
     * @return string
     */
    public static function tiny(string $filedata):string{
        $filedata = self::postCSSOptimize($filedata);
        $filedata = self::compress($filedata);
        return $filedata;
    }



    /**
     * 使用postcss来优化css
     * 这里是使用的预设postcss预设，根据业务需求，可在package.json中配置
     * @param string $filedata 文件数据
     * @return string
     */
    public static function postCSSOptimize(string $filedata):string{
        @mkdir(".cache", 0777);
        $cache = ".cache" . DIRECTORY_SEPARATOR . ".tmp";
        file_put_contents($cache, $filedata);
        exec("npm run css");
        $res = file_get_contents($cache);
        self::delCache(".cache");
        if(!$res)return "";
        return $res;
    }


    // 删除文件夹
    private static function delCache(string $dirname):bool{
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
                is_dir($dir) ? self::delCache($dir) : unlink($dir);
            }
        }
        closedir($handle);
        $result = rmdir($dirname) ? true : false;
        return $result;
    }



    /** 
     * 文件转base64输出
     * @param String $file 文件路径
     * @return String base64 string
     */
    private static function fileToBase64(string $file):string{
        $base64File = '';
        if(!file_exists($file)){
            throw new \Exception($file . " 不是文件啊！！！");
        }

        $mimeTypes = new MimeTypes();
        $mimeType = $mimeTypes->guessMimeType($file);

        $base64Data = base64_encode(file_get_contents($file));
        $base64File = 'data:'.$mimeType.';base64,'.$base64Data;
        return $base64File;
    }



    

    /**
     * 判断字符串是不是base64编码
     * @param string $str
     * @return bool
     */
    private static function isBase64Str(string $str):bool{
        return substr($str, 0, 5) === "data:";
    }

}
