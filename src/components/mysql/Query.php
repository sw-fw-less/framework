<?php

namespace SwFwLess\components\mysql;

use SwFwLess\components\utils\data\structure\variable\Variable;
use SwFwLess\facades\MysqlPool;
use Aura\SqlQuery\QueryFactory;
use Aura\SqlQuery\QueryInterface;
use Cake\Event\Event;

/**
 * @deprecated
 */
class Query
{
    const EVENT_EXECUTING = 'query.executing';
    const EVENT_EXECUTED = 'query.executed';

    const QUERY_TYPE_FETCH = 0;
    const QUERY_TYPE_FETCH_ALL = 1;
    const QUERY_TYPE_WRITE = 2;

    /** @var QueryInterface|QueryFactory */
    private $auraQuery;

    private $db;

    private $connectionName;

    private $tablePrefix;

    private $needRelease = true;

    private $sequence = 'id';

    private $hasSequence = true;

    private $lastInsertId;

    private $sql;

    private $bindValues;

    private $affectedRows = 0;

    private $pdo;

    private $executeMode = self::QUERY_TYPE_FETCH;

    private $retryWhenError = false;

    /**
     * @param string $db
     * @param null|string $connectionName
     * @return QueryFactory|Query
     */
    public static function create($db = 'mysql', $connectionName = null)
    {
        return new static($db, $connectionName);
    }

    /**
     * @param string $db
     * @param string $connectionName
     * @return \Aura\SqlQuery\Common\SelectInterface|static|QueryInterface
     */
    public static function select($db = 'mysql', $connectionName = null)
    {
        return static::create($db, $connectionName)->newSelect();
    }

    /**
     * @param string $db
     * @param string $connectionName
     * @return \Aura\SqlQuery\Common\UpdateInterface|static|QueryInterface
     */
    public static function update($db = 'mysql', $connectionName = null)
    {
        return static::create($db, $connectionName)->newUpdate();
    }

    /**
     * @param string $db
     * @param string $connectionName
     * @return \Aura\SqlQuery\Common\InsertInterface|static|QueryInterface
     */
    public static function insert($db = 'mysql', $connectionName = null)
    {
        return static::create($db, $connectionName)->newInsert();
    }

    /**
     * @param string $db
     * @param string $connectionName
     * @return \Aura\SqlQuery\Common\DeleteInterface|static|QueryInterface
     */
    public static function delete($db = 'mysql', $connectionName = null)
    {
        return static::create($db, $connectionName)->newDelete();
    }

    public function __construct($db, $connectionName)
    {
        $this->db = $db;
        $this->connectionName = static::connectionName($this->db, $connectionName);
        $this->tablePrefix = static::tablePrefix($this->db, $this->connectionName);

        $this->auraQuery = new QueryFactory($db);
    }

    /**
     * @param $name
     * @param $arguments
     * @return $this|mixed|null
     */
    public function __call($name, $arguments)
    {
        if (method_exists($this->auraQuery, $name)) {
            $result = call_user_func_array([$this->auraQuery, $name], $arguments);
            if (is_object($result)) {
                $this->auraQuery = $result;
                return $this;
            } else {
                return $result;
            }
        }

        return null;
    }

