<?php

require __DIR__ . '/../vendor/autoload.php';

use GAubry\Logger\ColoredIndentedLogger;
use Psr\Log\LogLevel;

// Instantiation
$aConfig = array(
    'colors' => array(
        'debug'      => "\033[0;35m",
        'error'      => "\033[1;31m",
        'section'    => "\033[1;37m",
        'subsection' => "\033[1;33m",
        'ok'         => "\033[1;32m"
    )
);
$oLogger = new ColoredIndentedLogger($aConfig);

// Use
$oLogger->info('{C.section}Start of {title}+++', array('title' => 'new section'));
$oLogger->debug('some debug information…');
$oLogger->info('{C.subsection}Subsection+++');
$oLogger->info("Initialization…\nStep 1+++");
$oLogger->info('Result is {result}---', array('result' => '{C.ok}OK'));
$oLogger->info('Step 2+++');
$oLogger->error(new \RuntimeException('Arghhhh!'));
$oLogger->info('---------Bye!');
