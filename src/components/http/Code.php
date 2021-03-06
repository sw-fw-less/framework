<?php

namespace SwFwLess\components\http;

class Code
{
    const STATUS_OK = 200;
    const STATUS_CREATED = 201;

    const STATUS_MOVED_PERMANENTLY = 301;
    const STATUS_FOUND = 302;

    const STATUS_BAD_REQUEST = 400;
    const STATUS_UNAUTHORIZED = 401;
    const STATUS_FORBIDDEN = 403;
    const STATUS_NOT_FOUND = 404;
    const STATUS_METHOD_NOT_ALLOWED = 405;
    const STATUS_TOO_MANY_REQUESTS = 429;

    const STATUS_INTERNAL_SERVER_ERROR = 500;
    const STATUS_BAD_GATEWAY = 502;
    const STATUS_SERVICE_UNAVAILABLE = 503;
    const STATUS_GATEWAY_TIMEOUT = 504;

    /** @var array Map of standard HTTP status code/reason phrases */
    const CODE_PHRASES_MAPPING = [
        100 => 'Continue',
        101 => 'Switching Protocols',
        102 => 'Processing',
        self::STATUS_OK => 'OK',
        self::STATUS_CREATED => 'Created',
        202 => 'Accepted',
        203 => 'Non-Authoritative Information',
        204 => 'No Content',
        205 => 'Reset Content',
        206 => 'Partial Content',
        207 => 'Multi-status',
        208 => 'Already Reported',
        300 => 'Multiple Choices',
        self::STATUS_MOVED_PERMANENTLY => 'Moved Permanently',
        self::STATUS_FOUND => 'Found',
        303 => 'See Other',
        304 => 'Not Modified',
        305 => 'Use Proxy',
        306 => 'Switch Proxy',
        307 => 'Temporary Redirect',
        self::STATUS_BAD_REQUEST => 'Bad Request',
        self::STATUS_UNAUTHORIZED => 'Unauthorized',
        402 => 'Payment Required',
        self::STATUS_FORBIDDEN => 'Forbidden',
        self::STATUS_NOT_FOUND => 'Not Found',
        self::STATUS_METHOD_NOT_ALLOWED => 'Method Not Allowed',
        406 => 'Not Acceptable',
        407 => 'Proxy Authentication Required',
        408 => 'Request Time-out',
        409 => 'Conflict',
        410 => 'Gone',
        411 => 'Length Required',
        412 => 'Precondition Failed',
        413 => 'Request Entity Too Large',
        414 => 'Request-URI Too Large',
        415 => 'Unsupported Media Type',
        416 => 'Requested range not satisfiable',
        417 => 'Expectation Failed',
        418 => 'I\'m a teapot',
        422 => 'Unprocessable Entity',
        423 => 'Locked',
        424 => 'Failed Dependency',
        425 => 'Unordered Collection',
        426 => 'Upgrade Required',
        428 => 'Precondition Required',
        self::STATUS_TOO_MANY_REQUESTS => 'Too Many Requests',
        431 => 'Request Header Fields Too Large',
        451 => 'Unavailable For Legal Reasons',
        self::STATUS_INTERNAL_SERVER_ERROR => 'Internal Server Error',
        501 => 'Not Implemented',
        self::STATUS_BAD_GATEWAY => 'Bad Gateway',
        self::STATUS_SERVICE_UNAVAILABLE => 'Service Unavailable',
        self::STATUS_GATEWAY_TIMEOUT => 'Gateway Time-out',
        505 => 'HTTP Version not supported',
        506 => 'Variant Also Negotiates',
        507 => 'Insufficient Storage',
        508 => 'Loop Detected',
        511 => 'Network Authentication Required',
    ];

    /**
     * @param $code
     * @return mixed
     */
    public static function phrase($code)
    {
        return static::CODE_PHRASES_MAPPING[$code] ?? static::CODE_PHRASES_MAPPING[500];
    }
}
