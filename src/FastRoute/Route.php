<?php

namespace FastRoute;

class Route {
    
    const GET = 'GET';
    const HEAD = 'HEAD';
    const POST = 'POST';
    const PUT = 'PUT';
    const DELETE = 'DELETE';
    const OPTIONS = 'OPTIONS';
    
    public $httpMethod;
    public $regex;
    public $variables;
    public $handler;

    public function __construct($httpMethod, $handler, $regex, $variables) {
        $this->httpMethod = $httpMethod;
        $this->handler = $handler;
        $this->regex = $regex;
        $this->variables = $variables;
    }

    public function matches($str) {
        $regex = '~^' . $this->regex . '$~';
        return (bool) preg_match($regex, $str);
    }
}

