<?php namespace Phroute\Phroute\Router;
use Phroute\Phroute\Route;


/**
 * Class RouteCollector
 * @package Phroute\Phroute
 */
interface RouterInterface {

    public function addRoute($httpMethod, Route $route, $handler);
    public function resolveRoute($route);
}
