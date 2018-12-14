<?php

namespace App\components;

class Helper
{
    /**
     * @param $arr
     * @param $key
     * @param null $default
     * @return null
     */
    public static function arrGet($arr, $key, $default = null)
    {
        return isset($arr[$key]) ? $arr[$key] : $default;
    }

    /**
     * @param $arr
     * @param $keys
     * @return null
     */
    public static function nestedArrGet($arr, $keys)
    {
        if (is_string($keys)) {
            $keys = explode('.', $keys);
        } else {
            if (!is_array($keys)) {
                return null;
            }
        }

        foreach ($keys as $key) {
            if (isset($arr[$key])) {
                $arr = $arr[$key];
            } else {
                $arr = null;
            }
        }

        return $arr;
    }

    /**
     * Determine if the given exception was caused by a lost connection.
     *
     * @param  \Exception $e
     * @return bool
     */
    public static function causedByLostConnection(\Exception $e)
    {
        $message = $e->getMessage();
        $lostConnectionMessages = [
            'server has gone away',
            'no connection to the server',
            'Lost connection',
            'is dead or not enabled',
            'Error while sending',
            'decryption failed or bad record mac',
            'server closed the connection unexpectedly',
            'SSL connection has been closed unexpectedly',
            'Error writing data to the connection',
            'Resource deadlock avoided',
            'Transaction() on null',
            'child connection forced to terminate due to client_idle_limit',
            'query_wait_timeout',
            'reset by peer',
            'Physical connection is not usable',
            'TCP Provider: Error code 0x68',
            'Name or service not known',
            'ORA-03114',
            'Packets out of order. Expected',
        ];
        foreach ($lostConnectionMessages as $lostConnectionMessage) {
            if (stripos($message, $lostConnectionMessage) !== false) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param $name
     * @param null $default
     * @return array|false|null|string
     */
    public static function env($name, $default = null)
    {
        $env = getenv($name);
        return $env !== false ? $env : $default;
    }

    /**
     * @param $name
     * @param null $default
     * @return int
     */
    public static function envInt($name, $default = null)
    {
        return intval(self::env($name, $default));
    }

    /**
     * @param $name
     * @param null $default
     * @return bool
     */
    public static function envBool($name, $default = null)
    {
        return boolval(self::env($name, $default));
    }

    /**
     * @param $name
     * @param null $default
     * @return float
     */
    public static function envDouble($name, $default = null)
    {
        return doubleval(self::env($name, $default));
    }

    /**
     * @param $string
     * @return mixed
     */
    public static function snake2Camel($string)
    {
        return str_replace(' ', '', ucwords(str_replace('_', ' ', $string)));
    }

    /**
     * @return array|mixed|null
     */
    public static function basePath()
    {
        return Config::get('base_path');
    }

    /**
     * @return string
     */
    public static function appPath()
    {
        return Config::get('base_path') . 'app/';
    }

    /**
     * @param $relativePath
     * @return string
     */
    public static function path($relativePath)
    {
        return self::basePath() . $relativePath;
    }
}
