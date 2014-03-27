<?php

namespace FastRoute\Dispatcher;

use FastRoute\RouteCollector;
use FastRoute\RouteParser;
use FastRoute\DataGenerator;
use FastRoute\Dispatcher;

class DispatcherTest extends \PHPUnit_Framework_TestCase {

    /**
     * Set appropriate options for the specific Dispatcher class we're testing
     */
    private function router() {
        return  new RouteCollector(new RouteParser, new DataGenerator);
    }
    
    private function dispatch($router, $method, $uri)
    {
        return (new Dispatcher($router))->dispatch($method, $uri);
    }

    /**
     * @dataProvider provideFoundDispatchCases
     */
    public function testFoundDispatches($method, $uri, $callback, $expected) {
        $r = $this->router();
        $callback($r);
        $response = $this->dispatch($r, $method, $uri);
        $this->assertEquals($expected, $response);
    }

    /**
     * @dataProvider provideNotFoundDispatchCases
  
    public function testNotFoundDispatches($method, $uri, $callback) {
        $dispatcher = \FastRoute\simpleDispatcher($callback, $this->generateDispatcherOptions());
        $this->assertFalse(isset($routeInfo[1]),
            "NOT_FOUND result must only contain a single element in the returned info array"
        );
        list($routedStatus) = $dispatcher->dispatch($method, $uri);
        $this->assertSame($dispatcher::NOT_FOUND, $routedStatus);
    }
   */
    /**
     * @dataProvider provideMethodNotAllowedDispatchCases
    
    public function testMethodNotAllowedDispatches($method, $uri, $callback, $availableMethods) {
        $dispatcher = \FastRoute\simpleDispatcher($callback, $this->generateDispatcherOptions());
        $routeInfo = $dispatcher->dispatch($method, $uri);
        $this->assertTrue(isset($routeInfo[1]),
            "METHOD_NOT_ALLOWED result must return an array of allowed methods at index 1"
        );

        list($routedStatus, $methodArray) = $dispatcher->dispatch($method, $uri);
        $this->assertSame($dispatcher::METHOD_NOT_ALLOWED, $routedStatus);
        $this->assertSame($availableMethods, $methodArray);
    }
 */
    /**
     * @expectedException \FastRoute\Exception\BadRouteException
     * @expectedExceptionMessage Cannot use the same placeholder "test" twice
     */
    public function testDuplicateVariableNameError() {
        $this->router()->addRoute('GET', '/foo/{test}/{test:\d+}', 'handler0');
    }

    /**
     * @expectedException \FastRoute\Exception\BadRouteException
     * @expectedExceptionMessage Cannot register two routes matching "/user/([^/]+)" for method "GET"
     */
    public function testDuplicateVariableRoute() {
        $r =  $this->router();
        $r->addRoute('GET', '/user/{id}', 'handler0'); // oops, forgot \d+ restriction ;)
        $r->addRoute('GET', '/user/{name}', 'handler1');
    }

    /**
     * @expectedException \FastRoute\Exception\BadRouteException
     * @expectedExceptionMessage Cannot register two routes matching "/user" for method "GET"
     */
    public function testDuplicateStaticRoute() {
         $r = $this->router();
        $r->addRoute('GET', '/user', 'handler0');
        $r->addRoute('GET', '/user', 'handler1');
    }

    /**
     * @expectedException \FastRoute\Exception\BadRouteException
     * @expectedExceptionMessage Static route "/user/nikic" is shadowed by previously defined variable route "/user/([^/]+)" for method "GET"
     */
    public function testShadowedStaticRoute() {
         $r = $this->router();
        $r->addRoute('GET', '/user/{name}', 'handler0');
        $r->addRoute('GET', '/user/nikic', 'handler1');
    }

