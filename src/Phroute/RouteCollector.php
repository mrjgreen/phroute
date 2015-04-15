<?php namespace Phroute\Phroute;

use ReflectionClass;
use ReflectionMethod;

use Phroute\Phroute\Exception\BadRouteException;

/**
 * Class RouteCollector
 * @package Phroute\Phroute
 */
class RouteCollector implements RouteDataProviderInterface {

    /**
     *
     */
    const DEFAULT_CONTROLLER_ROUTE = 'index';

    /**
     *
     */
    const APPROX_CHUNK_SIZE = 10;

    /**
     * @var RouteParser
     */
    private $routeParser;
    /**
     * @var array
     */
    private $filters = [];
    /**
     * @var array
     */
    private $staticRoutes = [];
    /**
     * @var array
     */
    private $regexToRoutesMap = [];
    /**
     * @var array
     */
    private $reverse = [];

    /**
     * @var array
     */
    private $globalFilters = [];

    /**
     * @param RouteParser $routeParser
     */
    public function __construct(RouteParser $routeParser = null) {
        $this->routeParser = $routeParser ?: new RouteParser();
    }

    /**
     * @param $name
     * @param array $args
     * @return string
     */
    public function route($name, array $args = null)
    {
        $url = [];

        $replacements = is_null($args) ? [] : array_values($args);

        $variable = 0;

        foreach($this->reverse[$name] as $part)
        {
            if(!$part['variable'])
            {
                $url[] = $part['value'];
            }
            elseif(isset($replacements[$variable]))
            {
                if($part['optional'])
                {
                    $url[] = '/';
                }

                $url[] = $replacements[$variable++];
            }
            elseif(!$part['optional'])
            {
                throw new BadRouteException("Expecting route variable '{$part['name']}'");
            }
        }

        return implode('', $url);
    }

    /**
     * @param $httpMethod
     * @param $route
     * @param $handler
     * @param array $filters
     * @return $this
     */
    public function addRoute($httpMethod, $route, $handler, array $filters = []) {
        
        if(is_array($route))
        {
            list($route, $name) = $route;
        }
        
        list($routeData, $reverseData) = $this->routeParser->parse(trim($route , '/'));
        
        if(isset($name))
        {
            $this->reverse[$name] = $reverseData;
        }
        
        $filters = array_merge_recursive($this->globalFilters, $filters);

        isset($routeData[1]) ? 
            $this->addVariableRoute($httpMethod, $routeData, $handler, $filters) :
            $this->addStaticRoute($httpMethod, $routeData, $handler, $filters);
        
        return $this;
    }

