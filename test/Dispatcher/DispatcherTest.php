<?php

namespace FastRoute\Dispatcher;

use FastRoute\RouteCollector;
use FastRoute\RouteParser;
use FastRoute\DataGenerator;
use FastRoute\Dispatcher;

class Test {
    
    public function route()
    {
        return 'testRoute';
    }
    
    public function anyIndex()
    {
        return 'testRoute';
    }
    
    public function anyTest()
    {
        return 'testRoute';
    }
    
    public function getTest()
    {
        return 'testRoute';
    }
    
    public function postTest()
    {
        return 'testRoute';
    }
    
    public function putTest()
    {
        return 'testRoute';
    }

    public function deleteTest()
    {
        return 'testRoute';
    }
    
    public function headTest()
    {
        return 'testRoute';
    }
    
    public function optionsTest()
    {
        return 'testRoute';
    }
    
    
}

class DispatcherTest extends \PHPUnit_Framework_TestCase {

    /**
     * Set appropriate options for the specific Dispatcher class we're testing
     */
    private function router()
    {
        return new RouteCollector(new RouteParser, new DataGenerator);
    }

    private function dispatch($router, $method, $uri)
    {
        return (new Dispatcher($router))->dispatch($method, $uri);
    }

    /**
     * @dataProvider provideFoundDispatchCases
     */
    public function testFoundDispatches($method, $uri, $callback, $expected)
    {
        $r = $this->router();
        $callback($r);
        $response = $this->dispatch($r, $method, $uri);
        $this->assertEquals($expected, $response);
    }

    /**
     * @dataProvider provideNotFoundDispatchCases
     * @expectedException \FastRoute\Exception\HttpRouteNotFoundException
     * @expectedExceptionMessage does not exist
     */
    public function testNotFoundDispatches($method, $uri, $callback)
    {
        $r = $this->router();
        $callback($r);
        $this->dispatch($r, $method, $uri);
    }

    /**
     * @dataProvider provideMethodNotAllowedDispatchCases
     * @expectedException \FastRoute\Exception\HttpMethodNotAllowedException
     * @expectedExceptionMessage Allowed routes
     */
    public function testMethodNotAllowedDispatches($method, $uri, $callback)
    {
        $r = $this->router();
        $callback($r);
        $this->dispatch($r, $method, $uri);
    }
    
    public function testStringObjectIsDispatched()
    {
        $r = $this->router();
        
        $r->addRoute('GET', '/foo', array(__NAMESPACE__.'\\Test','route'));
        
        $response = $this->dispatch($r, 'GET', '/foo');
        
        $this->assertEquals('testRoute',$response);
    }

    /**
     * @expectedException \FastRoute\Exception\BadRouteException
     * @expectedExceptionMessage Cannot use the same placeholder "test" twice
     */
    public function testDuplicateVariableNameError()
    {
        $this->router()->addRoute('GET', '/foo/{test}/{test:\d+}', function() {
            
        });
    }

    /**
     * @expectedException \FastRoute\Exception\BadRouteException
     * @expectedExceptionMessage Cannot register two routes matching "/user/([^/]+)" for method "GET"
     */
    public function testDuplicateVariableRoute()
    {
        $r = $this->router();
        $r->addRoute('GET', '/user/{id}', function() {
            
        }); // oops, forgot \d+ restriction ;)
        $r->addRoute('GET', '/user/{name}', function() {
            
        });
    }

    /**
     * @expectedException \FastRoute\Exception\BadRouteException
     * @expectedExceptionMessage Cannot register two routes matching "/user" for method "GET"
     */
    public function testDuplicateStaticRoute()
    {
        $r = $this->router();
        $r->addRoute('GET', '/user', function() {
            
        });
        $r->addRoute('GET', '/user', function() {
            
        });
    }

    /**
     * @expectedException \FastRoute\Exception\BadRouteException
     * @expectedExceptionMessage Static route "/user/nikic" is shadowed by previously defined variable route "/user/([^/]+)" for method "GET"
     */
    public function testShadowedStaticRoute()
    {
        $r = $this->router();
        $r->addRoute('GET', '/user/{name}', function() {
            
        });
        $r->addRoute('GET', '/user/nikic', function() {
            
        });
    }

    public function testBeforeFilters()
    {
        $r = $this->router();

        $dispatchedFilter = false;
        
        $r->filter('test', function() use(&$dispatchedFilter){
            $dispatchedFilter = true;
        });

        $r->addRoute('GET', '/user', function() {
            return 'dispatched';
        }, array('before' => 'test'));

        $this->assertEquals('dispatched', $this->dispatch($r, 'GET', '/user'));
        
        $this->assertTrue($dispatchedFilter);
    }

    public function testBeforeFilterCancels()
    {
        $r = $this->router();
        
        $r->filter('test', function(){            
            return 'cancel';
        });

        $r->addRoute('GET', '/user', function() {
            return 'dispatched';
        }, array('before' => 'test'));

        $this->assertEquals('cancel', $this->dispatch($r, 'GET', '/user'));
    }
    
    
    public function testAfterFilters()
    {
        $r = $this->router();

        $dispatchedFilter = false;
        
        $r->filter('test', function() use(&$dispatchedFilter){
            $dispatchedFilter = true;
        });

        $r->addRoute('GET', '/user', function() {
            
        }, array('after' => 'test'));

        $this->dispatch($r, 'GET', '/user');
        
        $this->assertTrue($dispatchedFilter);
    }

    public function testValidMethods()
    {
        $this->assertEquals(array(
            \FastRoute\Route::ANY,
            \FastRoute\Route::GET,
            \FastRoute\Route::POST,
            \FastRoute\Route::PUT,
            \FastRoute\Route::DELETE,
            \FastRoute\Route::HEAD,
            \FastRoute\Route::OPTIONS,
        ), $this->router()->getValidMethods());
    }
    
