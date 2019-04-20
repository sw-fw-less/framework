<?php

namespace SwFwLess\components\http;

use SwFwLess\components\Helper;
use SwFwLess\components\http\traits\Tracer;
use SwFwLess\components\swoole\coresource\traits\CoroutineRes;

class Request
{
    use Tracer;
    use CoroutineRes;

    /** @var \Swoole\Http\Request */
    private $swRequest;

    private $route;

    public function __construct()
    {
        static::register($this);
    }

    /**
     * @param \Swoole\Http\Request $swRequest
     * @return $this
     */
    public function setSwRequest(\Swoole\Http\Request $swRequest)
    {
        $this->swRequest = $swRequest;
        return $this;
    }

    /**
     * @return \Swoole\Http\Request
     */
    public function getSwRequest()
    {
        return $this->swRequest;
    }

    /**
     * @param mixed $route
     * @return $this
     */
    public function setRoute($route)
    {
        $this->route = $route;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getRoute()
    {
        return $this->route;
    }

    /**
     * @param $name
     * @param null $default
     * @return null
     */
    public function get($name, $default = null)
    {
        return Helper::arrGet($this->getSwRequest()->get, $name, $default);
    }

    /**
     * @param $name
     * @param null $default
     * @return null
     */
    public function post($name, $default = null)
    {
        return Helper::arrGet($this->getSwRequest()->post, $name, $default);
    }

    /**
     * @param $name
     * @return null
     */
    public function file($name)
    {
        return Helper::arrGet($this->getSwRequest()->files, $name, null);
    }

    /**
     * @param $name
     * @param null $default
     * @return null
     */
    public function param($name, $default = null)
    {
        $getParam = $this->get($name, $default);
        if (isset($getParam)) {
            return $getParam;
        }

        $postParam = $this->post($name, $default);
        if (isset($postParam)) {
            return $postParam;
        }

        $fileParam = $this->file($name);
        if (isset($fileParam)) {
            return $fileParam;
        }

        return $default;
    }

    /**
     * @return array
     */
    public function all()
    {
        return array_merge((array)$this->getSwRequest()->get, (array)$this->getSwRequest()->post, (array)$this->getSwRequest()->files);
    }

    /**
     * @param $name
     * @param null $default
     * @return null
     */
    public function header($name, $default = null)
    {
        $name = strtolower($name);
        return Helper::arrGet($this->getSwRequest()->header, $name, $default);
    }

    /**
     * @param $name
     * @return bool
     */
    public function hasHeader($name)
    {
        $name = strtolower($name);
        $headers = $this->getSwRequest()->header;
        return !is_null($headers) && array_key_exists($name, $headers);
    }

    public function realIp($prior = 'x-real-ip')
    {
        $prior = strtolower($prior);
        if ($this->hasHeader($prior)) {
            return $this->header($prior);
        } elseif ($this->hasServer($prior)) {
            return $this->server($prior);
        } elseif ($prior !== 'x-forwarded-for' && $this->hasHeader('x-forwarded-for')) {
            return trim(explode(',', $this->header('x-forwarded-for'))[0]);
        } elseif ($prior !== 'remote_addr') {
            return $this->server('remote_addr');
        } else {
            return null;
        }
    }

    /**
     * @param $name
     * @param null $default
     * @return null
     */
    public function server($name, $default = null)
    {
        $name = strtolower($name);
        return Helper::arrGet($this->getSwRequest()->server, $name, $default);
    }

    /**
     * @param $name
     * @return bool
     */
    public function hasServer($name)
    {
        $name = strtolower($name);
        $servers = $this->getSwRequest()->server;
        return !is_null($servers) && array_key_exists($name, $servers);
    }

    /**
     * @return string
     */
    public function method()
    {
        return strtoupper($this->server('request_method'));
    }

    /**
     * @return null
     */
    public function uri()
    {
        return $this->server('request_uri');
    }

    /**
     * @return null
     */
    public function queryString()
    {
        return $this->server('query_string');
    }

    /**
     * @return mixed
     */
    public function body()
    {
        return $this->getSwRequest()->rawcontent();
    }

    public function convertToPsr7()
    {
        $rawBody = $this->getSwRequest()->rawcontent();
        $contentType = $this->header('content-type');

        if (in_array($contentType, ['application/x-www-form-urlencoded', 'multipart/form-data']) && $this->method() === 'POST') {
            $parsedBody = $this->getSwRequest()->post;
        } else {
            if ($contentType === 'application/x-www-form-urlencoded') {
                parse_str((string) $rawBody, $parsedBody);
            } else {
                $parsedBody = null;
            }
        }

        return ServerRequestFactory::fromGlobals(
            $this->getSwRequest()->server ?? [],
            $this->getSwRequest()->get ?? [],
            $parsedBody ?? [],
            $this->getSwRequest()->cookie ?? [],
            $this->getSwRequest()->files ?? [],
            $this->getSwRequest()->header ?? [],
            $rawBody
        );
    }

    /**
     * @param $swRequest
     * @return Request
     */
    public static function fromSwRequest($swRequest)
    {
        return (new self())->setSwRequest($swRequest);
    }
}