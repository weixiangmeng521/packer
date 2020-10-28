<?php
declare (strict_types = 1);
namespace packer\contract;


interface ParserInterface{

    /**
     * 模版是否存在
     * @param  string $filename 地址
     * @return boolean 
     */
    public function exists(string $filename):bool;

    /**
     * 读取文件
     * @param string $filename 文件路径
     * @return TemplateInterface 把文件读成template
     */
    public function reader(string $filename):ParserInterface;
    


}
