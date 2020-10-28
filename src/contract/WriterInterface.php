<?php
declare (strict_types = 1);
namespace packer\contract;

interface WriterInterface{
    /**
     * 打包文件
     * @param TemplateInterface  $tpl  模版对象
     * @param array              $data 需要渲染到模版的数据
     * @return string
     */
    public function build(TemplateInterface $tpl, array $data):string;

    /**
     * 编译
     * @return void
     */
    public function compile();

}
