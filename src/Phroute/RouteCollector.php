<?php namespace Phroute\Phroute;

use Phroute\Phroute\Driver\CollectorInterface;
use Phroute\Phroute\Driver\FastRoute\FastRouteCollector;
use Phroute\Phroute\Driver\Hybrid\HybridCollector;
use Phroute\Phroute\Driver\Regex\RegexCollector;
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
    private $reverse = [];

    /**
     * @var array
     */
    private $globalFilters = [];

    /**
     * @var
     */
    private $globalRoutePrefix;

    /**
     * @var
     */
    private $driver;

    /**
     * @param RouteParser $routeParser
     * @param CollectorInterface $collector
     */
    public function __construct($routeParser = null, CollectorInterface $collector = null) {

        $this->routeParser = $routeParser ?: new RouteParser();

        $this->driver = $collector ?: new FastRouteCollector();
    }

    /**
     * @param $name
     * @return bool
     */
    public function hasRoute($name) {
        return isset($this->reverse[$name]);
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

        $route = $this->addPrefix($this->trim($route));

        $parsedRoute = $this->routeParser->parse($route);
        
        if(isset($name))
        {
            $this->reverse[$name] = $parsedRoute->reverse;
        }
        
        $filters = array_merge_recursive($this->globalFilters, $filters);

        $this->driver->addRoute($httpMethod, $parsedRoute, $handler, $filters);
        
        return $this;
    }

    /**
     * @param array $filters
     * @param \Closure $callback
     */
    public function group(array $filters, \Closure $callback)
    {
        $oldGlobalFilters = $this->globalFilters;

        $oldGlobalPrefix = $this->globalRoutePrefix;

        $this->globalFilters = array_merge_recursive($this->globalFilters, array_intersect_key($filters, [Route::AFTER => 1, Route::BEFORE => 1]));

        $newPrefix = isset($filters[Route::PREFIX]) ? $this->trim($filters[Route::PREFIX]) : null;

        $this->globalRoutePrefix = $this->addPrefix($newPrefix);

        $callback($this);

        $this->globalFilters = $oldGlobalFilters;

        $this->globalRoutePrefix = $oldGlobalPrefix;
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

    public function getData()
    {
        return new RouteDataArray($this->driver->getDispatcher(), $this->filters);
    }

    private function addPrefix($route)
    {
        return $this->trim($this->trim($this->globalRoutePrefix) . '/' . $route);
    }

    /**
     * @param $route
     * @return string
     */
    private function trim($route)
    {
        return trim($route, '/');
    }
}
