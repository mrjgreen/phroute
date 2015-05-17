<?php namespace Phroute\Phroute\Exception;

class HttpMethodNotAllowedException extends HttpException {

    public $allowedMethods;

    public $matchedRoute;

    public function __construct($message, array $allowedMethods, $matchedRoute)
    {
        parent::__construct($message);

        $this->allowedMethods = $allowedMethods;

        $this->matchedRoute = $matchedRoute;
    }
}