    /**
     * @param MysqlWrapper|\PDO $pdo
     * @param int $mode
     * @param string|null $sql
     * @param array|null $bindValues
     * @return array|int|mixed|null
     * @throws \Exception
     */
    private function _doMysqlExecute($pdo, $mode = self::QUERY_TYPE_FETCH, $sql = null, $bindValues = null)
    {
        /** @var \PDOStatement $pdoStatement */
        $pdoStatement = $pdo->prepare($this->setSql($sql ?? ($this->auraQuery->getStatement()))->getSql());
        if ($pdoStatement) {
            $bindValues = $bindValues ?? ($this->auraQuery->getBindValues());
            $this->setBindValues($bindValues);
            foreach ($bindValues as $placeholder => $bindValue) {
                if (is_string($bindValue)) {
                    $paramType = \PDO::PARAM_STR;
                } elseif (is_integer($bindValue) || is_float($bindValue) || is_double($bindValue)) {
                    $paramType = \PDO::PARAM_INT;
                } elseif (is_null($bindValue)) {
                    $paramType = \PDO::PARAM_NULL;
                } elseif (is_bool($bindValue)) {
                    $paramType = \PDO::PARAM_BOOL;
                } else {
                    throw new \Exception('Invalid type of pdo parameter value');
                }
                $pdoStatement->bindValue(
                    is_integer($placeholder) ? $placeholder + 1 : $placeholder,
                    $bindValue,
                    $paramType
                );
            }
            /**
             * The result is always true, because the error mode of pdo should be set to \PDO::ERRMODE_EXCEPTION.
             * The PDO driver will throw an exception on failure instead of the result.
             */
            $result = $pdoStatement->execute();
            $pdo->setLastActivityAt();
            if ($result) {
                $this->setAffectedRows($pdoStatement->rowCount());
            }
            switch ($mode) {
                case static::QUERY_TYPE_FETCH:
                    $row = $result ? $pdoStatement->fetch(\PDO::FETCH_ASSOC) : [];
                    $pdo->setLastActivityAt();
                    return $row;
                case static::QUERY_TYPE_FETCH_ALL:
                    if ($result) {
                        $queryResult = $pdoStatement->fetchAll(\PDO::FETCH_ASSOC);

                        //Reset connection after big query if not in transaction
                        if (stripos($this->getSql(), 'order')) {
                            if ($pdo->incrBigQueryTimes($this->getAffectedRows())->exceedMaxBigQueryTimes()) {
                                if (!$pdo->inTransaction()) {
                                    $pdo->reconnect();
                                }
                            }
                        }

                        $pdo->setLastActivityAt();
                        return $queryResult;
                    }

                    return [];
                case static::QUERY_TYPE_WRITE:
                    if ($this->hasSequence) {
                        $this->setLastInsertId($pdo->lastInsertId($this->sequence));
                    }
                    $affectedRows = $this->getAffectedRows();
                    $pdo->setLastActivityAt();
                    return $affectedRows;
            }
        }

        return null;
    }

    /**
     * @param MysqlWrapper|null|\PDO $pdo
     * @param $mode
     * @param bool $retry
     * @param string|null $sql
     * @param array|null $bindValues
     * @return mixed
     * @throws \Exception
     */
    private function mysqlExecute(
        $pdo = null, $mode = self::QUERY_TYPE_FETCH, $retry = false, $sql = null, $bindValues = null
    )
    {
        if ($pdo) {
            $this->needRelease = false;
        }

        try {
            /** @var MysqlWrapper|\PDO $pdo $pdo */
            $pdo = $pdo ?: MysqlPool::pick($this->connectionName);

            return $this->_doMysqlExecute($pdo, $mode, $sql, $bindValues);
        } catch (\PDOException $e) {
            if ($pdo) {
                return $pdo->handleExecuteException(
                    $e,
                    $retry,
                    function () use ($pdo, $mode, $sql, $bindValues) {
                        return $this->_doMysqlExecute($pdo, $mode, $sql, $bindValues);
                    }
                );
            }

            throw $e;
        } finally {
            if ($pdo) {
                $this->releasePDO($pdo);
            }
        }
    }

    /**
     * @param MysqlWrapper $pdo
     */
    private function releasePDO($pdo)
    {
        if ($this->needRelease) {
            MysqlPool::release($pdo);
        }
    }

    /**
     * @param null $pdo
     * @param int|null $mode
     * @param bool|null $retry
     * @param $sql
     * @param array|null $bindValues
     * @return mixed
     * @throws \Exception
     */
    public function execute($pdo = null, $mode = null, $retry = null, $sql = null, $bindValues = null)
    {
        if (!$pdo) {
            if ($this->pdo) {
                $pdo = $this->pdo;
            }
        }
        if (is_null($mode)) {
            $mode = $this->executeMode;
        }
        if (is_null($retry)) {
            $retry = $this->retryWhenError;
        }
        if (is_null($sql)) {
            $sql = $this->auraQuery->getStatement();
        }
        if (is_null($bindValues)) {
            $bindValues = $this->auraQuery->getBindValues();
        }
        $method = $this->db . 'Execute';
        if (method_exists($this, $method)) {
            return $this->executeWithEvents(function () use ($method, $pdo, $mode, $retry, $sql, $bindValues) {
                return call_user_func_array([$this, $method], [$pdo, $mode, $retry, $sql, $bindValues]);
            }, $mode);
        }

        return null;
    }

