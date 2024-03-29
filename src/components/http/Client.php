<?php

namespace SwFwLess\components\http;

use SwFwLess\components\swoole\Scheduler;
use SwFwLess\components\zipkin\HttpClient;
use SwFwLess\exceptions\HttpException;
use Swlib\Http\BufferStream;
use Swlib\Http\ContentType;
use Swlib\Http\Uri;
use Swlib\SaberGM;
use Swoole\Coroutine\Channel;

class Client
{
    const JSON_BODY = 'json';
    const FORM_BODY = 'form';
    const STRING_BODY = 'string';

    public static function get(
        $url, $swfRequest = null, $headers = [], $bodyLength = null, $withTrace = false,
        $spanName = null, $injectSpanCtx = true, $flushingTrace = false
    )
    {
        return static::multiGet(
            $url, $swfRequest, $headers, $bodyLength, $withTrace, $spanName,
            $injectSpanCtx, $flushingTrace
        );
    }

    public static function post(
        $url, $swfRequest = null, $headers = [], $body = null, $bodyType = self::JSON_BODY,
        $bodyLength = null, $withTrace = false, $spanName = null, $injectSpanCtx = true,
        $flushingTrace = false
    )
    {
        return static::multiPost(
            $url, $swfRequest, $headers, $body, $bodyType, $bodyLength, $withTrace,
            $spanName, $injectSpanCtx, $flushingTrace
        );
    }

    public static function put(
        $url, $swfRequest = null, $headers = [], $body = null, $bodyType = self::JSON_BODY,
        $bodyLength = null, $withTrace = false, $spanName = null, $injectSpanCtx = true,
        $flushingTrace = false
    )
    {
        return static::multiPut(
            $url, $swfRequest, $headers, $body, $bodyType, $bodyLength, $withTrace,
            $spanName, $injectSpanCtx, $flushingTrace
        );
    }

    public static function delete(
        $url, $swfRequest = null, $headers = [], $body = null, $bodyType = self::JSON_BODY,
        $bodyLength = null, $withTrace = false, $spanName = null, $injectSpanCtx = true,
        $flushingTrace = false
    )
    {
        return static::multiDelete(
            $url, $swfRequest, $headers, $body, $bodyType, $bodyLength, $withTrace,
            $spanName, $injectSpanCtx, $flushingTrace
        );
    }

    public static function multiGet(
        $urls, $swfRequest = null, $headers = [], $bodyLength = null, $withTrace = false,
        $spanName = null, $injectSpanCtx = true, $flushingTrace = false
    )
    {
        return static::multiRequest(
            $urls, 'GET', $swfRequest, $headers, null, self::JSON_BODY,
            $bodyLength, $withTrace, $spanName, $injectSpanCtx, $flushingTrace
        );
    }

    public static function multiPost(
        $urls, $swfRequest = null, $headers = [], $body = null, $bodyType = self::JSON_BODY,
        $bodyLength = null, $withTrace = false, $spanName = null, $injectSpanCtx = true,
        $flushingTrace = false
    )
    {
        return static::multiRequest(
            $urls, 'POST', $swfRequest, $headers, $body, $bodyType, $bodyLength, $withTrace,
            $spanName, $injectSpanCtx, $flushingTrace
        );
    }

    public static function multiPut(
        $urls, $swfRequest = null, $headers = [], $body = null, $bodyType = self::JSON_BODY,
        $bodyLength = null, $withTrace = false, $spanName = null, $injectSpanCtx = true,
        $flushingTrace = false
    )
    {
        return static::multiRequest(
            $urls, 'PUT', $swfRequest, $headers, $body, $bodyType, $bodyLength, $withTrace,
            $spanName, $injectSpanCtx, $flushingTrace
        );
    }

    public static function multiDelete(
        $urls, $swfRequest = null, $headers = [], $body = null, $bodyType = self::JSON_BODY,
        $bodyLength = null, $withTrace = false, $spanName = null, $injectSpanCtx = true,
        $flushingTrace = false
    )
    {
        return static::multiRequest(
            $urls, 'DELETE', $swfRequest, $headers, $body, $bodyType, $bodyLength, $withTrace,
            $spanName, $injectSpanCtx, $flushingTrace
        );
    }

