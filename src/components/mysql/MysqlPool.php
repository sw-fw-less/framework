<?php

namespace SwFwLess\components\mysql;

use SwFwLess\components\Helper;
use Cake\Event\Event as CakeEvent;
use SwFwLess\components\swoole\Scheduler;

/**
 * @deprecated
 */
class MysqlPool
{
    const EVENT_MYSQL_POOL_CHANGE = 'mysql.pool.change';

    protected static $instance;

    /** @var MysqlWrapper[][] */
    private $pdoPool = [];

    protected $config = [];

    public static function clearInstance()
    {
        static::$instance = null;
    }

    public static function create($mysqlConfig = null)
    {
        if (static::$instance instanceof static) {
            return static::$instance;
        }

        if (is_array($mysqlConfig) && !empty($mysqlConfig['switch'])) {
            return static::$instance = new static($mysqlConfig);
        } else {
            return null;
        }
    }

    /**
     * Mysql constructor.
     * @param array $mysqlConfig
     */
    public function __construct($mysqlConfig)
    {
        $this->config = $mysqlConfig;

        foreach ($mysqlConfig['connections'] as $connectionName => $mysqlConnection) {
            for ($i = 0; $i < $mysqlConnection['pool_size']; ++$i) {
                if (!is_null($connection = $this->getConnect(true, $connectionName))) {
                    $this->pdoPool[$connectionName][] = $connection;
                }
            }

            if ($mysqlConfig['pool_change_event']) {
                $this->poolChange($mysqlConnection['pool_size']);
            }
        }
    }

    protected function poolChange($count)
    {
        \SwFwLess\components\functions\event(
            new CakeEvent(static::EVENT_MYSQL_POOL_CHANGE,
                null,
                ['count' => $count]
            )
        );
    }

    /**
     * @param string|null $connectionName
     * @param callable $callback
     * @return MysqlWrapper mixed
     * @throws \Throwable
     */
    public function pick($connectionName = null, $callback = null)
    {
        if (is_null($connectionName)) {
            $connectionName = $this->config['default'];
        }
        if (!isset($this->pdoPool[$connectionName])) {
            return null;
        }
        /** @var MysqlWrapper $pdo */
        $pdo = Scheduler::withoutPreemptive(function () use ($connectionName) {
            return array_pop($this->pdoPool[$connectionName]);
        });
        if (!$pdo) {
            $pdo = $this->getConnect(false, $connectionName);
        } else {
            if ($pdo->exceedIdleTimeout()) {
                $pdo->reconnect();
            }

            if ($this->config['pool_change_event']) {
                $this->poolChange(-1);
            }
        }

        if (!is_null($callback)) {
            try {
                return call_user_func($callback, $pdo);
            } catch (\Throwable $e) {
                throw $e;
            } finally {
                $this->release($pdo);
            }
        }

        return $pdo;
    }

    /**
     * @param MysqlWrapper|\PDO $pdo
     */
    public function release($pdo)
    {
        if ($pdo) {
            if ($pdo->inTransaction()) {
                try {
                    $pdo->rollBack();
                } catch (\PDOException $rollbackException) {
                    $this->handleRollbackException($pdo, $rollbackException);
                }
            }
            if ($pdo->isNeedRelease()) {
                if ($pdo->exceedMaxBigQueryTimes()) {
                    $pdo->reconnect();
                }

                $pdo->setRetry(false);

                Scheduler::withoutPreemptive(function () use ($pdo) {
                    $this->pdoPool[$pdo->getConnectionName()][] = $pdo;
                });
                if ($this->config['pool_change_event']) {
                    $this->poolChange(1);
                }
            }
        }
    }

    /**
     * @param bool $needRelease
     * @param string|null $connectionName
     * @return MysqlWrapper
     */
    public function getConnect($needRelease = true, $connectionName = null)
    {
        if (is_null($connectionName)) {
            $connectionName = $this->config['default'];
        }
        if (!isset($this->config['connections'][$connectionName])) {
            return null;
        }

        $pdo = new \PDO(
            $this->config['connections'][$connectionName]['dsn'],
            $this->config['connections'][$connectionName]['username'],
            $this->config['connections'][$connectionName]['passwd'],
            $this->config['connections'][$connectionName]['options']
        );
        return (new MysqlWrapper())->setPDO($pdo)
            ->setLastConnectedAt()
            ->setLastActivityAt()
            ->setNeedRelease($needRelease)
            ->setConnectionName($connectionName)
            ->setIdleTimeout($this->config['connections'][$connectionName]['idle_timeout'] ?? 500);
    }

    /**
     * @param MysqlWrapper $pdo
     * @param \PDOException $e
     */
    private function handleRollbackException($pdo, \PDOException $e)
    {
        if (Helper::causedByLostConnection($e)) {
            if ($pdo->isNeedRelease()) {
                $pdo->reconnect();
            }
        } else {
            throw $e;
        }
    }

    /**
     * @return int
     */
    public function countPool()
    {
        $sum = 0;
        foreach ($this->pdoPool as $connections) {
            $sum += count($connections);
        }
        return $sum;
    }
}
