<?php namespace Phroute\Router;


/**
 * Interface RouteDataInterface
 * @package Phroute\Router
 */
interface RouteDataInterface {

    /**
     * @return array
     */
    public function getStaticRoutes();

    /**
     * @return array
     */
    public function getVariableRoutes();

    /**
     * @return array
     */
    public function getFilters();
}
