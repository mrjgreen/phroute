<?php

namespace FastRoute\Dispatcher;

use FastRoute\Dispatcher;
use FastRoute\Exception\HttpMethodNotAllowedException;
use FastRoute\Exception\HttpRouteNotFoundException;

class GroupCountBased implements Dispatcher {
    private $staticRouteMap;
    private $variableRouteData;

    public function __construct($data) {
        list($this->staticRouteMap, $this->variableRouteData) = $data;
    }

    public function dispatch($httpMethod, $uri) {
        if (isset($this->staticRouteMap[$uri])) {
            return $this->dispatchStaticRoute($httpMethod, $uri);
        } else {
            return $this->dispatchVariableRoute($httpMethod, $uri);
        }
    }

    private function dispatchStaticRoute($httpMethod, $uri) {
        $routes = $this->staticRouteMap[$uri];

        if (isset($routes[$httpMethod])) {
            return array($routes[$httpMethod], array());
        } elseif ($httpMethod === 'HEAD' && isset($routes['GET'])) {
            return array($routes['GET'], array());
        } 
        
        throw new HttpMethodNotAllowedException();
    }

    private function dispatchVariableRoute($httpMethod, $uri) {
        foreach ($this->variableRouteData as $data) {
            if (!preg_match($data['regex'], $uri, $matches)) {
                continue;
            }

            $routes = $data['routeMap'][count($matches)];
            if (!isset($routes[$httpMethod])) {
                if ($httpMethod === 'HEAD' && isset($routes['GET'])) {
                    $httpMethod = 'GET';
                } else {
                    throw new HttpMethodNotAllowedException();
                }
            }

            list($handler, $varNames) = $routes[$httpMethod];

            $vars = [];
            $i = 0;
            foreach ($varNames as $varName) {
                $vars[$varName] = $matches[++$i];
            }
            return array($handler, $vars);
        }

        throw new HttpRouteNotFoundException();
    }
}
