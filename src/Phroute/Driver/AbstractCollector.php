<?php namespace Phroute\Phroute\Driver;

use Phroute\Phroute\Route;

abstract class AbstractCollector implements CollectorInterface
{
    /**
     * @param $httpMethod
     * @param Route $routeData
     * @param $handler
     * @param $filters
     */
    public function addRoute($httpMethod, Route $routeData, $handler, $filters)
    {
        if($routeData->variables)
        {
            $this->addVariableRoute($httpMethod, $routeData, $handler, $filters);
        }
        else
        {
            $this->addStaticRoute($httpMethod, $routeData, $handler, $filters);
        }
    }

    abstract protected function addVariableRoute($httpMethod, Route $routeData, $handler, $filters);

    abstract protected function addStaticRoute($httpMethod, Route $routeData, $handler, $filters);
}