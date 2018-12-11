<?php

namespace App\models;

use App\components\ModelQuery;
use Aura\SqlQuery\QueryInterface;

abstract class AbstractModel
{
    protected static $table = '';

    private $attributes = [];

    /**
     * @return ModelQuery|QueryInterface
     */
    public static function select()
    {
        return ModelQuery::select()->from(static::$table)->setModelClass(static::class);
    }

    /**
     * @return ModelQuery|QueryInterface
     */
    public static function update()
    {
        return ModelQuery::update()->table(static::$table)->setModelClass(static::class);
    }

    /**
     * @return ModelQuery|QueryInterface
     */
    public static function insert()
    {
        return ModelQuery::insert()->into(static::$table)->setModelClass(static::class);
    }

    /**
     * @return ModelQuery|QueryInterface
     */
    public static function delete()
    {
        return ModelQuery::delete()->from(static::$table)->setModelClass(static::class);
    }

    /**
     * @param $attributes
     * @return $this
     */
    public function setAttributes($attributes)
    {
        foreach ($attributes as $name => $value) {
            $setter = 'set' . str_replace('_', '', ucwords($name));
            if (method_exists($this, $setter)) {
                call_user_func_array([$this, $setter], [$value]);
            } else {
                $this->attributes[$name] = $value;
            }
        }

        return $this;
    }

    /**
     * @return array
     */
    public function toArray()
    {
        return $this->attributes;
    }
}
