<?php

function autoloader($class) {
    $root = __DIR__;
    $class = str_replace('\\', '/', $class);
    $path = $root . '/' . $class . '.php';

    if (file_exists($path)) {
        include $path;
    }
}

spl_autoload_register('autoloader');
