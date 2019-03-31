<?php

namespace App\components\mysql;

use App\components\Config;
use App\components\Helper;
use Cake\Event\Event as CakeEvent;

class MysqlPool
{
    const EVENT_MYSQL_POOL_CHANGE = 'mysql.pool.change';

    private static $instance;

    /** @var MysqlWrapper[][] */
    private $pdoPool = [];

    private $config = [];

    public static function create($mysqlConfig = null)
    {
        if (self::$instance instanceof self) {
            return self::$instance;
        }

        if ($mysqlConfig['switch']) {
            return self::$instance = new self($mysqlConfig);
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
                event(
                    new CakeEvent(static::EVENT_MYSQL_POOL_CHANGE,
                        null,
                        ['count' => $mysqlConnection['pool_size']]
                    )
                );
            }
        }
    }

    /**
     * @param string $connectionName
     * @return MysqlWrapper mixed
     */
    public function pick($connectionName = null)
    {
        if (is_null($connectionName)) {
            $connectionName = $this->config['default'];
        }
        if (!isset($this->pdoPool[$connectionName])) {
            return null;
        }
        $pdo = array_pop($this->pdoPool[$connectionName]);
        if (!$pdo) {
            $pdo = $this->getConnect(false, $connectionName);
        } else {
            if ($this->config['pool_change_event']) {
                event(
                    new CakeEvent(static::EVENT_MYSQL_POOL_CHANGE,
                        null,
                        ['count' => -1]
                    )
                );
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
                    $pdo = $this->handleRollbackException($pdo, $rollbackException);
                }
            }
            if ($pdo->isNeedRelease()) {
                $this->pdoPool[$pdo->getConnectionName()][] = $pdo;
                if ($this->config['pool_change_event']) {
                    event(
                        new CakeEvent(static::EVENT_MYSQL_POOL_CHANGE,
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
        //
    }

    /**
     * @param bool $needRelease
     * @param string $connectionName
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
            ->setNeedRelease($needRelease)
            ->setConnectionName($connectionName);
    }

    /**
     * @param MysqlWrapper $pdo
     * @param \PDOException $e
     * @return MysqlWrapper
     */
    public function handleRollbackException($pdo, \PDOException $e)
    {
        if (Helper::causedByLostConnection($e)) {
            if ($pdo->isNeedRelease()) {
                $pdo = $this->getConnect(true, $pdo->getConnectionName());
            }
        } else {
            throw $e;
        }

        return $pdo;
    }

    /**
     * @return int
     */
    public function countPool()
    {
        $sum = 0;
        foreach ($this->pdoPool as $connectionName => $connections) {
            $sum += count($connections);
        }
        return $sum;
    }
}