    public function provideFoundDispatchCases() {
        $cases = [];

        // 0 -------------------------------------------------------------------------------------->

        $callback = function($r) {
            $r->addRoute('GET', '/resource/123/456', function(){
                return true;
            });
        };

        $cases[] = ['GET', '/resource/123/456', $callback, true];

        // 1 -------------------------------------------------------------------------------------->

        $callback = function($r) {
            $r->addRoute('GET', '/handler0', 'handler0');
            $r->addRoute('GET', '/handler1', 'handler1');
            $r->addRoute('GET', '/handler2', function(){
                return true;
            });
        };
        $cases[] = ['GET', '/handler2', $callback, true];

        // 2 -------------------------------------------------------------------------------------->

        $callback = function($r) {
            $r->addRoute('GET', '/user/{name}/{id:[0-9]+}', function($name, $id){
                return [$name, $id];
            });
            $r->addRoute('GET', '/user/{id:[0-9]+}', function($id){
                return $id;
            });
            $r->addRoute('GET', '/user/{name}', function($name){
                return $name;
            });
        };

        $cases[] = ['GET', '/user/rdlowrey', $callback, 'rdlowrey'];

        
        // 3 -------------------------------------------------------------------------------------->

        // reuse $callback from #2
        
        $cases[] = ['GET', '/user/12345', $callback, '12345'];

        // 4 -------------------------------------------------------------------------------------->

        // reuse $callback from #3

        $cases[] = ['GET', '/user/NaN', $callback, 'NaN'];

        // 5 -------------------------------------------------------------------------------------->

        // reuse $callback from #4
        $cases[] = ['GET', '/user/rdlowrey/12345', $callback, ['rdlowrey', '12345']];

        // 6 -------------------------------------------------------------------------------------->

        $callback = function( $r) {
            $r->addRoute('GET', '/user/{id:[0-9]+}', function(){});
            $r->addRoute('GET', '/user/12345/extension', function(){});
            $r->addRoute('GET', '/user/{id:[0-9]+}.{extension}', function($id, $extension){
                return [$id, $extension];
            });

        };

        $cases[] = ['GET', '/user/12345.svg', $callback, ['12345','svg']];

        // 7 ----- Test GET method fallback on HEAD route miss ------------------------------------>

        $callback = function($r) {
            $r->addRoute('GET', '/user/{name}', function($name){
                return $name;
            });
            $r->addRoute('GET', '/user/{name}/{id:[0-9]+}', function($name, $id){
                return [$name, $id];
            });
            $r->addRoute('GET', '/static0', function(){
                return 'static0';
            });
            $r->addRoute('GET', '/static1', function(){});
            $r->addRoute('HEAD', '/static1', function(){
                return 'static1head';
            });
        };

        $cases[] = ['HEAD', '/user/rdlowrey', $callback, 'rdlowrey'];

        // 8 ----- Test GET method fallback on HEAD route miss ------------------------------------>

        // reuse $callback from #7
        $cases[] = ['HEAD', '/user/rdlowrey/1234', $callback, ['rdlowrey','1234']];

        // 9 ----- Test GET method fallback on HEAD route miss ------------------------------------>

        // reuse $callback from #8

        $cases[] = ['HEAD', '/static0', $callback, 'static0'];

        // 10 ---- Test existing HEAD route used if available (no fallback) ----------------------->

            // reuse $callback from #9

        $cases[] = ['HEAD', '/static1', $callback, 'static1head'];

        // x -------------------------------------------------------------------------------------->

        return $cases;
    }

    public function provideNotFoundDispatchCases() {
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

        return $cases;
    }

    public function provideMethodNotAllowedDispatchCases() {
        $cases = [];

        // 0 -------------------------------------------------------------------------------------->

        $callback = function( $r) {
            $r->addRoute('GET', '/resource/123/456', 'handler0');
        };

        $method = 'POST';
        $uri = '/resource/123/456';
        $allowedMethods = ['GET'];

        $cases[] = [$method, $uri, $callback, $allowedMethods];

        // 1 -------------------------------------------------------------------------------------->

        $callback = function( $r) {
            $r->addRoute('GET', '/resource/123/456', 'handler0');
            $r->addRoute('POST', '/resource/123/456', 'handler1');
            $r->addRoute('PUT', '/resource/123/456', 'handler2');
        };

        $method = 'DELETE';
        $uri = '/resource/123/456';
        $allowedMethods = ['GET', 'POST', 'PUT'];

        $cases[] = [$method, $uri, $callback, $allowedMethods];

        // 2 -------------------------------------------------------------------------------------->

        $callback = function( $r) {
            $r->addRoute('GET', '/user/{name}/{id:[0-9]+}', 'handler0');
            $r->addRoute('POST', '/user/{name}/{id:[0-9]+}', 'handler1');
            $r->addRoute('PUT', '/user/{name}/{id:[0-9]+}', 'handler2');
            $r->addRoute('PATCH', '/user/{name}/{id:[0-9]+}', 'handler3');
        };

        $method = 'DELETE';
        $uri = '/user/rdlowrey/42';
        $allowedMethods = ['GET', 'POST', 'PUT', 'PATCH'];

        $cases[] = [$method, $uri, $callback, $allowedMethods];

        // x -------------------------------------------------------------------------------------->

        return $cases;
    }

}
