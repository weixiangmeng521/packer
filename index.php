<?php
declare (strict_types = 1);
namespace packer;
require __DIR__ . '/vendor/autoload.php';

use packer\engine\Writer;
use packer\server\LiveServer;


$path = __DIR__ . DIRECTORY_SEPARATOR . "web" . DIRECTORY_SEPARATOR."index.html";
$liveServer = new LiveServer($path);



