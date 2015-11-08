<?php namespace Phroute\Phroute\Exception;

class HttpMethodNotAllowedException extends HttpException {

    public function __construct(array $allowed)
    {
        $headers = array('Allow' => implode(', ', $allowed));

        parent::__construct(405, "Method not allowed", $headers);
    }
}
