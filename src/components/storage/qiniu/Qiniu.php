<?php

namespace SwFwLess\components\storage\qiniu;

use SwFwLess\components\Config;
use League\Flysystem\Filesystem;
use Overtrue\Flysystem\Qiniu\QiniuAdapter;
use Qiniu\Http\Client;

class Qiniu
{
    private static $instance;

    private $config;

    /**
     * Qiniu constructor.
     */
    public function __construct()
    {
        $this->config = Config::get('storage');

        if (($this->config['native_coroutine']) ?? false) {
            class_alias(QiniuCoHttpClient::class, Client::class);
        }
    }

    /**
     * @return Qiniu|null
     */
    public static function create()
    {
        if (self::$instance instanceof self) {
            return self::$instance;
        }

        $storageConfig = Config::get('storage');
        if ($storageConfig['switch']) {
            if (in_array('qiniu', $storageConfig['types'])) {
                return self::$instance = new self();
            }
        }

        return null;
    }

    /**
     * @param null $bucket
     * @return Filesystem
     */
    public function prepare($bucket = null)
    {
        $qiniuConfig = $this->config['ext']['qiniu'];
        $bucket = $bucket ? : $qiniuConfig['default_bucket'];
        $bucketConfig = $qiniuConfig['buckets'][$bucket];
        $local = new QiniuAdapter(
            $bucketConfig['access_key'],
            $bucketConfig['secret_key'],
            $bucket,
            $bucketConfig['domain']
        );
        return new Filesystem($local);
    }
}
