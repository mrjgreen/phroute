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
            $httpMethod = array(Route::HEAD, Route::GET);
        }
        
        $found = false;
        
        foreach((array)$httpMethod as $method)
        {
            if($found = $this->dispatchRoute($method, $uri ?: '/'))
            {
                list($handlerFilter, $vars) = $found;
                
                list($handler, $filters) = $handlerFilter;
                
                list($beforeFilter, $afterFilter) = $this->parseFilters($filters);
                
                break;
            }
        }
        
        if($found === false)
        {
            throw new HttpMethodNotAllowedException();
        }
        
        if(($response = $this->dispatchFilters($beforeFilter)) !== null)
        {
            return $response;
        }
        
        $resolvedHandler = $this->resolveHandler($handler);
        
        $response = call_user_func_array($resolvedHandler, $vars);

        $this->dispatchFilters($afterFilter);
        
        return $response;
    }
    
    private function resolveHandler($handler)
    {
        if(is_array($handler) and is_string($handler[0]))
        {
            $handler[0] = new $handler[0];
        }
        
        return $handler;
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
    
    private function parseFilters($filters)
    {        
        $beforeFilter = array();
        $afterFilter = array();
        
        if(isset($filters[Route::BEFORE]))
        {
            $beforeFilter = array_intersect_key($this->filters, array_flip((array) $filters[Route::BEFORE]));
        }

        if(isset($filters[Route::AFTER]))
        {
            $afterFilter = array_intersect_key($this->filters, array_flip((array) $filters[Route::AFTER]));
        }
        
        return array(array_merge($this->before, $beforeFilter), array_merge($this->after, $afterFilter));
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
                return false;
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
                    return false;
                } 
            } 

            list($handler, $varNames) = $routes[$httpMethod];

            foreach (array_values($varNames) as $i => $varName)
            {
                $varNames[$varName] = $matches[$i + 1];
            }
            
            return array($handler, $varNames);
        }

        throw new HttpRouteNotFoundException();
    }

}
