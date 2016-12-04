<?php

use Phroute\Phroute\Router;

class RouteValidRoutesTest extends \PHPUnit_Framework_TestCase {

    /**
     * Set appropriate options for the specific Dispatcher class we're testing
     */
    private function router()
    {
        return new Router();
    }

    private function dispatchTest($method, $uri, $callback, $expected)
    {
        $r = $this->router();
        $callback($r);
        $response = $r->dispatch($method, $uri);
        $this->assertEquals($expected, $response);
    }

    public function testItHandlesBaseRoute()
    {
        // Both cases should be equivalent, and should work with and without leading slash

        // Attached as '/'
        $callback = function(Router $r) {
            $r->get('/', function() {
                return 'response-GET';
            });
            $r->put('/', function() {
                return 'response-PUT';
            });
            $r->post('/', function() {
                return 'response-POST';
            });
            $r->delete('/', function() {
                return 'response-DELETE';
            });
            $r->options('/', function() {
                return 'response-OPTIONS';
            });
            $r->patch('/', function() {
                return 'response-PATCH';
            });
            $r->head('/', function() {
                return 'response-HEAD';
            });
        };

        $this->dispatchTest('GET', '', $callback, 'response-GET');
        $this->dispatchTest('GET', '/', $callback, 'response-GET');
        $this->dispatchTest('POST', '/', $callback, 'response-POST');
        $this->dispatchTest('DELETE', '/', $callback, 'response-DELETE');
        $this->dispatchTest('PUT', '/', $callback, 'response-PUT');
        $this->dispatchTest('PATCH', '/', $callback, 'response-PATCH');
        $this->dispatchTest('HEAD', '/', $callback, 'response-HEAD');
        $this->dispatchTest('OPTIONS', '/', $callback, 'response-OPTIONS');


        // Attached as ''
        $callback = function(Router $r) {
            $r->addRoute('GET', '', function() {
                return 'response';
            });
        };

        $this->dispatchTest('GET', '', $callback, 'response');
        $this->dispatchTest('GET', '/', $callback, 'response');
    }

    public function testItHandlesAnyMethodRoute()
    {
        // Both cases should be equivalent, and should work with and without leading slash

        // Attached as '/'
        $callback = function(Router $r) {
            $r->any('/', function() {
                return 'response-ANY';
            });
        };

        $this->dispatchTest('GET', '/', $callback, 'response-ANY');
        $this->dispatchTest('POST', '/', $callback, 'response-ANY');
        $this->dispatchTest('DELETE', '/', $callback, 'response-ANY');
        $this->dispatchTest('PUT', '/', $callback, 'response-ANY');
        $this->dispatchTest('PATCH', '/', $callback, 'response-ANY');
        $this->dispatchTest('HEAD', '/', $callback, 'response-ANY');
        $this->dispatchTest('OPTIONS', '/', $callback, 'response-ANY');
    }

    public function testItDispatchesCorrectStaticRoute()
    {
        $callback = function(Router $r) {
            $r->addRoute('GET', '/resource/123/456', function() {
                return '123';
            });

            $r->addRoute('GET', '/resource/abc/def', function() {
                return 'abc';
            });
        };

        $this->dispatchTest('GET', '/resource/123/456', $callback, '123');
        $this->dispatchTest('GET', 'resource/123/456', $callback, '123');

        $this->dispatchTest('GET', '/resource/abc/def', $callback, 'abc');
        $this->dispatchTest('GET', 'resource/abc/def', $callback, 'abc');
    }

    public function testItDispatchesCorrectMethod()
    {
        $callback = function(Router $r) {
            $r->addRoute('GET', '/resource/123/456', function() {
                return '123-GET';
            });

            $r->addRoute('POST', '/resource/123/456', function() {
                return '123-POST';
            });

            $r->addRoute('POST', '/resource/abc/def', function() {
                return 'abc';
            });
        };

        $this->dispatchTest('GET', '/resource/123/456', $callback, '123-GET');
        $this->dispatchTest('POST', '/resource/123/456', $callback, '123-POST');
        $this->dispatchTest('POST', '/resource/abc/def', $callback, 'abc');
    }

