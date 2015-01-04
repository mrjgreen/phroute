<?php namespace Phroute\Phroute;


interface RouteDataInterface {

    public function getStaticRoutes();
    public function getVariableRoutes();
    public function getFilters();
}
