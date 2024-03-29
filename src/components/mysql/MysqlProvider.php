<?php

namespace SwFwLess\components\mysql;

use SwFwLess\components\provider\WorkerProviderContract;

/**
 * @deprecated
 */
class MysqlProvider implements WorkerProviderContract
{
    public static function bootWorker()
    {
        MysqlPool::create(\SwFwLess\components\functions\config('mysql'));
    }

    public static function shutdownWorker()
    {
        //
    }
}
