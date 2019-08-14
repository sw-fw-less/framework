<?php

namespace SwFwLess\components\swoole;

class Scheduler
{
    /**
     * @param $callback
     * @return mixed
     */
    public static function withoutPreemptive($callback)
    {
        \Co::disableScheduler();
        $result = call_user_func($callback);
        \Co::enableScheduler();
        return $result;
    }
}
