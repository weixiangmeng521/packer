<?php
declare (strict_types = 1);
namespace packer\server;

interface MessageInterface{
    public function heartbeat():string;
}