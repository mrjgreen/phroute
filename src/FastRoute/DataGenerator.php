<?php namespace FastRoute;

use FastRoute\Exception\BadRouteException;

class DataGenerator {

    const APPROX_CHUNK_SIZE = 10;

    protected $staticRoutes = [];
    protected $regexToRoutesMap = [];

    public function addRoute($httpMethod, $routeData, $handler, array $filters = array())
    {
        if ($this->isStaticRoute($routeData))
        {
            $this->addStaticRoute($httpMethod, $routeData, $handler, $filters);
        } else
        {
            $this->addVariableRoute($httpMethod, $routeData, $handler, $filters);
        }
    }

    private function isStaticRoute($routeData)
    {
        return count($routeData) == 1 && is_string($routeData[0]);
    }

    private function addStaticRoute($httpMethod, $routeData, $handler, $filters)
    {
        $routeStr = $routeData[0];

        if (isset($this->staticRoutes[$routeStr][$httpMethod]))
        {
            throw new BadRouteException(sprintf(
                    'Cannot register two routes matching "%s" for method "%s"', $routeStr, $httpMethod
            ));
        }

        foreach ($this->regexToRoutesMap as $routes) {
            if (!isset($routes[$httpMethod]))
                continue;

            $route = $routes[$httpMethod];
            if ($route->matches($routeStr))
            {
                throw new BadRouteException(sprintf(
                        'Static route "%s" is shadowed by previously defined variable route "%s" for method "%s"', $routeStr, $route->regex, $httpMethod
                ));
            }
        }

        $this->staticRoutes[$routeStr][$httpMethod] = array($handler, $filters);
    }

    private function addVariableRoute($httpMethod, $routeData, $handler, $filters)
    {
        list($regex, $variables) = $this->buildRegexForRoute($routeData);

        if (isset($this->regexToRoutesMap[$regex][$httpMethod]))
        {
            throw new BadRouteException(sprintf(
                    'Cannot register two routes matching "%s" for method "%s"', $regex, $httpMethod
            ));
        }

        $this->regexToRoutesMap[$regex][$httpMethod] = new Route(
                $httpMethod, array($handler, $filters) ,$regex, $variables
        );
    }

    private function buildRegexForRoute($routeData)
    {
        $regex = [];
        $variables = [];
        foreach ($routeData as $i => $part) {
            if (is_string($part))
            {
                $regex[] = preg_quote($part, '~');
                continue;
            }

            list($varName, $regexPart, $optional) = $part;

            if (isset($variables[$varName]))
            {
                throw new BadRouteException(sprintf(
                        'Cannot use the same placeholder "%s" twice', $varName
                ));
            }

            $variables[$varName] = $varName;
            
            $match = '(' . $regexPart . ')';
            
            if($optional && isset($regex[$i - 1]))
            {
                $regex[$i - 1] = '(?:' . $regex[$i - 1] . ')?';
                
                $match = $match . '?';
            }

            $regex[] = $match;
            
        }

        return [ implode('',$regex), $variables];
    }

    public function getData()
    {
        if (empty($this->regexToRoutesMap))
        {
            return [$this->staticRoutes, []];
        }

        return [$this->staticRoutes, $this->generateVariableRouteData()];
    }

    private function generateVariableRouteData()
    {
        $chunkSize = $this->computeChunkSize(count($this->regexToRoutesMap));
        $chunks = array_chunk($this->regexToRoutesMap, $chunkSize, true);
        return array_map(array($this, 'processChunk'), $chunks);
    }

    private function computeChunkSize($count)
    {
        $numParts = max(1, round($count / self::APPROX_CHUNK_SIZE));
        return ceil($count / $numParts);
    }

    private function processChunk($regexToRoutesMap)
    {
        $routeMap = [];
        $regexes = [];
        $numGroups = 0;
        foreach ($regexToRoutesMap as $regex => $routes) {
            $numVariables = count(reset($routes)->variables);
            $numGroups = max($numGroups, $numVariables);

            $regexes[] = $regex . str_repeat('()', $numGroups - $numVariables);

            foreach ($routes as $route) {
                $routeMap[$numGroups + 1][$route->httpMethod] = [$route->handler, $route->variables];
            }

            ++$numGroups;
        }

        $regex = '~^(?|' . implode('|', $regexes) . ')$~';
        return ['regex' => $regex, 'routeMap' => $routeMap];
    }

}
