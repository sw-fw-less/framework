<?php

namespace SwFwLess\components\database;

use Carbon\Carbon;
use Carbon\CarbonInterface;
use SwFwLess\components\database\traits\PDOTransaction;
use SwFwLess\components\Helper;

class PDOWrapper
{
    use PDOTransaction;

    const MAX_BIG_QUERY_TIMES = 1000000;

    /** @var \PDO */
    private $pdo;
    private $needRelease = true;
    private $connectionName;
    private $retry = false;
    private $idleTimeout = 500; //seconds
    private $lastConnectedAt;
    private $lastActivityAt;

    /** @var ConnectionPool */
    protected $connectionPool;

    public $bigQueryTimes = 0;

    /**
     * @return \PDO
     */
    public function getPDO()
    {
        return $this->pdo;
    }

    /**
     * @param $pdo
     * @return $this
     */
    public function setPDO($pdo)
    {
        $this->pdo = $pdo;
        return $this;
    }

    /**
     * @return bool
     */
    public function isNeedRelease()
    {
        return $this->needRelease;
    }

    /**
     * @param bool $needRelease
     * @return $this
     */
    public function setNeedRelease($needRelease)
    {
        $this->needRelease = $needRelease;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getConnectionName()
    {
        return $this->connectionName;
    }

    /**
     * @param mixed $connectionName
     * @return $this
     */
    public function setConnectionName($connectionName)
    {
        $this->connectionName = $connectionName;
        return $this;
    }

    /**
     * @return bool
     */
    public function isRetry(): bool
    {
        return $this->retry;
    }

    /**
     * @param bool $retry
     * @return $this
     */
    public function setRetry(bool $retry)
    {
        $this->retry = $retry;
        return $this;
    }

    /**
     * @return int
     */
    public function getIdleTimeout(): int
    {
        return $this->idleTimeout;
    }

    /**
     * @param int $idleTimeout
     * @return $this
     */
    public function setIdleTimeout(int $idleTimeout)
    {
        $this->idleTimeout = $idleTimeout;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getLastConnectedAt()
    {
        return $this->lastConnectedAt;
    }

    /**
     * @param $lastConnectedAt
     * @return $this
     */
    public function setLastConnectedAt($lastConnectedAt = null)
    {
        $this->lastConnectedAt = ($lastConnectedAt ?: Carbon::now());
        return $this;
    }

    /**
     * @return CarbonInterface
     */
    public function getLastActivityAt()
    {
        return $this->lastActivityAt;
    }

    /**
     * @param null|CarbonInterface $lastActivityAt
     * @return $this
     */
    public function setLastActivityAt($lastActivityAt = null)
    {
        $this->lastActivityAt = ($lastActivityAt ?: Carbon::now());
        return $this;
    }

    /**
     * @return ConnectionPool
     */
    public function getConnectionPool(): ConnectionPool
    {
        return $this->connectionPool;
    }

    /**
     * @param ConnectionPool $connectionPool
     * @return $this
     */
    public function setConnectionPool(ConnectionPool $connectionPool)
    {
        $this->connectionPool = $connectionPool;
        return $this;
    }

    /**
     * @return bool
     */
    public function exceedIdleTimeout()
    {
        return (Carbon::now()->diffInSeconds($this->getLastActivityAt())) > ($this->getIdleTimeout());
    }

    /**
     * @param $name
     * @param $arguments
     * @return mixed
     */
    private function callPdo($name, $arguments)
    {
        $this->setLastActivityAt();
        return call_user_func_array([$this->pdo, $name], $arguments);
    }

    /**
     * @param $name
     * @param $arguments
     * @return mixed
     */
    public function __call($name, $arguments)
    {
        if (method_exists($this->pdo, $name)) {
            try {
                return $this->callPdo($name, $arguments);
            } catch (\PDOException $e) {
                return $this->handleExecuteException(
                    $e,
                    $this->isRetry(),
                    function () use ($name, $arguments) {
                        return $this->callPdo($name, $arguments);
                    }
                );
            }
        }

        return null;
    }

    /**
     * @param \PDOException $e
     * @param bool $retry
     * @param callable $callback
     * @return mixed
     */
    public function handleExecuteException(\PDOException $e, $retry, $callback)
    {
        if (!$this->inTransaction()) {
            if (Helper::causedByLostConnection($e)) {
                $this->reconnect();

                if ($retry) {
                    return call_user_func($callback);
                }
            }
        }

        throw $e;
    }

    public function reconnect()
    {
        return $this->setPDO(
            $this->getConnectionPool()->getConnect(false, $this->getConnectionName())->getPDO()
        )->setLastConnectedAt()->setLastActivityAt()->setBigQueryTimes(0);
    }

    /**
     * @return int
     */
    public function getBigQueryTimes(): int
    {
        return $this->bigQueryTimes;
    }

    /**
     * @param int $times
     * @return $this
     */
    public function incrBigQueryTimes(int $times = 1)
    {
        $this->bigQueryTimes += $times;
        return $this;
    }

    /**
     * @param int $times
     * @return $this
     */
    public function setBigQueryTimes(int $times)
    {
        $this->bigQueryTimes = $times;
        return $this;
    }

    /**
     * @return bool
     */
    public function exceedMaxBigQueryTimes()
    {
        return $this->getBigQueryTimes() > self::MAX_BIG_QUERY_TIMES;
    }
}
