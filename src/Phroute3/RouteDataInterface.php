<?php namespace Phroute\Phroute;


/**
 * Interface RouteDataInterface
 * @package Phroute\Phroute
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
