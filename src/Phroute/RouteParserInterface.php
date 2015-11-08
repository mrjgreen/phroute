<?php namespace Phroute\Phroute;

interface RouteParserInterface {

    /**
     * Parse a route returning the correct data format to pass to the dispatch engine.
     *
     * @param $route
     * @return array
     */
    public function parse($route);
}
