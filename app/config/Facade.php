<?php
namespace app\config;

class Facade
{
    private $syncManager;

    public function __construct(array $primary, array $params = null)
    {
        $this->syncManager = new \app\Core\DbSync($primary, $params);
    }

    public function addConnection(array $params)
    {
        $this->syncManager->addConnection($params);
    }

    public function sync()
    {
        $this->syncManager->syncBases();
    }
}