    /**
     * @param $httpMethod
     * @param $routeData
     * @param $handler
     * @param $filters
     */
    private function addStaticRoute($httpMethod, $routeData, $handler, $filters)
    {
        $routeStr = $routeData[0];

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
     */
    private function addVariableRoute($httpMethod, $routeData, $handler, $filters)
    {
        list($regex, $variables) = $routeData;

        if (isset($this->regexToRoutesMap[$regex][$httpMethod]))
        {
            throw new BadRouteException("Cannot register two routes matching '$regex' for method '$httpMethod'");
        }

        $this->regexToRoutesMap[$regex][$httpMethod] = [$handler, $filters, $variables];
    }

    /**
     * @param $option string
     * @param $instance $this
     */
    private function processPrefixedRoutes($option, $instance)
    {
        list($prefix, $routeData)=$this->routeParser->parse(trim($option, '/'));
        $prefix = $prefix[0];

        foreach ($instance->reverse as $name => $entries) {
            $flip = [$routeData[0],  ['variable' => false, 'value' => '/']];
            foreach ($entries as $entry) {
                $flip[] = $entry;
            }

            $this->reverse[$name] = $flip;
        }


        if ($prefix === trim($option, '/')) {
            foreach ($instance->regexToRoutesMap as $pattern => $entry) {
                $this->regexToRoutesMap[$prefix . '/' . $pattern] = $entry;
            }

            foreach ($instance->staticRoutes as $pattern => $entry) {
                $pattern = ($pattern === '' ?'': '/' . $pattern);
                $this->staticRoutes[$prefix . $pattern] = $entry;
            }
        } else {
            throw new BadRouteException(sprintf('Bad prefix: "%s", should not contain variables.', $prefix));
        }
    }
    /**
     * @param array $filters
     * @param callable $callback
     */
    public function group(array $filters, \Closure $callback, array $options = [])
    {
        $oldGlobal = $this->globalFilters;
        $this->globalFilters = array_merge_recursive($this->globalFilters, array_intersect_key($filters, [Route::AFTER => 1, Route::BEFORE => 1]));

        if (array_key_exists('prefix', $options)) {
            $self = clone $this;
            $self->regexToRoutesMap = [];
            $self->staticRoutes = [];

            $callback($self);

            $this->processPrefixedRoutes($options['prefix'], $self);
        } else {
            $callback($this);
        }



        $this->globalFilters = $oldGlobal;
    }

    /**
     * @param $name
     * @param $handler
     */
    public function filter($name, $handler)
    {
        $this->filters[$name] = $handler;
    }

    /**
     * @param $route
     * @param $handler
     * @param array $filters
     * @return RouteCollector
     */
    public function get($route, $handler, array $filters = [])
    {
        return $this->addRoute(Route::GET, $route, $handler, $filters);
    }

    /**
     * @param $route
     * @param $handler
     * @param array $filters
     * @return RouteCollector
     */
    public function head($route, $handler, array $filters = [])
    {
        return $this->addRoute(Route::HEAD, $route, $handler, $filters);
    }

    /**
     * @param $route
     * @param $handler
     * @param array $filters
     * @return RouteCollector
     */
    public function post($route, $handler, array $filters = [])
    {
        return $this->addRoute(Route::POST, $route, $handler, $filters);
    }

    /**
     * @param $route
     * @param $handler
     * @param array $filters
     * @return RouteCollector
     */
    public function put($route, $handler, array $filters = [])
    {
        return $this->addRoute(Route::PUT, $route, $handler, $filters);
    }

    /**
     * @param $route
     * @param $handler
     * @param array $filters
     * @return RouteCollector
     */
    public function patch($route, $handler, array $filters = [])
    {
        return $this->addRoute(Route::PATCH, $route, $handler, $filters);
    }

    /**
     * @param $route
     * @param $handler
     * @param array $filters
     * @return RouteCollector
     */
    public function delete($route, $handler, array $filters = [])
    {
        return $this->addRoute(Route::DELETE, $route, $handler, $filters);
    }

    /**
     * @param $route
     * @param $handler
     * @param array $filters
     * @return RouteCollector
     */
    public function options($route, $handler, array $filters = [])
    {
        return $this->addRoute(Route::OPTIONS, $route, $handler, $filters);
    }

    /**
     * @param $route
     * @param $handler
     * @param array $filters
     * @return RouteCollector
     */
    public function any($route, $handler, array $filters = [])
    {
        return $this->addRoute(Route::ANY, $route, $handler, $filters);
    }

    /**
     * @param $route
     * @param $classname
     * @param array $filters
     * @return $this
     */
    public function controller($route, $classname, array $filters = [])
    {
        $reflection = new ReflectionClass($classname);

        $validMethods = $this->getValidMethods();

        $sep = $route === '/' ? '' : '/';

        foreach($reflection->getMethods(ReflectionMethod::IS_PUBLIC) as $method)
        {
            foreach($validMethods as $valid)
            {
                if(stripos($method->name, $valid) === 0)
                {
                    $methodName = $this->camelCaseToDashed(substr($method->name, strlen($valid)));

                    $params = $this->buildControllerParameters($method);

                    if($methodName === self::DEFAULT_CONTROLLER_ROUTE)
                    {
                        $this->addRoute($valid, $route . $params, [$classname, $method->name], $filters);
                    }

                    $this->addRoute($valid, $route . $sep . $methodName . $params, [$classname, $method->name], $filters);
                    
                    break;
                }
            }
        }
        
        return $this;
    }

    /**
     * @param ReflectionMethod $method
     * @return string
     */
    private function buildControllerParameters(ReflectionMethod $method)
    {
        $params = '';

        foreach($method->getParameters() as $param)
        {
            $params .= "/{" . $param->getName() . "}" . ($param->isOptional() ? '?' : '');
        }

        return $params;
    }

    /**
     * @param $string
     * @return string
     */
    private function camelCaseToDashed($string)
    {
        return strtolower(preg_replace('/([A-Z])/', '-$1', lcfirst($string)));
    }

    /**
     * @return array
     */
    public function getValidMethods()
    {
        return [
            Route::ANY,
            Route::GET,
            Route::POST,
            Route::PUT,
            Route::PATCH,
            Route::DELETE,
            Route::HEAD,
            Route::OPTIONS,
        ];
    }

    /**
     * @return RouteDataArray
     */
    public function getData()
    {
        if (empty($this->regexToRoutesMap))
        {
            return new RouteDataArray($this->staticRoutes, [], $this->filters);
        }

        return new RouteDataArray($this->staticRoutes, $this->generateVariableRouteData(), $this->filters);
    }

    /**
     * @return array
     */
    private function generateVariableRouteData()
    {
        $chunkSize = $this->computeChunkSize(count($this->regexToRoutesMap));
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
        $routeMap = [];
        $regexes = [];
        $numGroups = 0;
        foreach ($regexToRoutesMap as $regex => $routes) {
            $firstRoute = reset($routes);
            $numVariables = count($firstRoute[2]);
            $numGroups = max($numGroups, $numVariables);

            $regexes[] = $regex . str_repeat('()', $numGroups - $numVariables);

            foreach ($routes as $httpMethod => $route) {
                $routeMap[$numGroups + 1][$httpMethod] = $route;
            }

            $numGroups++;
        }

        $regex = '~^(?|' . implode('|', $regexes) . ')$~';
        return ['regex' => $regex, 'routeMap' => $routeMap];
    }
}
