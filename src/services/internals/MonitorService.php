<?php

namespace SwFwLess\services\internals;

use SwFwLess\components\database\Database;
use SwFwLess\components\http\Response;
use SwFwLess\components\swoole\counter\Counter;
use SwFwLess\components\swoole\Server;
use SwFwLess\facades\AMQPConnectionPool;
use SwFwLess\facades\HbasePool;
use SwFwLess\facades\HealthCheck;
use SwFwLess\facades\Log;
use SwFwLess\facades\DBConnectionPool;
use SwFwLess\facades\ObjectPool;
use SwFwLess\facades\RedisPool;
use SwFwLess\services\BaseService;
use Swoole\Coroutine;

class MonitorService extends BaseService
{
    public function status()
    {
        return Response::json([
            'status' => HealthCheck::status(),
        ]);
    }

    public function pool()
    {
        $swServer = Server::getInstance();

        return Response::json([
            'worker' => [
                'id' => $swServer->worker_id,
                'pid' => $swServer->worker_pid,
            ],
            'redis' => \SwFwLess\components\functions\config('redis.pool_change_event') &&
            \SwFwLess\components\functions\config('redis.report_pool_change') ?
                Counter::get('monitor:pool:redis') : RedisPool::countPool(),
            'db_conn' => (Database::poolChangeEvent() && Database::reportPoolChange()) ?
                Counter::get('monitor:pool:db_conn') : DBConnectionPool::countPool(),
            'log' => [
                'pool' => Log::countPool(),
                'record_buffer' => Log::countRecordBuffer(),
                'worker_id' => $swServer->worker_id,
                'worker_pid' => $swServer->worker_pid,
            ],
            'amqp' => \SwFwLess\components\functions\config('amqp.pool_change_event') &&
            \SwFwLess\components\functions\config('amqp.report_pool_change') ?
                Counter::get('monitor:pool:amqp') : AMQPConnectionPool::countPool(),
            'hbase' => \SwFwLess\components\functions\config('hbase.pool_change_event') &&
            \SwFwLess\components\functions\config('hbase.report_pool_change') ?
                Counter::get('monitor:pool:hbase') : HbasePool::countPool(),
            'object' => ObjectPool::stats(),
        ]);
    }

    public function swoole()
    {
        $swServer = Server::getInstance();

        return Response::json([
            'worker' => [
                'id' => $swServer->worker_id,
                'pid' => $swServer->worker_pid,
            ],
            'swoole' => $swServer->stats(),
            'coroutine' => Coroutine::stats(),
        ]);
    }

    public function cpu()
    {
        $swServer = Server::getInstance();

        return Response::json([
            'worker_id' => $swServer->worker_id,
            'worker_pid' => $swServer->worker_pid,
            'sys_load' => sys_getloadavg(),
        ]);
    }

    public function memory()
    {
        $swServer = Server::getInstance();

        return Response::json([
            'usage' => memory_get_usage(),
            'real_usage' => memory_get_usage(true),
            'peak_usage' => memory_get_peak_usage(),
            'peak_real_usage' => memory_get_peak_usage(true),
            'worker_id' => $swServer->worker_id,
            'worker_pid' => $swServer->worker_pid,
        ]);
    }
}
