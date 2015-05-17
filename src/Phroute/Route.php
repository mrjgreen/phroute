<?php namespace Phroute\Phroute;

class Route {

    /**
     * Constants for before and after filters
     */
    const BEFORE = 'before';

    const AFTER = 'after';

    const PREFIX = 'prefix';

    /**
     * Constants for common HTTP methods
     */
    const ANY = 'ANY';

    const GET = 'GET';

    const HEAD = 'HEAD';

    const POST = 'POST';

    const PUT = 'PUT';

    const PATCH = 'PATCH';

    const DELETE = 'DELETE';

    const OPTIONS = 'OPTIONS';

    public $regex;

    /**
     * @var RoutePiece[]
     */
    public $pieces;

    public $variables;

    public $reverse;

    public function __construct($regex, $pieces, $variables, $reverse)
    {
        $this->regex = $regex;

        $this->pieces = $pieces;

        $this->variables = $variables;

        $this->reverse = $reverse;
    }
}

