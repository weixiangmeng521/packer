<?php
declare (strict_types = 1);
namespace packer\contract;

interface CompressorInterface{

    /**
     * 压缩html
     * @param  string $data 脚本内容
     * @return string 
     */
    public function tinyHtml(string $data):string;


    /**
     * 压缩css
     * @param  string $data 脚本内容
     * @return string 
     */
    public function tinyCSS(string $data):string;


    /**
     * 压缩javascript
     * @param  string $data 脚本内容
     * @return string 
     */
    public function tinyJS(string $data):string;



}
