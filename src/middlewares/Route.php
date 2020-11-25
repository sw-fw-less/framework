<?php

namespace SwFwLess\middlewares;

use SwFwLess\components\http\Request;
use SwFwLess\components\http\Response;
use SwFwLess\components\swoole\Scheduler;
use SwFwLess\facades\Container;
use SwFwLess\facades\ObjectPool;
use SwFwLess\middlewares\traits\Parser;
use FastRoute\Dispatcher;
use SwFwLess\services\BaseService;
use SwFwLess\services\GrpcUnaryService;

class Route extends AbstractMiddleware
{
    static $routeCacheKeyCache = [];

    static $routeCacheKeyCacheCount = 0;

    static $cachedRouteInfo = [];

    static $cachedRouteInfoCount = 0;

    use Parser;

    private function getRequestHandler(Request $appRequest, $routeInfo)
    {
        $controllerAction = $routeInfo[1];
        $route = $controllerAction[0];
        $appRequest->setRoute($route);
        $controllerName = $controllerAction[1];
        $action = $controllerAction[2];
        $parameters = $routeInfo[2];
        $routeDiSwitch = \SwFwLess\components\di\Container::routeDiSwitch();
        /** @var AbstractMiddleware|BaseService|GrpcUnaryService $controller */
        $controller = $routeDiSwitch ? Container::make($controllerName) : new $controllerName;
        if ($controller instanceof \SwFwLess\services\BaseService) {
            $controller->setRequestAndHandlerAndParameters(
                $appRequest,
                $action,
                $parameters
            );
        } else {
            $controller->setHandlerAndParameters($action, $parameters);
        }

        //Middleware
        $middlewareNames = config('middleware.routeMiddleware');
        if (isset($controllerAction[3])) {
            $middlewareNames = array_merge($middlewareNames, $controllerAction[3]);
        }
        $firstMiddlewareConcrete = null;
        $prevMiddlewareConcrete = null;
        foreach ($middlewareNames as $i => $middlewareName) {
            list($middlewareClass, $middlewareOptions) = $this->parseMiddlewareName($middlewareName);

            /** @var \SwFwLess\middlewares\AbstractMiddleware $middlewareConcrete */
            $middlewareConcrete = ObjectPool::pick($middlewareClass);
            if (!$middlewareConcrete) {
                $middlewareConcrete = $routeDiSwitch ?
                    Container::make($middlewareClass) :
                    new $middlewareClass;
            }

            if (is_null($firstMiddlewareConcrete)) {
                $firstMiddlewareConcrete = $middlewareConcrete;
            }

            $middlewareConcrete->setParametersAndOptions(
                [$appRequest],
                $middlewareOptions
            );
            if (!is_null($prevMiddlewareConcrete)) {
                $prevMiddlewareConcrete->setNext($middlewareConcrete);
            }
            $prevMiddlewareConcrete = $middlewareConcrete;
        }
        if (!is_null($prevMiddlewareConcrete)) {
            $prevMiddlewareConcrete->setNext($controller);
        }
        if (is_null($firstMiddlewareConcrete)) {
            $firstMiddlewareConcrete = $controller;
        }

        return $firstMiddlewareConcrete;
    }

    public function handle(Request $request)
    {
        $requestUri = $request->uri();
        if (false !== $pos = strpos($requestUri, '?')) {
            $requestUri = substr($requestUri, 0, $pos);
        }
        $requestUri = rawurldecode($requestUri);

        $routeInfo = Scheduler::withoutPreemptive(function () use ($request, $requestUri) {
            $requestMethod = $request->method();
            if (isset(self::$routeCacheKeyCache[$requestMethod][$requestUri])) {
                $cacheKey = self::$routeCacheKeyCache[$requestMethod][$requestUri];
            } else {
                $cacheKey = json_encode(['method' => $requestMethod, 'uri' => $requestUri]);
                self::$routeCacheKeyCache[$requestMethod][$requestUri] = $cacheKey;
                ++self::$routeCacheKeyCacheCount;
                if (self::$routeCacheKeyCacheCount > 100) {
                    self::$routeCacheKeyCache = array_slice(
                        self::$routeCacheKeyCache, 0, 100, true
                    );
                    self::$routeCacheKeyCacheCount = 100;
                }
            }

            if (isset(self::$cachedRouteInfo[$cacheKey])) {
                $routeInfo = self::$cachedRouteInfo[$cacheKey];
            } else {
                /** @var Dispatcher $httpRouteDispatcher */
                $httpRouteDispatcher = $this->getOptions();
                self::$cachedRouteInfo[$cacheKey] = $routeInfo = $httpRouteDispatcher->dispatch(
                    $requestMethod, $requestUri
                );
                ++self::$cachedRouteInfoCount;
                if (self::$cachedRouteInfoCount > 100) {
                    self::$cachedRouteInfo = array_slice(
                        self::$cachedRouteInfo, 0, 100, true
                    );
                    self::$cachedRouteInfoCount = 100;
                }
            }
            return $routeInfo;
        });
        $routeResult = $routeInfo[0];
        switch ($routeResult) {
            case Dispatcher::NOT_FOUND:
                // ... 404 Not Found
                return Response::output('', 404);
            case Dispatcher::METHOD_NOT_ALLOWED:
                $allowedMethods = $routeInfo[1];
                // ... 405 Method Not Allowed
                return Response::output('', 405);
            case Dispatcher::FOUND:
                $this->setNext($this->getRequestHandler($request, $routeInfo));
                break;
            default:
                return Response::output('');
        }

        return $this->next();
    }
}