    public function testRestfulControllerMethods()
    {
        
        $r = $this->router();
        
        $r->controller('/user', __NAMESPACE__ . '\\Test');
        
        $data = $r->getData();
        
        $this->assertEquals($r->getValidMethods(), array_keys($data[0]['/user/test']));
        
        $this->assertEquals(array(\FastRoute\Route::ANY), array_keys($data[0]['/user']));
        $this->assertEquals(array(\FastRoute\Route::ANY), array_keys($data[0]['/user/index']));
    }
    
    public function testRestfulMethods()
    {
        
        $r = $this->router();
        
        $methods = $r->getValidMethods();
        
        foreach($methods as $method)
        {
            $r->$method('/user','callback');
        }
        
        $data = $r->getData();
        
        $this->assertEquals($methods, array_keys($data[0]['/user']));
    }
    
    public function provideFoundDispatchCases()
    {
        $cases = [];

        // 0 -------------------------------------------------------------------------------------->

        $callback = function($r) {
            $r->addRoute('GET', '/resource/123/456', function() {
                return true;
            });
        };

        $cases[] = ['GET', '/resource/123/456', $callback, true];

        // 1 -------------------------------------------------------------------------------------->

        $callback = function($r) {
            $r->addRoute('GET', '/handler0', function() {
                
            });
            $r->addRoute('GET', '/handler1', function() {
                
            });
            $r->addRoute('GET', '/handler2', function() {
                return true;
            });
        };
        $cases[] = ['GET', '/handler2', $callback, true];

        // 2 -------------------------------------------------------------------------------------->

        $callback = function($r) {
            $r->addRoute('GET', '/user/{name}/{id:[0-9]+}', function($name, $id) {
                return [$name, $id];
            });
            $r->addRoute('GET', '/user/{id:[0-9]+}', function($id) {
                return $id;
            });
            $r->addRoute('GET', '/user/{name}', function($name) {
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
            $r->addRoute('GET', '/user/{id:[0-9]+}', function() {
                
            });
            $r->addRoute('GET', '/user/12345/extension', function() {
                
            });
            $r->addRoute('GET', '/user/{id:[0-9]+}.{extension}', function($id, $extension) {
                return [$id, $extension];
            });
        };

        $cases[] = ['GET', '/user/12345.svg', $callback, ['12345', 'svg']];

        // 7 ----- Test GET method fallback on HEAD route miss ------------------------------------>

        $callback = function($r) {
            $r->addRoute('GET', '/user/{name}', function($name) {
                return $name;
            });
            $r->addRoute('GET', '/user/{name}/{id:[0-9]+}', function($name, $id) {
                return [$name, $id];
            });
            $r->addRoute('GET', '/static0', function() {
                return 'static0';
            });
            $r->addRoute('GET', '/static1', function() {
                
            });
            $r->addRoute('HEAD', '/static1', function() {
                return 'static1head';
            });
        };

        $cases[] = ['HEAD', '/user/rdlowrey', $callback, 'rdlowrey'];

        // 8 ----- Test GET method fallback on HEAD route miss ------------------------------------>
        // reuse $callback from #7
        $cases[] = ['HEAD', '/user/rdlowrey/1234', $callback, ['rdlowrey', '1234']];

        // 9 ----- Test GET method fallback on HEAD route miss ------------------------------------>
        // reuse $callback from #8

        $cases[] = ['HEAD', '/static0', $callback, 'static0'];

        // 10 ---- Test existing HEAD route used if available (no fallback) ----------------------->
        // reuse $callback from #9

        $cases[] = ['HEAD', '/static1', $callback, 'static1head'];

        // x -------------------------------------------------------------------------------------->

        
        // 11 -------------------------------------------------------------------------------------->
        // Test optional parameter
        $callback = function($r) {
            $r->addRoute('GET', '/user/{name}/{id:[0-9]+}?', function($name, $id = null) {
                return [$name, $id];
            });
        };

        $cases[] = ['GET', '/user/rdlowrey', $callback, array('rdlowrey', null)];
        
        // 12
        $cases[] = ['GET', '/user/rdlowrey/23', $callback, array('rdlowrey', '23')];
        
        // 13 -------------------------------------------------------------------------------------->
        // Test multiple optional parameters
        $callback = function($r) {
            $r->addRoute('GET', '/user/{name}/{id:[0-9]+}?/{other}?', function($name, $id = null, $other = null) {
                return [$name, $id, $other];
            });
        };

        $cases[] = ['GET', '/user/rdlowrey', $callback, array('rdlowrey', null, null)];
        
        // 14
        $cases[] = ['GET', '/user/rdlowrey/23', $callback, array('rdlowrey', '23', null)];
        
        //15
        $cases[] = ['GET', '/user/rdlowrey/23/blah', $callback, array('rdlowrey', '23', 'blah')];
        

        $callback = function($r) {
            $r->addRoute('GET', '/user/random_{name}', function($name) {
                return $name;
            });
        };

        //16
        $cases[] = ['GET', '/user/random_rdlowrey', $callback, 'rdlowrey'];
        
        
        $callback = function($r) {
            $r->addRoute('GET', '/user/random_{name}?', function($name = null) {
                return $name;
            });
        };

        //17
        $cases[] = ['GET', '/user/random_rdlowrey', $callback, 'rdlowrey'];
         //18
        $cases[] = ['GET', '/user/random_', $callback, null];
        
        return $cases;
    }

    public function provideNotFoundDispatchCases()
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
        
        return $cases;
    }

    public function provideMethodNotAllowedDispatchCases()
    {
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
