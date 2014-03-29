<?php namespace FastRoute;

use ReflectionClass;
use ReflectionMethod;

use FastRoute\Exception\BadRouteException;

class RouteCollector {
    
    const DEFAULT_CONTROLLER_ROUTE = 'index';
    
    const APPROX_CHUNK_SIZE = 10;
    
    private $routeParser;
    private $filters;
    private $staticRoutes = [];
    private $regexToRoutesMap = [];
    
    private $globalFilters = array();
    
    public function __construct(RouteParser $routeParser) {
        $this->routeParser = $routeParser;
    }

    public function addRoute($httpMethod, $route, $handler, array $filters = array()) {
        
        $routeData = $this->routeParser->parse(trim($route , '/'));
        
        $filters = array_merge_recursive($this->globalFilters, $filters);

        if ($this->isStaticRoute($routeData))
        {
            $this->addStaticRoute($httpMethod, $routeData, $handler, $filters);
        } else
        {
            $this->addVariableRoute($httpMethod, $routeData, $handler, $filters);
        }
        
        return $this;
    }
    
    private function isStaticRoute($routeData)
    {
        return !isset($routeData[1]);
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
        list($regex, $variables) = $routeData;

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
    
    public function group(array $filters, \Closure $callback)
    {
        $oldGlobal = $this->globalFilters;
        $this->globalFilters = array_merge_recursive($this->globalFilters, array_intersect_key($filters, array(Route::AFTER => 1, Route::BEFORE => 1)));
        $callback($this);
        $this->globalFilters = $oldGlobal;
    }

    public function filter($name, $handler)
    {
        $this->filters[$name] = $handler;
    }
    
    public function get($route, $handler, array $filters = array())
    {
        return $this->addRoute(Route::GET, $route, $handler, $filters);
    }
    
    public function head($route, $handler, array $filters = array())
    {
        return $this->addRoute(Route::HEAD, $route, $handler, $filters);
    }
    
    public function post($route, $handler, array $filters = array())
    {
        return $this->addRoute(Route::POST, $route, $handler, $filters);
    }
    
    public function put($route, $handler, array $filters = array())
    {
        return $this->addRoute(Route::PUT, $route, $handler, $filters);
    }
    
    public function delete($route, $handler, array $filters = array())
    {
        return $this->addRoute(Route::DELETE, $route, $handler, $filters);
    }
    
    public function options($route, $handler, array $filters = array())
    {
        return $this->addRoute(Route::OPTIONS, $route, $handler, $filters);
    }
    
    public function any($route, $handler, array $filters = array())
    {
        return $this->addRoute(Route::ANY, $route, $handler, $filters);
    }
    
    public function getFilters() 
    {
        return $this->filters;
    }
    
    public function controller($route, $classname)
    {
        $reflection = new ReflectionClass($classname);

        $validMethods = $this->getValidMethods();
        
        foreach($reflection->getMethods(ReflectionMethod::IS_PUBLIC) as $method)
        {                    
            foreach($validMethods as $valid)
            {
                if(stripos($method->name, $valid) === 0)
                {
                    $methodName = strtolower(substr($method->name, strlen($valid)));
                    
                    if($methodName === self::DEFAULT_CONTROLLER_ROUTE)
                    {
                        $this->addRoute($valid, $route, array($classname, $method->name));
                    }
                    
                    $sep = $route === '/' ? '' : '/';
                    
                    $this->addRoute($valid, $route . $sep . $methodName, array($classname, $method->name));
                    
                    break;
                }
            }
        }
    }
    
    public function getValidMethods()
    {
        return array(
            Route::ANY,
            Route::GET,
            Route::POST,
            Route::PUT,
            Route::DELETE,
            Route::HEAD,
            Route::OPTIONS,
        );
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
