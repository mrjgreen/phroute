<?php namespace FastRoute;

use FastRoute\Exception\HttpMethodNotAllowedException;
use FastRoute\Exception\HttpRouteNotFoundException;

class Dispatcher {

    private $staticRouteMap;
    private $variableRouteData;
    private $after;
    private $before;
    private $filters;

    public function __construct(RouteCollector $data)
    {
        list($this->staticRouteMap, $this->variableRouteData) = $data->getData();
        
        list($this->before, $this->after, $this->filters) = $data->getFilters();
    }

    public function dispatch($httpMethod, $uri)
    {
        if($httpMethod === Route::HEAD)
        {
            $httpMethod = Route::GET;
        }
                
        list($beforeFilter, $afterFilter, $handler, $vars) = $this->parseFilters($httpMethod, $uri);
        
        if(($response = $this->dispatchFilters($beforeFilter)) !== null)
        {
            return $response;
        }
        
        $response = call_user_func_array($handler, $vars);

        $this->dispatchFilters($afterFilter);
        
        return $response;
    }
    
    private function dispatchFilters($filters, $args = array())
    {        
        while($filter = array_shift($filters))
        {
            if(($response = call_user_func_array($filter, $args)) !== null)
            {
                return $response;
            }
        }
    }
    
    private function parseFilters($httpMethod, $uri)
    {
        list($handler, $vars) = $this->dispatchRoute($httpMethod, $uri);
        
        $beforeFilter = array();
        $afterFilter = array();
        
        if(is_array($handler))
        {
            if(isset($handler[Route::BEFORE]))
            {
                $beforeFilter = array_intersect_key($this->filters, array_flip((array) $handler[Route::BEFORE]));
            }
            
            if(isset($handler[Route::AFTER]))
            {
                $afterFilter = array_intersect_key($this->filters, array_flip((array) $handler[Route::AFTER]));
            }
            
            $handler = array_pop($handler);
        }
        
        return array(array_merge($this->before, $beforeFilter), array_merge($this->after, $afterFilter), $handler, $vars);
    }
    
    private function dispatchRoute($httpMethod, $uri)
    {
        if (isset($this->staticRouteMap[$uri]))
        {
            return $this->dispatchStaticRoute($httpMethod, $uri);
        }
        
        return $this->dispatchVariableRoute($httpMethod, $uri);
    }

    private function dispatchStaticRoute($httpMethod, $uri)
    {
        $routes = $this->staticRouteMap[$uri];

        if (!isset($routes[$httpMethod]))
        {
            $httpMethod = Route::ANY;
                    
            if (!isset($routes[$httpMethod]))
            {
                throw new HttpMethodNotAllowedException();
            } 
        } 
        
        return array($routes[$httpMethod], array());
    }

    private function dispatchVariableRoute($httpMethod, $uri)
    {
        foreach ($this->variableRouteData as $data) 
        {
            if (!preg_match($data['regex'], $uri, $matches))
            {
                continue;
            }

            $routes = $data['routeMap'][count($matches)];
            
            if (!isset($routes[$httpMethod]))
            {
                $httpMethod = Route::ANY;

                if (!isset($routes[$httpMethod]))
                {
                    throw new HttpMethodNotAllowedException();
                } 
            } 

            list($handler, $varNames) = $routes[$httpMethod];

            $vars = array();

            foreach ($varNames as $i => $varName)
            {
                $vars[$varName] = $matches[$i + 1];
            }
            
            return array($handler, $vars);
        }

        throw new HttpRouteNotFoundException();
    }

}
