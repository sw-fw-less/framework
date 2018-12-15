<?php

namespace App\components;

use App\facades\RedisPool;

/**
 * Class RedisStreamWrapper
 * @package App\components
 */
class RedisStreamWrapper
{
    private $host;
    private $mode;
    private $data;
    private $position;

    public static function register()
    {
        stream_wrapper_register('redis', __CLASS__, 1);
    }

    /**
     * @param $path
     * @param $mode
     * @param $options
     * @param $opened_path
     * @return bool
     */
    function stream_open($path, $mode, $options, &$opened_path)
    {
        $url = parse_url($path);
        $this->host = $url['host'];
        $this->mode = $mode;
        $this->position = 0;

        return true;
    }

    /**
     * @param $count
     * @return bool|string
     * @throws \Exception
     */
    function stream_read($count)
    {
        if (is_null($this->data)) {
            $result = false;
            /** @var \Redis $redis */
            $redis = RedisPool::pick();
            try {
                $result = $redis->get(RedisPool::getKey($this->host));
            } catch (\Exception $e) {
                throw $e;
            } finally {
                RedisPool::release($redis);
            }
            $this->data = $result;
        }

        $ret = substr($this->data, $this->position, $count);
        $this->position += strlen($ret);
        return $this->data !== false ? $ret : false;
    }

    /**
     * @return mixed
     */
    function stream_tell()
    {
        return $this->position;
    }

    /**
     * @return bool
     */
    function stream_eof()
    {
        return is_null($this->data) ? false : ($this->position >= strlen($this->data));
    }

    /**
     * @return array
     */
    public function stream_stat()
    {
        static $modeMap = [
            'r'  => 33060,
            'r+' => 33206,
            'w'  => 33188,
            'rb' => 33060,
        ];

        return [
            'dev'     => 0,
            'ino'     => 0,
            'mode'    => $modeMap[$this->mode],
            'nlink'   => 0,
            'uid'     => 0,
            'gid'     => 0,
            'rdev'    => 0,
            'size'    => strlen($this->data),
            'atime'   => 0,
            'mtime'   => 0,
            'ctime'   => 0,
            'blksize' => 0,
            'blocks'  => 0
        ];
    }

    /**
     * @param $data
     * @return int
     * @throws \Exception
     */
    function stream_write($data)
    {
        /** @var \Redis $redis */
        $redis = RedisPool::pick();
        try {
            $redis->set(RedisPool::getKey($this->host), $data);
        } catch (\Exception $e) {
            throw $e;
        } finally {
            RedisPool::release($redis);
        }

        $this->data = $data;

        return strlen($data);
    }
}
