# PHRoute - Fast request router for PHP

![Build Status](https://github.com/mrjgreen/phroute/actions/workflows/php.yml/badge.svg)
[![Coverage Status](https://coveralls.io/repos/github/mrjgreen/phroute/badge.svg)](https://coveralls.io/github/mrjgreen/phroute)
[![Latest Stable Version](https://poser.pugx.org/phroute/phroute/v/stable)](https://packagist.org/packages/phroute/phroute)
[![License](https://poser.pugx.org/phroute/phroute/license)](https://packagist.org/packages/phroute/phroute)
[![Total Downloads](https://poser.pugx.org/phroute/phroute/downloads)](https://packagist.org/packages/phroute/phroute)

## This library provides a fast implementation of a regular expression based router.

- [Super fast](#performance)
- [Route parameters and optional route parameters](#defining-routes)
- [Dependency Injection Resolving (Integrates easily with 3rd parties eg. Orno/Di)](#dependency-injection)
- [Named routes and reverse routing](#named-routes-for-reverse-routing)
- [Restful controller routing](#controllers)
- [Route filters and filter groups](#filters)
- [Route prefix groups](#prefix-groups)

### Credit to nikic/FastRoute.

While the bulk of the library and extensive unit tests are my own, credit for the regex matching core implementation and benchmarking goes to [nikic](https://github.com/nikic/FastRoute). Please go and read nikic's
[blog post explaining how the implementation works and why it's fast.](http://nikic.github.io/2014/02/18/Fast-request-routing-using-regular-expressions.html)

Many modifications to the core have been made to suit the new library wrapper, and additional features added such as optional route parameters and reverse routing etc, but please head over and checkout nikic's library to see the origins of the core and how it works.

## Installation

Install via composer

```
composer require phroute/phroute
```

## Usage

### Example

```PHP

$router->get('/example', function(){
    return 'This route responds to requests with the GET method at the path /example';
});

$router->post('/example/{id}', function($id){
    return 'This route responds to requests with the POST method at the path /example/1234. It passes in the parameter as a function argument.';
});

$router->any('/example', function(){
    return 'This route responds to any method (POST, GET, DELETE, OPTIONS, HEAD etc...) at the path /example';
});
```

### Defining routes

```PHP
use Phroute\Phroute\RouteCollector;

$router = new RouteCollector();

$router->get($route, $handler);    # match only get requests
$router->post($route, $handler);   # match only post requests
$router->delete($route, $handler); # match only delete requests
$router->any($route, $handler);    # match any request method

etc...
```

> These helper methods are wrappers around `addRoute($method, $route, $handler)`

This method accepts the HTTP method the route must match, the route pattern and a callable handler, which can be a closure, function name or `['ClassName', 'method']` pair.

The methods also accept an additional parameter which is an array of middlewares: currently filters `before` and `after`, and route prefixing with `prefix` are supported. See the sections on Filters and Prefixes for more info and examples.

By default a route pattern syntax is used where `{foo}` specifies a placeholder with name `foo`
and matching the string `[^/]+`. To adjust the pattern the placeholder matches, you can specify
a custom pattern by writing `{bar:[0-9]+}`. However, it is also possible to adjust the pattern
syntax by passing a custom route parser to the router at construction.

```php
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

// Lazy load autoloaded route handling classes using strings for classnames
// Calls the Controllers\User::displayUser($id) method with {id} parameter as an argument
$router->any('/users/{id}', ['Controllers\User','displayUser']);

// Optional Parameters
// simply add a '?' after the route name to make the parameter optional
// NB. be sure to add a default value for the function argument
$router->get('/user/{id}?', function($id = null) {
    return 'second';
});

# NB. You can cache the return value from $router->getData() so you don't have to create the routes each request - massive speed gains
$dispatcher = new Phroute\Phroute\Dispatcher($router->getData());

$response = $dispatcher->dispatch($_SERVER['REQUEST_METHOD'], parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));

// Print out the value returned from the dispatched function
echo $response;

```

### Regex Shortcuts

```

:i => :/d+                # numbers only
:a => :[a-zA-Z0-9]+       # alphanumeric
:c => :[a-zA-Z0-9+_\-\.]+  # alnumnumeric and + _ - . characters
:h => :[a-fA-F0-9]+       # hex

use in routes:

'/user/{name:i}'
'/user/{name:a}'

```

###Named Routes for Reverse Routing

Pass in an array as the first argument, where the first item is your route and the second item is a name with which to reference it later.

```php
$router->get(['/user/{name}', 'username'], function($name){
    return 'Hello ' . $name;
})
->get(['/page/{slug}/{id:\d+}', 'page'], function($id){
    return 'You must be authenticated to see this page: ' . $id;
});

// Use the routename and pass in any route parameters to reverse engineer an existing route path
// If you change your route path above, you won't need to go through your code updating any links/references to that route
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
}, ['before' => 'statsStart', 'after' => 'statsComplete']);
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

$router->group(['before' => 'auth'], function($router){

    $router->get('/user/{name}', function($name){
        return 'Hello ' . $name;
    })
    ->get('/page/{id:\d+}', function($id){
        return 'You must be authenticated to see this page: ' . $id;
    });

});
```

###Prefix Groups

```php

// You can combine a prefix with a filter, eg. `['prefix' => 'admin', 'before' => 'auth']`

$router->group(['prefix' => 'admin'], function($router){

    $router->get('pages', function(){
        return 'page management';
    });

    $router->get('products', function(){
        return 'product management';
    });

    $router->get('orders', function(){
        return 'order management';
    });
});
```

###Controllers

```php
namespace MyApp;

class Test {

    public function anyIndex()
    {
        return 'This is the default page and will respond to /controller and /controller/index';
    }

    /**
    * One required paramter and one optional parameter
    */
    public function anyTest($param, $param2 = 'default')
    {
        return 'This will respond to /controller/test/{param}/{param2}? with any method';
    }

    public function getTest()
    {
        return 'This will respond to /controller/test with only a GET method';
    }

    public function postTest()
    {
        return 'This will respond to /controller/test with only a POST method';
    }

    public function putTest()
    {
        return 'This will respond to /controller/test with only a PUT method';
    }

    public function deleteTest()
    {
        return 'This will respond to /controller/test with only a DELETE method';
    }
}

$router->controller('/controller', 'MyApp\\Test');

// Controller with associated filter
$router->controller('/controller', 'MyApp\\Test', ['before' => 'auth']);
```

### Dispatching a URI

A URI is dispatched by calling the `dispatch()` method of the created dispatcher. This method
accepts the HTTP method and a URI. Getting those two bits of information (and normalizing them
appropriately) is your job - this library is not bound to the PHP web SAPIs.

$response = (new Phroute\Phroute\Dispatcher($router))
->dispatch($\_SERVER['REQUEST_METHOD'], $\_SERVER['REQUEST_URI']);

The `dispatch()` method will call the matched route, or if no matches, throw one of the exceptions below:

    # Route not found
    Phroute\Phroute\Exception\HttpRouteNotFoundException;

    # Route found, but method not allowed
    Phroute\Phroute\Exception\HttpMethodNotAllowedException;

> **NOTE:** The HTTP specification requires that a `405 Method Not Allowed` response include the
> `Allow:` header to detail available methods for the requested resource.
> This information can be obtained from the thrown exception's message content:
> which will look like: `"Allow: HEAD, GET, POST"` etc... depending on the methods you have set
> You should catch the exception and use this to send a header to the client: `header($e->getMessage());`

###Dependency Injection

Defining your own dependency resolver is simple and easy. The router will attempt to resolve filters,
and route handlers via the dependency resolver.

The example below shows how you can define your own resolver to integrate with orno/di,
but pimple/pimple or others will work just as well.

```PHP

use Orno\Di\Container;
use Phroute\Phroute\HandlerResolverInterface;

class RouterResolver implements HandlerResolverInterface
{
    private $container;

    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    public function resolve($handler)
    {
        /*
         * Only attempt resolve uninstantiated objects which will be in the form:
         *
         *      $handler = ['App\Controllers\Home', 'method'];
         */
        if(is_array($handler) and is_string($handler[0]))
        {
            $handler[0] = $this->container[$handler[0]];
        }

        return $handler;
    }
}

```

When you create your dispatcher:

```PHP

$appContainer = new Orno\Di;

// Attach your controllers as normal
// $appContainer->add('App\Controllers\Home')


$resolver = new RouterResolver($appContainer);
$response = (new Phroute\Phroute\Dispatcher($router, $resolver))->dispatch($requestMethod, $requestUri);

```

### A Note on HEAD Requests

The HTTP spec requires servers to [support both GET and HEAD methods][2616-511]:

> The methods GET and HEAD MUST be supported by all general-purpose servers

To avoid forcing users to manually register HEAD routes for each resource we fallback to matching an
available GET route for a given resource. The PHP web SAPI transparently removes the entity body
from HEAD responses so this behavior has no effect on the vast majority of users.

However, implementors using Phroute outside the web SAPI environment (e.g. a custom server) MUST
NOT send entity bodies generated in response to HEAD requests. If you are a non-SAPI user this is
_your responsibility_; Phroute has no purview to prevent you from breaking HTTP in such cases.

Finally, note that applications MAY always specify their own HEAD method route for a given
resource to bypass this behavior entirely.

### Performance

Performed on a machine with :

- Processor 2.3 GHz Intel Core i7
- Memory 8 GB 1600 MHz DDR3

####Phroute

This test is to illustrate, in part, the efficiency of the lightweight routing-core, but mostly the lack of degradation of matching speed as the number of routes grows, as compared to conventional libraries.

##### With 10 routes, matching 1st route (best case)

```
$ /usr/local/bin/ab -n 1000 -c 100 http://127.0.0.1:9943/

Finished 1000 requests

Time taken for tests:   3.062 seconds
Requests per second:    326.60 [#/sec] (mean)
Time per request:       306.181 [ms] (mean)
Time per request:       3.062 [ms] (mean, across all concurrent requests)
Transfer rate:          37.32 [Kbytes/sec] received

Percentage of the requests served within a certain time (ms)
  50%    306
  66%    307
  75%    307
  80%    308
  90%    309
  95%    309
  98%    310
  99%    310
 100%    310 (longest request)
```

##### With 10 routes, matching last route (worst case)

Note that the match is just as quick as against the first route

```
$ /usr/local/bin/ab -n 1000 -c 100 http://127.0.0.1:9943/thelastroute

Finished 1000 requests

Time taken for tests:   3.079 seconds
Requests per second:    324.80 [#/sec] (mean)
Time per request:       307.880 [ms] (mean)
Time per request:       3.079 [ms] (mean, across all concurrent requests)
Transfer rate:          37.11 [Kbytes/sec] received


Percentage of the requests served within a certain time (ms)
  50%    307
  66%    308
  75%    309
  80%    309
  90%    310
  95%    311
  98%    312
  99%    312
 100%    313 (longest request)
```

##### With 100 routes, matching last route (worst case)

```
$ /usr/local/bin/ab -n 1000 -c 100 http://127.0.0.1:9943/thelastroute

Finished 1000 requests

Time taken for tests:   3.195 seconds
Requests per second:    312.97 [#/sec] (mean)
Time per request:       319.515 [ms] (mean)
Time per request:       3.195 [ms] (mean, across all concurrent requests)
Transfer rate:          35.76 [Kbytes/sec] received


Percentage of the requests served within a certain time (ms)
  50%    318
  66%    319
  75%    320
  80%    320
  90%    322
  95%    323
  98%    323
  99%    324
 100%    324 (longest request)
```

##### With 1000 routes, matching the last route (worst case)

```
$ /usr/local/bin/ab -n 1000 -c 100 http://127.0.0.1:9943/thelastroute

Finished 1000 requests

Time taken for tests:   4.497 seconds
Complete requests:      1000
Requests per second:    222.39 [#/sec] (mean)
Time per request:       449.668 [ms] (mean)
Time per request:       4.497 [ms] (mean, across all concurrent requests)
Transfer rate:          25.41 [Kbytes/sec] received

Percentage of the requests served within a certain time (ms)
  50%    445
  66%    447
  75%    448
  80%    449
  90%    454
  95%    456
  98%    457
  99%    458
 100%    478 (longest request)
```

###For comparison, Laravel 4.0 routing core

Please note, this is no slight against laravel - it is based on a routing loop, which is why the performance worsens as the number of routes grows

##### With 10 routes, matching first route (best case)

```
$ /usr/local/bin/ab -n 1000 -c 100 http://127.0.0.1:4968/

Finished 1000 requests

Time taken for tests:   13.366 seconds
Requests per second:    74.82 [#/sec] (mean)
Time per request:       1336.628 [ms] (mean)
Time per request:       13.366 [ms] (mean, across all concurrent requests)
Transfer rate:          8.55 [Kbytes/sec] received

Percentage of the requests served within a certain time (ms)
  50%   1336
  66%   1339
  75%   1340
  80%   1341
  90%   1346
  95%   1348
  98%   1349
  99%   1351
 100%   1353 (longest request)
```

##### With 10 routes, matching last route (worst case)

```
$ /usr/local/bin/ab -n 1000 -c 100 http://127.0.0.1:4968/thelastroute

Finished 1000 requests

Time taken for tests:   14.621 seconds
Requests per second:    68.39 [#/sec] (mean)
Time per request:       1462.117 [ms] (mean)
Time per request:       14.621 [ms] (mean, across all concurrent requests)
Transfer rate:          7.81 [Kbytes/sec] received

Percentage of the requests served within a certain time (ms)
  50%   1461
  66%   1465
  75%   1469
  80%   1472
  90%   1476
  95%   1479
  98%   1480
  99%   1482
 100%   1484 (longest request)
```

##### With 100 routes, matching last route (worst case)

```
$ /usr/local/bin/ab -n 1000 -c 100 http://127.0.0.1:4968/thelastroute

Finished 1000 requests

Time taken for tests:   31.254 seconds
Requests per second:    32.00 [#/sec] (mean)
Time per request:       3125.402 [ms] (mean)
Time per request:       31.254 [ms] (mean, across all concurrent requests)
Transfer rate:          3.66 [Kbytes/sec] received

Percentage of the requests served within a certain time (ms)
  50%   3124
  66%   3145
  75%   3154
  80%   3163
  90%   3188
  95%   3219
  98%   3232
  99%   3236
 100%   3241 (longest request)
```

##### With 1000 routes, matching last route (worst case)

```
$ /usr/local/bin/ab -n 1000 -c 100 http://127.0.0.1:5740/thelastroute

Finished 1000 requests

Time taken for tests:   197.366 seconds
Requests per second:    5.07 [#/sec] (mean)
Time per request:       19736.598 [ms] (mean)
Time per request:       197.366 [ms] (mean, across all concurrent requests)
Transfer rate:          0.58 [Kbytes/sec] received

Percentage of the requests served within a certain time (ms)
  50%  19736
  66%  19802
  75%  19827
  80%  19855
  90%  19898
  95%  19918
  98%  19945
  99%  19960
 100%  19975 (longest request)
```