    /**
     * @param $executor
     * @param $mode
     * @return mixed
     */
    protected function executeWithEvents($executor, $mode)
    {
        \SwFwLess\components\functions\event(new Event(
            static::EVENT_EXECUTING,
            null,
            [
                'db' => $this->db,
                'connection' => $this->connectionName,
                'mode' => $mode,
            ]
        ));

        $executingAt = microtime(true) * 1000;

        $result = call_user_func($executor);

        \SwFwLess\components\functions\event(new Event(
            static::EVENT_EXECUTED,
            null,
            [
                'db' => $this->db,
                'connection' => $this->connectionName,
                'mode' => $mode,
                'time' => microtime(true) * 1000 - $executingAt,
            ]
        ));

        return $result;
    }

    /**
     * @param null $pdo
     * @param null $retry
     * @param string|null $sql
     * @param array|null $bindValues
     * @return mixed
     * @throws \Exception
     */
    public function first($pdo = null, $retry = null, $sql = null, $bindValues = null)
    {
        $this->limit(1);
        return $this->execute(
            $pdo, static::QUERY_TYPE_FETCH, $retry, $sql, $bindValues
        );
    }

    /**
     * @param null $pdo
     * @param null $retry
     * @param string|null $sql
     * @param array|null $bindValues
     * @return mixed
     * @throws \Exception
     */
    public function get($pdo = null, $retry = null, $sql = null, $bindValues = null)
    {
        return $this->execute(
            $pdo, static::QUERY_TYPE_FETCH_ALL, $retry, $sql, $bindValues
        );
    }

    /**
     * @param null $pdo
     * @param null $retry
     * @param string|null $sql
     * @param array|null $bindValues
     * @return mixed
     * @throws \Exception
     */
    public function write($pdo = null, $retry = null, $sql = null, $bindValues = null)
    {
        return $this->execute(
            $pdo, static::QUERY_TYPE_WRITE, $retry, $sql, $bindValues
        );
    }

    public function whereHasInteraction($beginField, $endField, $begin = null, $end = null)
    {
        if (Variable::allNull($begin, $end)) {
            return $this;
        }

        $sql = '';

        $beginConditionSql = '';
        if (!is_null($begin)) {
            $beginConditionSql .= "{$beginField} >= :begin";
        }
        if (!is_null($end)) {
            if ($beginConditionSql) {
                $beginConditionSql .= " and {$beginField} <= :end";
            } else {
                $beginConditionSql .= "{$beginField} <= :end";
            }
        }

        $sql .= '('.$beginConditionSql.')';

        $endConditionSql = '';
        if (!is_null($begin)) {
            $endConditionSql .= "{$endField} >= :begin";
        }
        if (!is_null($end)) {
            if ($endConditionSql) {
                $endConditionSql .= " and {$endField} <= :end";
            } else {
                $endConditionSql .= "{$endField} <= :end";
            }
        }

        $sql .= ' OR (' . $endConditionSql . ')';

        if (Variable::allNotNull($begin, $end)) {
            $startEndConditionSql = "{$beginField} <= :begin AND {$endField} >= :end";
            $sql .= ' OR (' . $startEndConditionSql . ')';
        }

        $this->auraQuery->where('(' . $sql . ')');
        $this->auraQuery->bindValue(':begin', $begin);
        $this->auraQuery->bindValue(':end', $end);
        return $this;
    }

    public function bulkInsert($rows)
    {
        $this->auraQuery->addRows($rows);
        return $this;
    }

    public function bulkUpdate($rows, $conditions)
    {
        $columnValuesMapping = [];
        $columnValuesConditions = [];

        foreach ($rows as $rowNum => $row) {
            foreach ($row as $col => $value) {
                $columnValuesMapping[$col][] = $value;
                $columnValuesConditions[$col][] = $conditions[$rowNum];
            }
        }

        foreach ($columnValuesMapping as $col => $values) {
            $valueExpr = 'CASE';
            $valueBranchExpr = [];
            foreach ($values as $valueIndex => $value) {
                $valuePlaceholder = ':' . $col . '_' . ((string)$valueIndex);
                $valueBranchExpr[] = ' WHEN ' .
                    ($columnValuesConditions[$col][$valueIndex]) .
                    ' THEN ' . $valuePlaceholder;
                $this->auraQuery->bindValue($valuePlaceholder, $value);
            }
            $valueExpr = $valueExpr . implode('', $valueBranchExpr);
            $this->auraQuery->set($col, $valueExpr);
        }

        return $this;
    }

