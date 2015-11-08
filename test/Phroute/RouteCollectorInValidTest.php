<?php

namespace Phroute\Phroute\Dispatcher;

use Phroute\Phroute\RouteCollector;
use Phroute\Phroute\RouteParser;
use Phroute\Phroute\Dispatcher;
use Phroute\Phroute\Route;


class RouteCollectorInValidTest extends \PHPUnit_Framework_TestCase {

    /**
     * Set appropriate options for the specific Dispatcher class we're testing
     */
    private function router()
    {
        return new RouteCollector();
    }

    /**
     * @dataProvider providerInvalidRoutes
     * @expectedException \Phroute\Phroute\Exception\HttpRouteNotFoundException
     * @expectedExceptionMessage does not exist
     */
    public function testItDispatchesInvalidRoutes($method, $uri, $callback)
    {
        $r = $this->router();
        $callback($r);
        $r->dispatch($method, $uri);
    }

    public function providerInvalidRoutes()
    {
        $cases = [];

        // 0 -------------------------------------------------------------------------------------->

        $callback = function( $r) {
            $r->addRoute('GET', '/resource/123/456', 'handler0');
        };

        $method = 'GET';
        $uri = '/not-found';

        $cases[] = [$method, $uri, $callback];

        // 1 -------------------------------------------------------------------------------------->
        // reuse callback from #0
        $method = 'POST';
        $uri = '/not-found';

        $cases[] = [$method, $uri, $callback];

        // 2 -------------------------------------------------------------------------------------->
        // reuse callback from #1
        $method = 'PUT';
        $uri = '/not-found';

        $cases[] = [$method, $uri, $callback];

        // 3 -------------------------------------------------------------------------------------->

        $callback = function( $r) {
            $r->addRoute('GET', '/handler0', 'handler0');
            $r->addRoute('GET', '/handler1', 'handler1');
            $r->addRoute('GET', '/handler2', 'handler2');
        };

        $method = 'GET';
        $uri = '/not-found';

        $cases[] = [$method, $uri, $callback];

        // 4 -------------------------------------------------------------------------------------->

        $callback = function( $r) {
            $r->addRoute('GET', '/user/{name}/{id:[0-9]+}', 'handler0');
            $r->addRoute('GET', '/user/{id:[0-9]+}', 'handler1');
            $r->addRoute('GET', '/user/{name}', 'handler2');
        };

        $method = 'GET';
        $uri = '/not-found';

        $cases[] = [$method, $uri, $callback];

        // 5 -------------------------------------------------------------------------------------->
        // reuse callback from #4
        $method = 'GET';
        $uri = '/user/rdlowrey/12345/not-found';

        $cases[] = [$method, $uri, $callback];

        // x -------------------------------------------------------------------------------------->

        $callback = function($r) {
            $r->addRoute('GET', '/user/random_{name}?', function($name = null) {
                return $name;
            });
        };

        //17
        $cases[] = ['GET', 'rdlowrey', $callback];

        //19
        $cases[] = ['GET', '/user/rdlowrey', $callback, null];


        // 20 -------------------------------------------------------------------------------------->
        // Test \d{3,4} style quantifiers
        $callback = function($r) {
            $r->addRoute('GET', 'server/{ip:\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}}', function($year) {
                return trim("$year");
            });
        };

        $cases[] = ['GET', 'server/1044.10.10.10', $callback, null];
        $cases[] = ['GET', 'server/0.0.0', $callback, null];
        $cases[] = ['GET', 'server/.2.23.111', $callback, null];

        return $cases;
    }
}