    public static function makeRequest(
        $url, $method, $headers = [], $body = null, $bodyType = self::JSON_BODY, $bodyLength = null
    )
    {
        $request = SaberGM::psr()->withMethod($method)
            ->withUri(new Uri($url));

        if (!is_null($body)) {
            switch ($bodyType) {
                case self::JSON_BODY:
                    $headers['Content-Type'] = ContentType::JSON;
                    $body = json_encode($body);
                    break;
                case self::FORM_BODY:
                    $headers['Content-Type'] = ContentType::URLENCODE;
                    $body = http_build_query($bodyType);
                    break;
                case self::STRING_BODY:
                    $body = (string)$body;
                    break;
            }
            $bufferStream = (new BufferStream($bodyLength ?? strlen($body)));
            $bufferStream->write($body);
            $request->withBody(new BufferStream($body));
        }

        $request->withHeaders($headers);

        return $request;
    }

    public static function sendRequest(
        \Swlib\Saber\Request $request, $swfRequest, $withTrace = false, $spanName = null, $injectSpanCtx = true,
        $flushingTrace = false
    )
    {
        return (new HttpClient())->send(
            $request, $swfRequest, $spanName, $injectSpanCtx,
            $flushingTrace, $withTrace
        );
    }

    public static function sendMultiRequest($requests, $swfRequest, $traceInfoList)
    {
        $requestCount = count($requests);
        $channel = new Channel($requestCount);
        $aggResult = [];
        $exceptions = [];
        foreach ($requests as $id => $request) {
            go(
                function () use (&$aggResult, $id, $request,
                    &$exceptions, $swfRequest, $traceInfoList, $channel
                ) {
                    Scheduler::withoutPreemptive(function () use (
                        &$aggResult, $id, $request, &$exceptions, $swfRequest, $traceInfoList, $channel
                    ) {
                        try {
                            $traceInfo = $traceInfoList[$id];
                            $withTrace = $traceInfo['with_trace'];
                            $spanName = $traceInfo['span_name'];
                            $injectSpanCtx = $traceInfo['inject_span_ctx'];
                            $flushingTrace = $traceInfo['flushing_trace'];
                            $aggResult[$id] = static::sendRequest(
                                $request, $swfRequest, $withTrace, $spanName, $injectSpanCtx,
                                $flushingTrace
                            );
                        } catch (\Throwable $e) {
                            array_push($exceptions, $e);
                        }
                    });

                    $channel->push(1);
                }
            );
        }

        for ($i = 0; $i < $requestCount; ++$i) {
            $channel->pop();
        }
        $channel->close();

        return [$aggResult, $exceptions];
    }

    public static function multiRequest(
        $urls, $method, $swfRequest = null, $headers = [], $body = null, $bodyType = self::JSON_BODY,
        $bodyLength = null, $withTrace = false, $spanName = null, $injectSpanCtx = true, $flushingTrace = false
    )
    {
        $swfRequest = $swfRequest ?? \SwFwLess\components\functions\request();

        if (!is_array($urls)) {
            $urls = (array)$urls;
        }

        $requests = [];
        $traceInfoList = [];
        foreach ($urls as $url) {
            $requests[] = static::makeRequest($url, $method, $headers, $body, $bodyType, $bodyLength);
            $traceInfoList[] = [
                'with_trace' => $withTrace,
                'span_name' => $spanName,
                'inject_span_ctx' => $injectSpanCtx,
                'flushing_trace' => $flushingTrace,
            ];
        }

        list($aggResult, $exceptions) = static::sendMultiRequest(
            $requests, $swfRequest, $traceInfoList
        );

        if (count($exceptions) > 0) {
            $messages = [];
            foreach ($exceptions as $exception) {
                array_push($messages, $exception->getMessage());
            }
            throw new HttpException(implode(',', $messages));
        }

        return $aggResult;
    }
}
