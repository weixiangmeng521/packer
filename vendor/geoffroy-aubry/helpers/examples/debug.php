<?php

require __DIR__ . '/../vendor/autoload.php';

use GAubry\Helpers\Debug;

$value = array('key' => 'xyz');
Debug::printr($value);

eval('\GAubry\Helpers\Debug::varDump($value);');

function f($a, $b) {
    Debug::varDump($a + $b);
}

f(3, 5);
