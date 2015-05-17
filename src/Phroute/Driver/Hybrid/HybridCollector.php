<?php namespace Phroute\Phroute\Driver\Hybrid;

use Phroute\Phroute\Driver\AbstractCollector;
use Phroute\Phroute\Exception\BadRouteException;
use Phroute\Phroute\Route;

class VariableRouteNode
{
    public $regexes = [];

    public $static = [];

    public $data;
}

class HybridCollector extends AbstractCollector
{
    /**
     * @var array
     */
    private $staticRoutes = [];

    /**
     * @var array
     */
    private $variableRouteMap;

    public function __construct()
    {
        $this->variableRouteMap = new VariableRouteNode();
    }

    /**
     * @return HybridDispatcher
     */
    public function getDispatcher()
    {
        $variableRoutes = empty($this->variableRouteMap) ? [] : $this->variableRouteMap;

        return new HybridDispatcher($this->staticRoutes, $variableRoutes);
    }

    /**
     * @param $httpMethod
     * @param $routeData
     * @param $handler
     * @param $filters
     */
    public function addStaticRoute($httpMethod, Route $routeData, $handler, $filters)
    {
        $routeStr = $routeData->regex;

        if (isset($this->staticRoutes[$routeStr][$httpMethod]))
        {
            throw new BadRouteException("Cannot register two routes matching '$routeStr' for method '$httpMethod'");
        }

//        foreach ($this->regexToRoutesMap as $regex => $routes) {
//            if (isset($routes[$httpMethod]) && preg_match('~^' . $regex . '$~', $routeStr))
//            {
//                throw new BadRouteException("Static route '$routeStr' is shadowed by previously defined variable route '$regex' for method '$httpMethod'");
//            }
//        }

        $this->staticRoutes[$routeStr][$httpMethod] = array($handler, $filters, []);
    }

    /**
     * @param $httpMethod
     * @param Route $routeData
     * @param $handler
     * @param $filters
     */
    public function addVariableRoute($httpMethod, Route $routeData, $handler, $filters)
    {
        $node = $this->variableRouteMap;

        $data = array($handler, $filters, $routeData->variables);

        foreach($routeData->pieces as $piece)
        {
            if($piece->value === '/') continue;

            if($piece->variable)
            {
                if(!isset($node->regexes[$piece->value]))
                {
                    $node->regexes[$piece->value] = new VariableRouteNode();
                }

                $newNode = $node->regexes[$piece->value];

                if($piece->optional)
                {
                    $node->data[$httpMethod] = $data;
                }
            }
            else
            {
                if(!isset($node->static[$piece->value]))
                {
                    $node->static[$piece->value] = new VariableRouteNode();
                }

                $newNode = $node->static[$piece->value];
            }

            $node = $newNode;
        }

        $node->data[$httpMethod] = $data;
    }
}