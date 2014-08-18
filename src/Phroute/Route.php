<?php

namespace Phroute;

interface Route {

    /**
     * Constants for before and after filters
     */
    const BEFORE = 'before';

    const AFTER = 'after';

    /**
     * Constants for common HTTP methods
     */
    const ANY = 'ANY';

    const GET = 'GET';

    const HEAD = 'HEAD';

    const POST = 'POST';

    const PUT = 'PUT';

    const DELETE = 'DELETE';

    const OPTIONS = 'OPTIONS';
}

