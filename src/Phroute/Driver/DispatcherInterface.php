<?php namespace Phroute\Phroute\Driver;

interface DispatcherInterface {

    /**
     * @param $httpMethod
     * @param $uri
     * @return mixed
     */
    public function dispatchRoute($httpMethod, $uri);
}
