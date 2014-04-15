PHRoute - Fast request router for PHP
=======================================

[![Build Status](https://travis-ci.org/joegreen0991/phroute.svg)](https://travis-ci.org/joegreen0991/phroute)  [![Coverage Status](https://coveralls.io/repos/joegreen0991/phroute/badge.png?branch=master)](https://coveralls.io/r/joegreen0991/phroute?branch=master)

#### Based on nikic/FastRoute. 

The regex engine is taken from the nikic/FastRoute library

This library provides a fast implementation of a regular expression based router. [Blog post explaining how the
implementation works and why it is fast.][blog_post]

Installation
------------
Install via composer

```
{
    "require": {
        "phroute/phroute": "1.*"
    }
}

```

Usage
-----

### Defining routes

The routes are added by calling `addRoute()` on the `Phroute\RouteCollector` collector instance.

This method accepts the HTTP method the route must match, the route pattern, an associated
handler and an optional array of 'before' and 'after' filters. The handler does not necessarily have 
to be a callback (it could also be a controller class name and method or any other kind of data you wish to 
associate with the route).

By default a route pattern syntax is used where `{foo}` specified a placeholder with name `foo`
and matching the string `[^/]+`. To adjust the pattern the placeholder matches, you can specify
a custom pattern by writing `{bar:[0-9]+}`. However, it is also possible to adjust the pattern
syntax by passing using a different route parser.


```php

$router = new Phroute\RouteCollector(new Phroute\RouteParser);


$router->any('/example', function(){
    return 'This route responds to any method (POST, GET, DELETE etc...) at the URI /example';
});

// or '/page/{id:i}' (see shortcuts)

$router->post('/page/{id:\d+}', function($id){

    // $id contains the url paramter
    
    return 'This route responds to the post method at the URI /page/{param} where param is at least one number';
});

$router->any('/', function(){

    return 'This responds to the default route';
});

// Optional Parameters
// simply add a '?' after the route name to make the parameter optional
// NB. be sure to add a default value for the function argument
$router->addRoute('GET', '/user/{id}?', function($id = null) {
    return 'second';
});


$response = (new Phroute\Dispatcher($router))->dispatch($_SERVER['REQUEST_METHOD'], $_SERVER['REQUEST_URI']);
    
// Print out the value returned from the dispatched function
echo $response;

```

### Regex Shortcuts

```

:i => :/d+                # numbers only
:a => :[a-zA-Z0-9]+       # alphanumeric
:c => :[a-zA-Z0-9+_-\.]+  # alnumnumeric and + _ - . characters 
:h => :[a-fA-F0-9]+       # hex

use in routes:

'/user/{name:i}'
'/user/{name:a}'

```

###Named Routes

```php

$router->get(['/user/{name}', 'username'], function($name){
    return 'Hello ' . $name;
})
->get(['/page/{slug}/{id:\d+}', 'page'], function($id){
    return 'You must be authenticated to see this page: ' . $id;
});

$router->route('username', 'joe');
// string(9) '/user/joe'

$router->route('page', ['intro', 456]);
// string(15) '/page/intro/456'

```

###Filters

```php

$router->filter('statsStart', function(){    
    setPageStartTime(microtime(true));
});

$router->filter('statsComplete', function(){    
    var_dump('Page load time: ' . (microtime(true) - getPageStartTime()));
});

$router->get('/user/{name}', function($name){
    return 'Hello ' . $name;
}, array('before' => 'statsStart', 'after' => 'statsComplete'));
```

###Filter Groups

Wrap multiple routes in a route group to apply that filter to every route defined within. You can nest route groups if required.

```php

// Any thing other than null returned from a filter will prevent the route handler from being dispatched
$router->filter('auth', function(){    
    if(!isset($_SESSION['user'])) 
    {
        header('Location: /login');
        
        return false;
    }
});

$router->group(array('before' => 'auth'), function($router){
    
    $router->get('/user/{name}', function($name){
        return 'Hello ' . $name;
    })
    ->get('/page/{id:\d+}', function($id){
        return 'You must be authenticated to see this page: ' . $id;
    });
    
});
```

##Controllers

```php
class Test {
    
    public function anyIndex()
    {
        return 'This is the default page and will respond to /controller and /controller/index';
    }
    
    public function anyTestany()
    {
        return 'This will respond to /controller/testany with any method';
    }
    
    public function getTestget()
    {
        return 'This will respond to /controller/testget with only a GET method';
    }
    
    public function postTestpost()
    {
        return 'This will respond to /controller/testpost with only a POST method';
    }
    
    public function putTestput()
    {
        return 'This will respond to /controller/testput with only a PUT method';
    }
    
    public function deleteTestdelete()
    {
        return 'This will respond to /controller/testdelete with only a DELETE method';
    }
}

Route::controller('/controller', 'Test');
```


### Dispatching a URI

A URI is dispatched by calling the `dispatch()` method of the created dispatcher. This method
accepts the HTTP method and a URI. Getting those two bits of information (and normalizing them
appropriately) is your job - this library is not bound to the PHP web SAPIs.

The `dispatch()` method returns an array those first element contains a status code. It is one
of `Dispatcher::NOT_FOUND`, `Dispatcher::METHOD_NOT_ALLOWED` and `Dispatcher::FOUND`. For the
method not allowed status the second array element contains a list of HTTP methods allowed for
this method. For example:

    [Phroute\Dispatcher::METHOD_NOT_ALLOWED, ['GET', 'POST']]

> **NOTE:** The HTTP specification requires that a `405 Method Not Allowed` response include the
`Allow:` header to detail available methods for the requested resource. Applications using Phroute
should use the second array element to add this header when relaying a 405 response.

For the found status the second array element is the handler that was associated with the route
and the third array element is a dictionary of placeholder names to their values. For example:

    /* Routing against GET /user/nikic/42 */

    [Phroute\Dispatcher::FOUND, 'handler0', ['name' => 'nikic', 'id' => '42']]


### A Note on HEAD Requests

The HTTP spec requires servers to [support both GET and HEAD methods][2616-511]:

> The methods GET and HEAD MUST be supported by all general-purpose servers

To avoid forcing users to manually register HEAD routes for each resource we fallback to matching an
available GET route for a given resource. The PHP web SAPI transparently removes the entity body
from HEAD responses so this behavior has no effect on the vast majority of users.

However, implementors using Phroute outside the web SAPI environment (e.g. a custom server) MUST
NOT send entity bodies generated in response to HEAD requests. If you are a non-SAPI user this is
*your responsibility*; Phroute has no purview to prevent you from breaking HTTP in such cases.

Finally, note that applications MAY always specify their own HEAD method route for a given
resource to bypass this behavior entirely.

### Credits

This library is based on a router that [Levi Morrison][levi] implemented for the Aerys server.

A large number of tests, as well as HTTP compliance considerations, were provided by [Daniel Lowrey][rdlowrey].


[2616-511]: http://www.w3.org/Protocols/rfc2616/rfc2616-sec5.html#sec5.1.1 "RFC 2616 Section 5.1.1"
[blog_post]: http://nikic.github.io/2014/02/18/Fast-request-routing-using-regular-expressions.html
[levi]: https://github.com/morrisonlevi
[rdlowrey]: https://github.com/rdlowrey
