<?php

namespace SwFwLess\models;

use SwFwLess\components\database\Connector;
use SwFwLess\components\database\ModelQuery;
use Aura\SqlQuery\Common\DeleteInterface;
use Aura\SqlQuery\Common\InsertInterface;
use Aura\SqlQuery\Common\SelectInterface;
use Aura\SqlQuery\Common\UpdateInterface;
use Aura\SqlQuery\QueryInterface;

abstract class AbstractPDOModel extends AbstractModel
{
    protected static $table = '';

    protected static $connectionName = null;

    public static function connectionName(): string
    {
        return Connector::connectionName(static::$connectionName);
    }

    public static function connectionConfig()
    {
        return Connector::config(static::connectionName());
    }

    public static function tablePrefix(): string
    {
        $connectionConfig = static::connectionConfig();
        if (is_null($connectionConfig)) {
            return '';
        }

        return $connectionConfig['table_prefix'] ?? '';
    }

    public static function tableName(): string
    {
        return (static::tablePrefix()) . (static::$table);
    }

    /**
     * @return ModelQuery|QueryInterface|SelectInterface
     */
    public static function select()
    {
        return ModelQuery::select(static::$connectionName)
            ->fromWithPrefix(static::$table)->setModelClass(static::class);
    }

    /**
     * @return ModelQuery|QueryInterface|UpdateInterface
     */
    public static function update()
    {
        return ModelQuery::update(static::$connectionName)
            ->tableWithPrefix(static::$table)->setModelClass(static::class);
    }

    /**
     * @return ModelQuery|QueryInterface|InsertInterface
     */
    public static function insert()
    {
        return ModelQuery::insert(static::$connectionName)
            ->intoWithPrefix(static::$table)->setModelClass(static::class);
    }

    /**
     * @return ModelQuery|QueryInterface|DeleteInterface
     */
    public static function delete()
    {
        return ModelQuery::delete(static::$connectionName)
            ->fromWithPrefix(static::$table)->setModelClass(static::class);
    }

    /**
     * @param bool $force
     * @return bool|mixed
     * @throws \Exception
     */
    public function save($force = false)
    {
        if ($this->fireEvent('saving')->isStopped()) {
            return false;
        }

        if ($result = ($this->isNewRecord() ? $this->performInsert() : $this->performUpdate($force))) {
            $this->finishSave();
        }

        return $result;
    }

    /**
     * @return bool
     * @throws \Exception
     */
    protected function performInsert()
    {
        if ($this->fireEvent('creating')->isStopped()) {
            return false;
        }

        $insertBuilder = static::insert();
        foreach ($this->data as $attributeName => $attribute) {
            $insertBuilder->col($attributeName)->bindValue(':' . $attributeName, $this->{$attributeName});
        }
        if (static::$incrPrimaryKey) {
            $insertBuilder->setSequence(static::$primaryKey)
                ->setHasSequence(true);
        } else {
            $insertBuilder->setHasSequence(false);
        }

        $res = $insertBuilder->write() > 0;

        if (static::$incrPrimaryKey) {
            $lastInsetId = $insertBuilder->getLastInsertId();
            if ($lastInsetId) {
                $this->setPrimaryValue($lastInsetId);
            }
        }

        if ($res) {
            $this->fireEvent('created');
        }

        return $res;
    }

    /**
     * @param false $force
     * @return bool
     * @throws \Exception
     */
    protected function performUpdate($force = false)
    {
        if ($this->fireEvent('updating')->isStopped()) {
            return false;
        }

        if (!$force && !$this->isDirty()) {
            return false;
        }

        $attributes = $this->data;

        if (count($attributes) < 1) {
            return false;
        }

        $attributes = $this->toArray();
        $updateBuilder = static::update();
        $primaryKey = static::$primaryKey;
        $updateBuilder->where("`{$primaryKey}` = :primaryValue");
        $updateBuilder->bindValue(':primaryValue', $this->getPrimaryValue());
        foreach ($attributes as $attributeName => $attribute) {
            if ($attributeName == $primaryKey) {
                continue;
            }

            $updateBuilder->col($attributeName)->bindValue(':' . $attributeName, $this->{$attributeName});
        }
        $updateBuilder->setHasSequence(false);
        $updateBuilder->write();
        $this->fireEvent('updated');

        return true;
    }

    /**
     * @return bool
     */
    public function del()
    {
        if ($this->fireEvent('deleting')->isStopped()) {
            return false;
        }

        if ($this->isNewRecord()) {
            return false;
        }

        $primaryKey = static::$primaryKey;
        static::delete()->where("`{$primaryKey}` = :primaryValue")
            ->bindValue(':primaryValue', $this->getPrimaryValue())
            ->write();

        $this->fireEvent('deleted');
        return true;
    }
}
