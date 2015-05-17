<?php namespace Phroute\Phroute\Driver;


use Phroute\Phroute\Route;

interface CollectorInterface {

    /**
     * @param $httpMethod
     * @param Route $routeData
     * @param $handler
     * @param $filters
     */
    public function addRoute($httpMethod, Route $routeData, $handler, $filters);

    /**
     * @return DispatcherInterface
     */
    public function getDispatcher();
}
