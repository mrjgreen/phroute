<?php namespace Phroute\Phroute\Driver\Regex;

use Phroute\Phroute\Driver\AbstractCollector;
use Phroute\Phroute\Exception\BadRouteException;
use Phroute\Phroute\Route;

class RegexCollector extends AbstractCollector
{
    /**
     * @var array
     */
    protected $staticRoutes = [];
    /**
     * @var array
     */
    protected $regexToRoutesMap = [];

    /**
     * @return RegexDispatcher
     */
    public function getDispatcher()
    {
        $variableRoutes = empty($this->regexToRoutesMap) ? [] : $this->regexToRoutesMap;

        return new RegexDispatcher($this->staticRoutes, $variableRoutes);
    }

    /**
     * @param $httpMethod
     * @param $routeData
     * @param $handler
     * @param $filters
     */
    protected function addStaticRoute($httpMethod, Route $routeData, $handler, $filters)
    {
        $routeStr = $routeData->regex;

        if (isset($this->staticRoutes[$routeStr][$httpMethod]))
        {
            throw new BadRouteException("Cannot register two routes matching '$routeStr' for method '$httpMethod'");
        }

        foreach ($this->regexToRoutesMap as $regex => $routes) {
            if (isset($routes[$httpMethod]) && preg_match('~^' . $regex . '$~', $routeStr))
            {
                throw new BadRouteException("Static route '$routeStr' is shadowed by previously defined variable route '$regex' for method '$httpMethod'");
            }
        }

        $this->staticRoutes[$routeStr][$httpMethod] = array($handler, $filters, []);
    }

    /**
     * @param $httpMethod
     * @param $routeData
     * @param $handler
     * @param $filters
     * @throws BadRouteException
     */
    protected function addVariableRoute($httpMethod, Route $routeData, $handler, $filters)
    {
        if (isset($this->regexToRoutesMap[$routeData->regex][$httpMethod]))
        {
            throw new BadRouteException("Cannot register two routes matching '$routeData->regex' for method '$httpMethod'");
        }

        $this->regexToRoutesMap[$routeData->regex][$httpMethod] = [$handler, $filters, $routeData->variables];
    }
}