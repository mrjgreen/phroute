<?php namespace Phroute\Phroute;
use Phroute\Phroute\Exception\BadRouteException;

/**
 * Class RouteCollector
 * @package Phroute\Phroute
 */
class MethodHandlerMap {

    /**
     * @var array
     */
    private $map = [];

    /**
     * @var
     */
    private $route;

    /**
     * MethodHandlerMap constructor.
     * @param $route
     */
    public function __construct($route)
    {
        $this->route = $route;
    }

    /**
     * @param $method
     * @param $handler
     * @param array $variables
     */
    public function add($method, $handler, array $variables)
    {
        if (isset($this->map[$method]))
        {
            throw new BadRouteException("Method '$method' already exists for route '$this->route'");
        }

        $this->map[$method] = [$handler, $variables];
    }

    /**
     * @param $method
     * @return mixed
     */
    public function getHandler($method)
    {
        $methods = [$method, Route::ANY];

        $method === Route::HEAD and $methods[] = Route::GET;

        foreach($methods as $m)
        {
            if(isset($this->map[$m]))
            {
                return $this->map[$m];
            }
        }
    }

    public function getMap()
    {
        return $this->map;
    }
}
