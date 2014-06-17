PHRoute - Fast request router for PHP
=======================================

[![Build Status](https://travis-ci.org/joegreen0991/phroute.svg)](https://travis-ci.org/joegreen0991/phroute)  [![Coverage Status](https://coveralls.io/repos/joegreen0991/phroute/badge.png?branch=master)](https://coveralls.io/r/joegreen0991/phroute?branch=master)

#### Credit to nikic/FastRoute. 

While the bulk of the library and extensive unit tests are my own, full credit for the work and benchmarking surrounding the fast routing engine must go to [nikic/FastRoute](https://github.com/nikic/FastRoute). Some modifications to the core suit the new library wrapper, and additional features such as optional route parameters etc...

This library provides a fast implementation of a regular expression based router. [Blog post explaining how the
implementation works and why it is fast.](http://nikic.github.io/2014/02/18/Fast-request-routing-using-regular-expressions.html)

Please head over and checkout the nikic's library to see the origins of the core and how it works.


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

$router->controller('/controller', 'Test');
```


### Dispatching a URI

A URI is dispatched by calling the `dispatch()` method of the created dispatcher. This method
accepts the HTTP method and a URI. Getting those two bits of information (and normalizing them
appropriately) is your job - this library is not bound to the PHP web SAPIs.

$response = (new Phroute\Dispatcher($router))
            ->dispatch($_SERVER['REQUEST_METHOD'], $_SERVER['REQUEST_URI']);

The `dispatch()` method will call the matched route, or if no matches, throw one of the exceptions below:

    # Route not found
    Phroute\Exception\HttpRouteNotFoundException;
    
    # Route found, but method not allowed
    Phroute\Exception\HttpMethodNotAllowedException;

> **NOTE:** The HTTP specification requires that a `405 Method Not Allowed` response include the
`Allow:` header to detail available methods for the requested resource. 


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


### Some Stats
Performed on a machine with :

 * Processor  2.3 GHz Intel Core i7
 * Memory  8 GB 1600 MHz DDR3

####Phroute

This test is to illustrate, in part, the efficiency of the lightweight routing-core, but mostly to the lack of degradation of matching speed as the number of routes grows, as compared to conventional libraries.

##### With 10 routes, matching 1st route (best case)
~~~~
$ /usr/local/bin/ab -n 1000 -c 100 http://127.0.0.1:9943/

Finished 1000 requests

Document Path:          /
Document Length:        7 bytes

Concurrency Level:      100
Time taken for tests:   3.488 seconds
Complete requests:      1000
Failed requests:        0
Write errors:           0
Total transferred:      117000 bytes
HTML transferred:       7000 bytes
Requests per second:    286.71 [#/sec] (mean)
Time per request:       348.789 [ms] (mean)
Time per request:       3.488 [ms] (mean, across all concurrent requests)
Transfer rate:          32.76 [Kbytes/sec] received

Connection Times (ms)
              min  mean[+/-sd] median   max
Connect:        0    0   0.5      0       3
Processing:     5  331  60.6    347     358
Waiting:        5  331  60.6    347     358
Total:          8  332  60.1    347     358

Percentage of the requests served within a certain time (ms)
  50%    347
  66%    350
  75%    351
  80%    352
  90%    353
  95%    353
  98%    357
  99%    358
 100%    358 (longest request)
~~~~

#####  With 10 routes, matching last route (worst case)

Note that the match is just as quick as against the first route

~~~
$ /usr/local/bin/ab -n 1000 -c 100 http://127.0.0.1:9943/thelastroute

Finished 1000 requests

Document Path:          /thelastroute
Document Length:        7 bytes

Concurrency Level:      100
Time taken for tests:   3.487 seconds
Complete requests:      1000
Failed requests:        0
Write errors:           0
Total transferred:      117000 bytes
HTML transferred:       7000 bytes
Requests per second:    286.79 [#/sec] (mean)
Time per request:       348.693 [ms] (mean)
Time per request:       3.487 [ms] (mean, across all concurrent requests)
Transfer rate:          32.77 [Kbytes/sec] received

Connection Times (ms)
              min  mean[+/-sd] median   max
Connect:        0    0   0.6      0       3
Processing:     5  331  59.4    347     359
Waiting:        5  331  59.4    346     359
Total:          8  331  58.8    347     359

Percentage of the requests served within a certain time (ms)
  50%    347
  66%    348
  75%    349
  80%    350
  90%    353
  95%    358
  98%    359
  99%    359
 100%    359 (longest request)
~~~

###For comparison, Laravel 4.0 routing core

##### With 10 routes, matching first route (best case)

~~~
$ /usr/local/bin/ab -n 1000 -c 100 http://127.0.0.1:4968/

Finished 1000 requests

Document Length:        7 bytes

Concurrency Level:      100
Time taken for tests:   13.366 seconds
Complete requests:      1000
Failed requests:        0
Write errors:           0
Total transferred:      117000 bytes
HTML transferred:       7000 bytes
Requests per second:    74.82 [#/sec] (mean)
Time per request:       1336.628 [ms] (mean)
Time per request:       13.366 [ms] (mean, across all concurrent requests)
Transfer rate:          8.55 [Kbytes/sec] received

Connection Times (ms)
              min  mean[+/-sd] median   max
Connect:        0    0   0.6      0       3
Processing:    16 1270 233.7   1336    1353
Waiting:       16 1270 233.7   1335    1352
Total:         19 1271 233.1   1336    1353

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
~~~


##### With 10 routes, matching last route (worst case)

~~~
$ /usr/local/bin/ab -n 1000 -c 100 http://127.0.0.1:4968/thelastroute

Finished 1000 requests

Document Path:          /thelastroute
Document Length:        7 bytes

Concurrency Level:      100
Time taken for tests:   14.621 seconds
Complete requests:      1000
Failed requests:        0
Write errors:           0
Total transferred:      117000 bytes
HTML transferred:       7000 bytes
Requests per second:    68.39 [#/sec] (mean)
Time per request:       1462.117 [ms] (mean)
Time per request:       14.621 [ms] (mean, across all concurrent requests)
Transfer rate:          7.81 [Kbytes/sec] received

Connection Times (ms)
              min  mean[+/-sd] median   max
Connect:        0    0   0.6      0       3
Processing:    15 1389 255.7   1461    1484
Waiting:       14 1389 255.7   1460    1484
Total:         18 1389 255.1   1461    1484

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
~~~
