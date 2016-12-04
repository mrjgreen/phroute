<?php namespace Phroute\Phroute;
use Phroute\Phroute\Dispatch\DispatcherInterface;
use Phroute\Phroute\Dispatch\ParameterDispatcher;
use Phroute\Phroute\Exception\HttpMethodNotAllowedException;
use Phroute\Phroute\Exception\HttpRouteNotFoundException;
use Phroute\Phroute\Router\FastRoute;
use Phroute\Phroute\Router\RouterInterface;

/**
 * Class Router
 * @package Phroute\Phroute
 */
class Router {

    private $router;

    private $dispatcher;

    /**
     * @var RouteParser
     */
    private $routeParser;

    /**
     * Router constructor.
     * @param RouterInterface|null $router
     * @param DispatcherInterface|null $dispatcher
     */
    public function __construct(RouterInterface $router = null, DispatcherInterface $dispatcher = null)
    {
        $this->router = $router ?: new FastRoute();

        $this->dispatcher = $dispatcher ?: new ParameterDispatcher();

        $this->routeParser = new RouteParser();
    }

    /**
     * @param $httpMethod
     * @param $route
     * @param $handler
     * @return $this
     */
    public function addRoute($httpMethod, $route, $handler)
    {
        $routeData = $this->routeParser->parse($route);

        $this->router->addRoute($httpMethod, $routeData, $handler);

        return $this;
    }

    /**
     * @param $route
     * @param $handler
     * @return Router
     */
    public function get($route, $handler)
    {
        return $this->addRoute(Route::GET, $route, $handler);
    }

    /**
     * @param $route
     * @param $handler
     * @return Router
     */
    public function head($route, $handler)
    {
        return $this->addRoute(Route::HEAD, $route, $handler);
    }

    /**
     * @param $route
     * @param $handler
     * @return Router
     */
    public function post($route, $handler)
    {
        return $this->addRoute(Route::POST, $route, $handler);
    }

    /**
     * @param $route
     * @param $handler
     * @return Router
     */
    public function put($route, $handler)
    {
        return $this->addRoute(Route::PUT, $route, $handler);
    }

    /**
     * @param $route
     * @param $handler
     * @return Router
     */
    public function patch($route, $handler)
    {
        return $this->addRoute(Route::PATCH, $route, $handler);
    }

    /**
     * @param $route
     * @param $handler
     * @return Router
     */
    public function delete($route, $handler)
    {
        return $this->addRoute(Route::DELETE, $route, $handler);
    }

    /**
     * @param $route
     * @param $handler
     * @return Router
     */
    public function options($route, $handler)
    {
        return $this->addRoute(Route::OPTIONS, $route, $handler);
    }

    /**
     * @param $route
     * @param $handler
     * @return Router
     */
    public function any($route, $handler)
    {
        return $this->addRoute(Route::ANY, $route, $handler);
    }

    /**
     * @param $httpMethod
     * @param $route
     * @return array
     * @throws HttpRouteNotFoundException
     */
    public function dispatch($httpMethod, $route)
    {
        if(list($methodHandlerMap, $variables) = $this->router->resolveRoute(trim($route, '/')))
        {
            if($handler = $methodHandlerMap->getHandler($httpMethod))
            {
                return $this->dispatcher->dispatch($handler, $variables);
            }

            throw new HttpMethodNotAllowedException($methodHandlerMap->getAllowedMethods());
        }

        throw new HttpRouteNotFoundException();
    }
}
