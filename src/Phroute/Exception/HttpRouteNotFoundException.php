<?php namespace Phroute\Phroute\Exception;

class HttpRouteNotFoundException extends HttpException {

    public function __construct()
    {
        parent::__construct(404, "Route does not exist");
    }
}

