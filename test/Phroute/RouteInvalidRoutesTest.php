<?php

use Phroute\Phroute\Router;

class RouteInvalidRoutesTest extends \PHPUnit_Framework_TestCase {

    /**
     * Set appropriate options for the specific Dispatcher class we're testing
     */
    private function router()
    {
        return new Router();
    }

    /**
     * @dataProvider providerInvalidRoutes
     * @expectedException \Phroute\Phroute\Exception\HttpRouteNotFoundException
     * @expectedExceptionMessage does not exist
     */
    public function dispatchTest($method, $uri, $callback)
    {
        $r = $this->router();
        $callback($r);
        $r->dispatch($method, $uri);
    }


    /**
     * @expectedException \Phroute\Phroute\Exception\HttpRouteNotFoundException
     * @expectedExceptionMessage does not exist
     */
    public function testItThrowsA404ForMissingRoute()
    {
        $callback = function(Router $r) {
            $r->addRoute('GET', '/resource/123/456', 'handler0');
        };

        $method = 'GET';
        $uri = '/not-found';

        $this->dispatchTest($method, $uri, $callback);
    }

    /**
     * @expectedException \Phroute\Phroute\Exception\HttpRouteNotFoundException
     * @expectedExceptionMessage does not exist
     */
    public function testItThrowsA404ForUnmatchedDynamicRoute()
    {
        $callback = function(Router $r) {
            $r->addRoute('GET', 'server/{ip:\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}}', function($year) {
                return trim("$year");
            });
        };

        $this->dispatchTest('GET', 'server/1044.10.10.10', $callback);
    }

    /**
     * @expectedException \Phroute\Phroute\Exception\HttpMethodNotAllowedException
     * @expectedExceptionMessage not allowed
     */
    public function testItThrowsA405MethodNotAllowedForStatic()
    {
        $callback = function(Router $r) {
            $r->addRoute('GET', '/matching/route', function($name = null) {
                return $name;
            });
        };

        // Correct route, but wrong method - POST instead of GET
        $this->dispatchTest('POST', '/matching/route', $callback);
    }


    /**
     * @expectedException \Phroute\Phroute\Exception\HttpMethodNotAllowedException
     * @expectedExceptionMessage not allowed
     */
    public function testItThrowsA405MethodNotAllowedForDynamic()
    {
        $callback = function(Router $r) {
            $r->addRoute('GET', '/server/{ip:\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}}', function($year) {
                return trim("$year");
            });
        };

        // Correct route and param, but wrong method - PUT instead of GET
        $this->dispatchTest('PUT', '/server/104.10.10.10', $callback);
    }


    /**
     * @expectedException \Phroute\Phroute\Exception\BadRouteException
     * @expectedExceptionMessage Method 'GET' already exists for route 'user'
     */
    public function testItWillNotDuplicateStaticRoute()
    {
        $r = $this->router();
        $r->addRoute('GET', '/user', function() {

        });
        $r->addRoute('GET', '/user', function() {

        });
    }

    /**
     * @expectedException \Phroute\Phroute\Exception\BadRouteException
     * @expectedExceptionMessage Static route 'user/username' is shadowed by previously defined variable route 'user/([^/]+)' for method 'GET'
     */
    public function testItWillNotAllowStaticRouteToShadowDynamicRoute()
    {
        $r = $this->router();
        $r->addRoute('GET', '/user/{name}', function() {

        });
        $r->addRoute('GET', '/user/username', function() {

        });
    }
}
