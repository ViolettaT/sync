<?php

namespace app\config;

$logDir = dirname(dirname(__DIR__)).DIRECTORY_SEPARATOR;

require $logDir.'/vendor/autoload.php';
require $logDir.'/autoload.php';

session_start();

$primary = $_SESSION['primary'];
$params=$_REQUEST;

$sync = new Facade($primary, $params);
header("Location: /app/view/result.php");

try {
    $sync->sync();
} catch (\Exception $e) {
   // var_dump($e->getMessage());
}

session_destroy();

