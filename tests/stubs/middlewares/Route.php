<?php

use FastRoute\Dispatcher;
use SwFwLess\components\http\Request;
use SwFwLess\components\http\Response;
use SwFwLess\middlewares\AbstractMiddleware;
use SwFwLess\middlewares\traits\Parser;
use SwFwLess\services\BaseService;
use SwFwLess\services\GrpcUnaryService;

class Route extends AbstractMiddleware
{
    use Parser;

    private function getRequestHandler(Request $appRequest, $routeInfo)
    {
        $controllerAction = $routeInfo[1];
        $route = $controllerAction[0];
        $appRequest->setRoute($route);
        $controllerName = $controllerAction[1];
        $action = $controllerAction[2];
        $parameters = $routeInfo[2];
        /** @var AbstractMiddleware|BaseService|GrpcUnaryService $controller */
        $controller = new $controllerName;
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
        $middlewareNames = [];
        if (isset($controllerAction[3])) {
            $middlewareNames = array_merge($middlewareNames, $controllerAction[3]);
        }
        $firstMiddlewareConcrete = null;
        $prevMiddlewareConcrete = null;
        foreach ($middlewareNames as $i => $middlewareName) {
            list($middlewareClass, $middlewareOptions) = \SwFwLess\middlewares\Parser::parseMiddlewareName(
                $middlewareName
            );

            /** @var \SwFwLess\middlewares\AbstractMiddleware $middlewareConcrete */
            $middlewareConcrete = new $middlewareClass;

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

        $requestMethod = $request->method();
        /** @var Dispatcher $httpRouteDispatcher */
        $httpRouteDispatcher = $this->getOptions();
        $routeInfo = $httpRouteDispatcher->dispatch($requestMethod, $requestUri);

        switch ($routeInfo[0]) {
            case Dispatcher::NOT_FOUND:
                // ... 404 Not Found
                return Response::output('', 404);
            case Dispatcher::METHOD_NOT_ALLOWED:
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

    public function call()
    {
        list($handler, $parameters) = $this->getHandlerAndParameters();
        $response = call_user_func_array([$this, $handler], $parameters);

        if (is_array($response)) {
            return Response::json($response);
        }

        return $response;
    }
}
