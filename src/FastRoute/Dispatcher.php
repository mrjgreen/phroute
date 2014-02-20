<?php namespace FastRoute;

use FastRoute\Exception\HttpMethodNotAllowedException;
use FastRoute\Exception\HttpRouteNotFoundException;

class Dispatcher {

    private $staticRouteMap;
    private $variableRouteData;

    public function __construct(RouteCollector $data)
    {
        list($this->staticRouteMap, $this->variableRouteData) = $data->getData();
    }

    public function dispatch($httpMethod, $uri)
    {
        if($httpMethod === Route::HEAD)
        {
            $httpMethod = Route::GET;
        }
        
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
            throw new HttpMethodNotAllowedException();
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
                throw new HttpMethodNotAllowedException();
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