    /**
     * @param $id
     * @return $this
     */
    private function setLastInsertId($id)
    {
        $this->lastInsertId = $id;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getLastInsertId()
    {
        return $this->lastInsertId;
    }

    /**
     * @return mixed
     */
    public function getSql()
    {
        return $this->sql;
    }

    /**
     * @param mixed $sql
     * @return $this
     */
    public function setSql($sql)
    {
        $this->sql = $sql;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getBindValues()
    {
        return $this->bindValues;
    }

    /**
     * @param mixed $bindValues
     * @return $this
     */
    public function setBindValues($bindValues)
    {
        $this->bindValues = $bindValues;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getAffectedRows()
    {
        return $this->affectedRows;
    }

    /**
     * @param mixed $affectedRows
     * @return $this
     */
    public function setAffectedRows($affectedRows)
    {
        $this->affectedRows = $affectedRows;
        return $this;
    }

    /**
     * @return string
     */
    public function getSequence(): string
    {
        return $this->sequence;
    }

    /**
     * @param string $sequence
     * @return $this
     */
    public function setSequence(string $sequence)
    {
        $this->sequence = $sequence;
        return $this;
    }

    /**
     * @return bool
     */
    public function isHasSequence(): bool
    {
        return $this->hasSequence;
    }

    /**
     * @param bool $hasSequence
     * @return $this
     */
    public function setHasSequence(bool $hasSequence)
    {
        $this->hasSequence = $hasSequence;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getPdo()
    {
        return $this->pdo;
    }

    /**
     * @param $pdo
     * @return $this
     */
    public function setPdo($pdo)
    {
        $this->pdo = $pdo;
        return $this;
    }

    /**
     * @return int
     */
    public function getExecuteMode(): int
    {
        return $this->executeMode;
    }

    /**
     * @param int $executeMode
     * @return $this
     */
    public function setExecuteMode(int $executeMode)
    {
        $this->executeMode = $executeMode;
        return $this;
    }

    /**
     * @return bool
     */
    public function isRetryWhenError(): bool
    {
        return $this->retryWhenError;
    }

    /**
     * @param bool $retryWhenError
     * @return $this
     */
    public function setRetryWhenError(bool $retryWhenError)
    {
        $this->retryWhenError = $retryWhenError;
        return $this;
    }

    public function fromWithPrefix($table, $alias = null)
    {
        $tableWithPrefix = $this->tablePrefix . $table;
        $this->auraQuery->from(is_null($alias) ? $tableWithPrefix : ($tableWithPrefix . ' AS ' . $alias));
        return $this;
    }

    public function tableWithPrefix($table, $alias = null)
    {
        $tableWithPrefix = $this->tablePrefix . $table;
        $this->auraQuery->table(is_null($alias) ? $tableWithPrefix : ($tableWithPrefix . ' AS ' . $alias));
        return $this;
    }

    public function intoWithPrefix($table, $alias = null)
    {
        $tableWithPrefix = $this->tablePrefix . $table;
        $this->auraQuery->into(is_null($alias) ? $tableWithPrefix : ($tableWithPrefix . ' AS ' . $alias));
        return $this;
    }

    public function joinWithPrefix($join, $table, $alias = null, $cond = null, array $bind = array())
    {
        $tableWithPrefix = $this->tablePrefix . $table;
        $this->auraQuery->join(
            $join,
            is_null($alias) ? $tableWithPrefix : ($tableWithPrefix . ' AS ' . $alias),
            $cond,
            $bind
        );
        return $this;
    }

    public static function tablePrefix($db, $connectionName): string
    {
        return \SwFwLess\components\functions\config(
            $db . '.connections.' . $connectionName . '.table_prefix', ''
        );
    }

    public static function connectionName($db, $connectionName = null): string
    {
        if (is_null($connectionName)) {
            $connectionName = \SwFwLess\components\functions\config(
                $db . '.default', ''
            );
        }

        return $connectionName;
    }
}
