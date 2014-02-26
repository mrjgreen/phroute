<?php namespace FastRoute;

class RouteCollector {
    private $routeParser;
    private $dataGenerator;
    private $filters;
    private $before = array();
    private $after = array();
    
    private $globalFilters = array();

    public function __construct(RouteParser $routeParser, DataGenerator $dataGenerator) {
        $this->routeParser = $routeParser;
        $this->dataGenerator = $dataGenerator;
    }

    private function addRoute($httpMethod, $route, $handler) {
        $routeData = $this->routeParser->parse($route);
        $this->dataGenerator->addRoute($httpMethod, $routeData, array_merge_recursive($this->globalFilters, (array)$handler));
        return $this;
    }
    
    public function group(array $filters, \Closure $callback)
    {
        $this->globalFilters = array_intersect_key($filters, array(Route::AFTER => 1, Route::BEFORE => 1));
        $callback();
        $this->globalFilters = array();
    }
    
    public function before($handler)
    {
        $this->before[] = $handler;
    }
    
    public function after($handler)
    {
        $this->after[] = $handler;
    }
    
    public function filter($name, $handler)
    {
        $this->filters[$name] = $handler;
    }
    
    public function get($route, $handler)
    {
        return $this->addRoute(Route::GET, $route, $handler);
    }
    
    public function head($route, $handler)
    {
        return $this->addRoute(Route::GET, $route, $handler);
    }
    
    public function post($route, $handler)
    {
        return $this->addRoute(Route::POST, $route, $handler);
    }
    
    public function put($route, $handler)
    {
        return $this->addRoute(Route::PUT, $route, $handler);
    }
    
    public function delete($route, $handler)
    {
        return $this->addRoute(Route::DELETE, $route, $handler);
    }
    
    public function options($route, $handler)
    {
        return $this->addRoute(Route::OPTIONS, $route, $handler);
    }
    
    public function any($route, $handler)
    {
        return $this->addRoute(Route::ANY, $route, $handler);
    }

    public function getData() 
    {
        return $this->dataGenerator->getData();
    }
    
    public function getFilters() 
    {
        return array($this->before, $this->after, $this->filters);
    }
}