    public function testItDispatchesCorrectDynamicRoute()
    {
        $callback = function(Router $r) {
            $r->addRoute('GET', '/user/{name}/{id:[0-9]+}', function($name, $id) {
                return 'NAME:' . $name . ',ID:' . $id;
            });
            $r->addRoute('GET', '/user/{id:[0-9]+}', function($id) {
                return 'ID:' . $id;
            });
            $r->addRoute('GET', '/user/{name}', function($name) {
                return 'NAME:' . $name;
            });
        };

        $this->dispatchTest('GET', '/user/username123', $callback, 'NAME:username123');
        $this->dispatchTest('GET', '/user/1234', $callback, 'ID:1234');
        $this->dispatchTest('GET', '/user/username123/1234', $callback, 'NAME:username123,ID:1234');
    }

    public function testItMatchesMultiPatternRouteParts()
    {

        $callback = function(Router $r) {
            $r->addRoute('GET', '/user/{id:[0-9]+}', function() {
                return [];
            });
            $r->addRoute('GET', '/user/{id:[0-9]+}.{extension}', function($id, $extension) {
                return [$id, $extension];
            });

            $r->addRoute('GET', 'ext/{asset}.json', function($asset) {
                return $asset . ' jsonencoded';
            });
            $r->addRoute('GET', 'ext/{asset}', function($asset) {
                return $asset;
            });
        };

        $this->dispatchTest('GET', '/user/12345.svg', $callback, ['12345', 'svg']);


        $this->dispatchTest('GET', 'ext/asset', $callback, 'asset');
        $this->dispatchTest('GET', 'ext/asset.json', $callback, 'asset jsonencoded');
    }

    public function testItRespondsToHeadRequestsForGetEndpoints()
    {
        $callback = function(Router $r) {
            $r->addRoute('GET', '/user/{name}', function($name) {
                return $name;
            });
            $r->addRoute('GET', '/static0', function() {
                return 'response-GET';
            });
            $r->addRoute('HEAD', '/static1', function() {
                return 'response-HEAD';
            });
        };

        // Dynamic
        $this->dispatchTest('HEAD', '/user/username', $callback, 'username');

        // Static GET
        $this->dispatchTest('HEAD', '/static0', $callback, 'response-GET');

        // Static HEAD
        $this->dispatchTest('HEAD', '/static1', $callback, 'response-HEAD');
    }

    public function testItAllowsOptionalParams()
    {
        $callback = function(Router $r) {
            $r->addRoute('GET', '/user/{name}/{id:[0-9]+}?', function($name, $id = null) {
                return [$name, $id];
            });
        };

        // With Param
        $this->dispatchTest('GET', '/user/someuser123', $callback, ['someuser123', null]);

        // Without Param
        $this->dispatchTest('GET', '/user/someuser123/23', $callback, ['someuser123', '23']);
    }

    public function testItAllowsMultipleOptionalParams()
    {
        $callback = function(Router $r) {
            $r->addRoute('GET', '/user/{name}/{id:[0-9]+}?/{other}?', function($name, $id = null, $other = null) {
                return [$name, $id, $other];
            });
        };

        // With no params
        $this->dispatchTest('GET', '/user/someuser123', $callback, ['someuser123', null, null]);

        // With 1 param
        $this->dispatchTest('GET', '/user/someuser123/23', $callback, ['someuser123', '23', null]);

        // With 2 params
        $this->dispatchTest('GET', '/user/someuser123/23/blah', $callback, ['someuser123', '23', 'blah']);

    }

    public function testItAllowsPartialOptionalParameters()
    {
        // Required
        $callback = function(Router $r) {
            $r->addRoute('GET', '/user/random_{name}', function($name) {
                return $name;
            });
        };

        $this->dispatchTest('GET', '/user/random_username', $callback, 'username');


        // Optional
        $callback = function(Router $r) {
            $r->addRoute('GET', '/user/random_{name}?', function($name = 'default') {
                return $name;
            });
        };

        $this->dispatchTest('GET', '/user/random_username', $callback, 'username');
        $this->dispatchTest('GET', '/user/random_', $callback, 'default');
    }

    public function testOptionalBaseParam()
    {
        $callback = function(Router $r) {
            $r->addRoute('GET', '{name}?', function($name = null) {
                return $name;
            });
        };


        $this->dispatchTest('GET', 'somename', $callback, 'somename');
        $this->dispatchTest('GET', '/', $callback, null);
    }

