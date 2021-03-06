<?php

namespace SwFwLess\components\hbase;

use SwFwLess\components\Config;
use Cake\Event\Event as CakeEvent;
use Hbase\HbaseClient;
use SwFwLess\components\swoole\Scheduler;
use Thrift\Protocol\TBinaryProtocol;
use Thrift\Transport\TBufferedTransport;
use Thrift\Transport\TSocket;

class HbasePool
{
    const EVENT_HBASE_POOL_CHANGE = 'hbase.pool.change';

    private static $instance;

    /** @var HbaseWrapper[] */
    private $connectionPool = [];

    private $config;

    /**
     * @return HbasePool|null
     */
    public static function create()
    {
        if (self::$instance instanceof self) {
            return self::$instance;
        }

        if (Config::get('hbase.switch')) {
            return self::$instance = new self();
        } else {
            return null;
        }
    }

    /**
     * ConnectionPool constructor.
     */
    public function __construct()
    {
        $this->config = Config::get('hbase');

        $poolSize = $this->config['pool_size'];
        for ($i = 0; $i < $poolSize; ++$i) {
            $this->connectionPool[] = $this->getConnect();
        }

        if (Config::get('hbase.pool_change_event')) {
            \SwFwLess\components\functions\event(
                new CakeEvent(static::EVENT_HBASE_POOL_CHANGE,
                    null,
                    ['count' => $poolSize]
                )
            );
        }
    }

    /**
     * @return HbaseWrapper mixed
     */
    public function pick()
    {
        $connection = Scheduler::withoutPreemptive(function () {
            return array_pop($this->connectionPool);
        });
        if (!$connection) {
            $connection = $this->getConnect(false);
        } else {
            if (Config::get('hbase.pool_change_event')) {
                \SwFwLess\components\functions\event(
                    new CakeEvent(static::EVENT_HBASE_POOL_CHANGE,
                        null,
                        ['count' => -1]
                    )
                );
            }
        }

        $connection->getTransport()->open();
        return $connection;
    }

    /**
     * @param HbaseWrapper $connection
     */
    public function release($connection)
    {
        if ($connection) {
            if ($connection->isNeedRelease()) {
                if ($connection->getTransport()->isOpen()) {
                    $connection->getTransport()->close();
                }
                Scheduler::withoutPreemptive(function () use ($connection) {
                    $this->connectionPool[] = $connection;
                });
                if (Config::get('hbase.pool_change_event')) {
                    \SwFwLess\components\functions\event(
                        new CakeEvent(static::EVENT_HBASE_POOL_CHANGE,
                            null,
                            ['count' => 1]
                        )
                    );
                }
            }
        }
    }

    public function __destruct()
    {
        foreach ($this->connectionPool as $connection) {
            if ($connection->getTransport()->isOpen()) {
                $connection->getTransport()->close();
            }
        }
    }

    /**
     * @param bool $needRelease
     * @return HbaseWrapper
     */
    public function getConnect($needRelease = true)
    {
        require_once __DIR__ . '/thrift/Hbase.php';
        require_once __DIR__ . '/thrift/Types.php';

        $socketClass = $this->config['socket_driver'];
        /** @var TSocket $socket */
        $socket = new $socketClass($this->config['host'], $this->config['port']);
        $socket->setSendTimeout($this->config['write_timeout']);
        $socket->setRecvTimeout($this->config['read_timeout']);

        $transport = new TBufferedTransport($socket);
        $protocol = new TBinaryProtocol($transport);
        $client = new HbaseClient($protocol);

        return (new HbaseWrapper())->setClient($client)->setTransport($transport)->setNeedRelease($needRelease);
    }

    /**
     * @return int
     */
    public function countPool()
    {
        return count($this->connectionPool);
    }
}
