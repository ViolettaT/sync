<?php

namespace app\Core;

use Doctrine\DBAL\Configuration;
use Doctrine\DBAL\DriverManager;

final class Connections
{
    private static $instance;
    private static $primaryConnection;
    private static $connections = [];

    public static function getInstance(): Connections
    {
        if (null === static::$instance) {
            static::$instance = new static();
        }

        return static::$instance;
    }

    private function __construct()
    {
    }

    private function __clone()
    {
    }

    private function __wakeup()
    {
    }

    public static function setPrimaryConnection(array $params)
    {
        $config = new Configuration();
        self::$primaryConnection = DriverManager::getConnection($params, $config);
    }

    /**
     * @param array $params = [
     *  'name' => 'users alias for database' (unique)
     *  'params' => array() connection params
     * ]
     * @throws \Doctrine\DBAL\DBALException
     */
    public static function addConnection(array $params)
    {
        $config = new Configuration();
        $connection = DriverManager::getConnection($params['params'], $config);

        if (empty($params['name'])) {
            $params['name'] = ucfirst($connection->getDatabasePlatform()->getName()) . ' database ' . count(self::$connections);
        }

        self::$connections[$params['name']] = $connection;
    }

    public static function getConnections()
    {
        return self::$connections;
    }

    public static function getPrimaryConnection()
    {
        return self::$primaryConnection;
    }

    public static function deleteConnection($connetionName)
    {
        unset(self::$connections[$connetionName]);
    }
}
