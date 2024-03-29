<?php

namespace SwFwLess\components\es;

use SwFwLess\components\Config;
use SwFwLess\facades\Log;
use Elasticsearch\Client;
use Elasticsearch\ClientBuilder;

/**
 * Class Manager
 *
 * {@inheritdoc}
 *
 * ES连接管理
 *
 * @package Lxj\Laravel\Elasticsearch
 */
class Manager
{
    private static $instance;

    protected $config;

    public function __construct()
    {
        $this->config = Config::get('elasticsearch');
    }

    public static function create()
    {
        if (self::$instance instanceof self) {
            return self::$instance;
        }

        if (Config::get('elasticsearch.switch')) {
            return self::$instance = new self();
        } else {
            return null;
        }
    }

    /**
     * 获取ES连接
     *
     * @param  $connection_name
     * @return Client|null
     */
    public function connection($connection_name = 'default')
    {
        $clientBuilder = ClientBuilder::create();
        $clientBuilder->setHosts($this->config['connections'][$connection_name]['hosts']);
        $logger = Log::getLogger();
        if ($logger) {
            $clientBuilder->setLogger($logger);
        }
        $clientBuilder->setHandler(new GuzzleCoHandler(['timeout' => $this->config['connections'][$connection_name]['timeout']]));
        return $clientBuilder->build();
    }
}
