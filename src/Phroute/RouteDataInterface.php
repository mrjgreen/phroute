<?php namespace Phroute\Phroute;


/**
 * Interface RouteDataInterface
 * @package Phroute\Phroute
 */
interface RouteDataInterface {

    /**
     * @return Driver\DispatcherInterface
     */
    public function getDispatcher();

    /**
     * @return array
     */
    public function getFilters();
}
