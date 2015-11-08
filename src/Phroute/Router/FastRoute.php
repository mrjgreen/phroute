<?php namespace Phroute\Phroute\Router;

use Phroute\Phroute\MethodHandlerMap;
use Phroute\Phroute\Route;
use Phroute\Phroute\RouteParser;

use Phroute\Phroute\Exception\BadRouteException;

/**
 * Class RouteCollector
 * @package Phroute\Phroute
 */
class FastRoute implements RouterInterface {

    /**
     *
     */
    const APPROX_CHUNK_SIZE = 10;

    /**
     * @var array
     */
    private $staticRoutes = [];

    /**
     * @var array
     */
    private $regexToRoutesMap = [];


    /**
     * @param $httpMethod
     * @param $route
     * @param $handler
     * @return $this
     */
    public function addRoute($httpMethod, Route $route, $handler)
    {
        $route->hasVariableParts() ?
            $this->addVariableRoute($httpMethod, $route, $handler) :
            $this->addStaticRoute($httpMethod, $route, $handler);

        return $this;
    }

    public function resolveRoute($route)
    {
        return $this->resolveStaticRoute($route) ?: $this->dispatchVariableRoute($route);
    }

    /**
     * @param $httpMethod
     * @param $routeData
     * @param $handler
     */
    private function addStaticRoute($httpMethod, Route $routeData, $handler)
    {
        $routeStr = $routeData->getRegex();

        if(!isset($this->staticRoutes[$routeStr]))
        {
            $this->staticRoutes[$routeStr] = new MethodHandlerMap($routeStr);
        }

        foreach ($this->regexToRoutesMap as $regex => $map) {
            if ($map->getHandler($httpMethod) && preg_match('~^' . $regex . '$~', $routeStr))
            {
                throw new BadRouteException("Static route '$routeStr' is shadowed by previously defined variable route '$regex' for method '$httpMethod'");
            }
        }

        $this->staticRoutes[$routeStr]->add($httpMethod, $handler, []);
    }

    /**
     * @param $httpMethod
     * @param $routeData
     * @param $handler
     */
    private function addVariableRoute($httpMethod, Route $routeData, $handler)
    {
        $regex = $routeData->getRegex();
        $variables = $routeData->getVariableParts();

        if(!isset($this->regexToRoutesMap[$regex]))
        {
            $this->regexToRoutesMap[$regex] = new MethodHandlerMap($regex);
        }

        $this->regexToRoutesMap[$regex]->add($httpMethod, $handler, $variables);
    }

    /**
     * @return array
     */
    private function generateVariableRouteData()
    {
        if(($count = count($this->regexToRoutesMap)) === 0)
        {
            return [];
        }

        $chunkSize = $this->computeChunkSize($count);
        $chunks = array_chunk($this->regexToRoutesMap, $chunkSize, true);
        return array_map([$this, 'processChunk'], $chunks);
    }

    /**
     * @param $count
     * @return float
     */
    private function computeChunkSize($count)
    {
        $numParts = max(1, round($count / self::APPROX_CHUNK_SIZE));
        return ceil($count / $numParts);
    }

    /**
     * @param $regexToRoutesMap
     * @return array
     */
    private function processChunk($regexToRoutesMap)
    {
        $routeMaps = [];
        $regexes = [];
        $numGroups = 0;
        foreach ($regexToRoutesMap as $regex => $routes) {
            $map = $routes->getMap();
            $firstRoute = reset($map);
            $numVariables = count($firstRoute[1]);
            $numGroups = max($numGroups, $numVariables);
            $regexes[] = $regex . str_repeat('()', $numGroups - $numVariables);
            $routeMaps[++$numGroups] = $routes;
        }

        $regex = '~^(?|' . implode('|', $regexes) . ')$~';

        return ['regex' => $regex, 'routeMap' => $routeMaps];
    }

    private function resolveStaticRoute($route)
    {
        if(isset($this->staticRoutes[$route]))
        {
            return [$this->staticRoutes[$route], []];
        }
    }

    private function dispatchVariableRoute($route)
    {
        foreach ($this->generateVariableRouteData() as $data)
        {
            if (preg_match($data['regex'], $route, $matches))
            {
                $count = count($matches);

                while(!isset($data['routeMap'][$count++]));

                $map = $data['routeMap'][$count - 1];

                $variables = array_slice($matches, 1);

                return [$map, $variables];
            }
        }
    }
}
