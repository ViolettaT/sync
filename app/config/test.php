<?php

namespace app\config;

$logDir = dirname(dirname(__DIR__)).DIRECTORY_SEPARATOR;

require $logDir.'/vendor/autoload.php';
require $logDir.'/autoload.php';


$primary = [
       	'dbname' => 'painting',
		'user' => 'root',
		'password' => '',
		'host' => '127.0.0.1',
		'driver' => 'pdo_mysql'
];

$params = [
    [
        'name' => 'postgresql',
        'params' => [
              'dbname' => 'painting',
              'user' => 'postgres',
              'password' => '123',
              'host' => 'localhost',
              'driver' => 'pdo_pgsql'
        ]
    ],
	[
        'name' => 'mssql',
		'params' => [
		   	'dbname' => 'painting',
			'user' => 'user',
			'password' => '123',
			'host' => 'USER-VAIO\SQLEXPRESS',
			'driver' => 'pdo_sqlsrv'
		]
        
    ]
		  
		/*[
            'name' => 'oracle',
            'params' => [
             'dbname' => 'aero',
                'user' => 'C##myuser',
                'password' => 'MyPassword',
                'host' => 'localhost',
                'port' => '1521',
                 'servicename' => 'orcl',
                 'driver' => 'oci8'
            ]
    ]*/
];

$sync = new Facade($primary, $params);
//header("Location: /app/view/result.php");

try {
    $sync->sync();
} catch (\Exception $e) {
   //var_dump($e->getMessage());
}


