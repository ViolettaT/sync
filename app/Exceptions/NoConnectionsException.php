<?php

namespace app\Exceptions;

class NoConnectionsException extends \Exception
{
    public function __construct()
    {
        parent::__construct('There is no available connections to synchronize', 369);
    }
}