    public function testRouteParameterTypeShortCuts()
    {
        $callback = function(Router $r) {
            $r->addRoute('GET', '/user/{id:i}', function($id) {
                return $id;
            });
            $r->addRoute('GET', '/user1/{idname:a}', function($idname) {
                return array($idname);
            });
            $r->addRoute('GET', '/user2/{hexcode:h}', function($hexcode) {
                return array($hexcode);
            });
            $r->addRoute('GET', '/user3/{id:i}/{hexcode:h}?', function($id, $hexcode = null) {
                return array($id, $hexcode);
            });
            $r->addRoute('GET', '/user4/{slug:c}', function($slug) {
                return array($slug);
            });
        };

        $this->dispatchTest('GET', '/user/21', $callback, '21');
        $this->dispatchTest('GET', '/user1/abcdezzASd123', $callback, ['abcdezzASd123']);
        $this->dispatchTest('GET', '/user2/abcde123', $callback, ['abcde123']);
        $this->dispatchTest('GET', '/user3/21/abcde123', $callback, ['21','abcde123']);
        $this->dispatchTest('GET', '/user3/21', $callback, ['21', null]);
        $this->dispatchTest('GET', '/user4/test_something-123', $callback, ['test_something-123']);
    }

    public function testItDispatchesComplexRegexes()
    {

        $callback = function(Router $r) {
            $r->addRoute('GET', 'server/{ip:\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}}/{name}/{newname}?', function($ip, $name, $newname = null) {
                return trim("$ip $name $newname");
            });

            $r->addRoute('GET', 'date/{year:\d{4}}/{month:\d+}?', function($year, $month) {
                return trim("$year $month");
            });

            $r->addRoute('GET', 'date/{year:\d{4}}/{month:[A-Za-z]+}', function($year, $month) {
                return trim("$year $month");
            });
        };

        $this->dispatchTest('GET', 'server/10.10.10.10/server1', $callback, '10.10.10.10 server1');
        $this->dispatchTest('GET', 'server/0.0.0.0/server2', $callback, '0.0.0.0 server2');
        $this->dispatchTest('GET', 'server/123.2.23.111/server3/server4', $callback, '123.2.23.111 server3 server4');

        $this->dispatchTest('GET', 'date/1990/05', $callback, '1990 05');

        $this->dispatchTest('GET', 'date/2010/jan', $callback, '2010 jan');
    }

    #TODO - resolve this or document it
//    public function testOptionalParamIsntOverridenByStaticPost()
//    {
//        $callback = function(Router $r) {
//            $r->addRoute('POST', 'test', function() {
//                return '';
//            });
//            $r->addRoute('GET', 'test/{year}?', function($param = null) {
//                return $param;
//            });
//        };
//
//        $this->dispatchTest('GET', 'test', $callback, null);
//        $this->dispatchTest('GET', 'test/123', $callback, '123');
//        $this->dispatchTest('POST', 'test', $callback, '');
//    }


    // Various tests for deep level of optional params - these are probably already
    // covered about, but have left in for completeness
    public function testItRunsAllOptionalParamCases()
    {
        $callback = function(Router $r) {
            $r->addRoute('GET', '/user/{id}?/{id2}?/{id3}?', function() {
                return 'first';
            });
            $r->addRoute('GET', '/user2/{id}?', function() {
                return 'second';
            });
            $r->addRoute('GET', '/user3/{id}?', function() {
                return 'third';
            });
            $r->addRoute('GET', '/user4/{id}?/{id2}?/{id3}?', function() {
                return 'fourth';
            });
        };

        $this->dispatchTest('GET', '/user/21', $callback, 'first');
        $this->dispatchTest('GET', '/user2/abcdezzASd123', $callback, 'second');
        $this->dispatchTest('GET', '/user2/abcde123', $callback, 'second');
        $this->dispatchTest('GET', '/user/21/abcde123', $callback, 'first');
        $this->dispatchTest('GET', '/user2/21', $callback, 'second');
        $this->dispatchTest('GET', '/user3/abcdezzASd123', $callback, 'third');
        $this->dispatchTest('GET', '/user3/abcde123', $callback, 'third');
        $this->dispatchTest('GET', '/user3/21', $callback, 'third');
        $this->dispatchTest('GET', '/user4/abcdezzASd123', $callback, 'fourth');
        $this->dispatchTest('GET', '/user4/abcde123', $callback, 'fourth');
        $this->dispatchTest('GET', '/user4/21', $callback, 'fourth');
    }
}
