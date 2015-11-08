<?php

namespace Phroute\Phroute\Dispatcher;

use Phroute\Phroute\RouteCollector;
use Phroute\Phroute\RouteParser;
use Phroute\Phroute\Dispatcher;
use Phroute\Phroute\Route;


class RouteCollectorValidTest extends \PHPUnit_Framework_TestCase {

    /**
     * Set appropriate options for the specific Dispatcher class we're testing
     */
    private function router()
    {
        return new RouteCollector();
    }

    /**
     * @dataProvider providerValidRoutes
     */
    public function testItDispatchesValidRoutes($method, $uri, $callback, $expected)
    {
        $r = $this->router();
        $callback($r);
        $response = $r->dispatch($method, $uri);
        $this->assertEquals($expected, $response);
    }

    public function providerValidRoutes()
    {
        $cases = [];
        
         // 0 -------------------------------------------------------------------------------------->

        $callback = function($r) {
            $r->addRoute('GET', '/', function() {
                return true;
            });
        };
        
        $cases[] = ['GET', '', $callback, true];

        $cases[] = ['GET', '/', $callback, true];
        
        
        $callback = function($r) {
            $r->addRoute('GET', '', function() {
                return true;
            });
        };
        
        $cases[] = ['GET', '', $callback, true];

        $cases[] = ['GET', '/', $callback, true];

        // 0 -------------------------------------------------------------------------------------->

        $callback = function($r) {
            $r->addRoute('GET', '/resource/123/456', function() {
                return true;
            });
        };

        $cases[] = ['GET', '/resource/123/456', $callback, true];
        
        
        
        $callback = function($r) {
            $r->addRoute('GET', 'resource/123/456', function() {
                return true;
            });
        };

        $cases[] = ['GET', 'resource/123/456', $callback, true];
        
        
        $callback = function($r) {
            $r->addRoute('GET', 'resource/123/456', function() {
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
        
        $callback = function($r) {
            $r->addRoute('GET', '{name}?', function($name = null) {
                return $name;
            });
        };

        //19
        $cases[] = ['GET', 'rdlowrey', $callback, 'rdlowrey'];
         //20
        $cases[] = ['GET', '/', $callback, null];
        
        // 11 -------------------------------------------------------------------------------------->
        // Test shortcuts parameter
        $callback = function($r) {
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

        $cases[] = ['GET', '/user/21', $callback, '21'];
        $cases[] = ['GET', '/user1/abcdezzASd123', $callback, array('abcdezzASd123')];
        $cases[] = ['GET', '/user2/abcde123', $callback, array('abcde123')];
        $cases[] = ['GET', '/user3/21/abcde123', $callback, array('21','abcde123')];
        $cases[] = ['GET', '/user3/21', $callback, array('21', null)];
        $cases[] = ['GET', '/user4/test_something-123', $callback, array('test_something-123')];
        
        
        // 11 -------------------------------------------------------------------------------------->
        // Test shortcuts parameter
        $callback = function($r) {
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

        $cases[] = ['GET', '/user/21', $callback, 'first'];
        $cases[] = ['GET', '/user2/abcdezzASd123', $callback, 'second'];
        $cases[] = ['GET', '/user2/abcde123', $callback, 'second'];
        $cases[] = ['GET', '/user/21/abcde123', $callback, 'first'];
        $cases[] = ['GET', '/user2/21', $callback, 'second'];
        $cases[] = ['GET', '/user3/abcdezzASd123', $callback, 'third'];
        $cases[] = ['GET', '/user3/abcde123', $callback, 'third'];
        $cases[] = ['GET', '/user3/21', $callback, 'third'];
        $cases[] = ['GET', '/user4/abcdezzASd123', $callback, 'fourth'];
        $cases[] = ['GET', '/user4/abcde123', $callback, 'fourth'];
        $cases[] = ['GET', '/user4/21', $callback, 'fourth'];
        
        // 11 -------------------------------------------------------------------------------------->
        // Test shortcuts parameter
        $callback = function($r) {
            $r->addRoute('GET', 'ext/{asset}.json', function($asset) {
                return $asset . ' jsonencoded';
            });
            $r->addRoute('GET', 'ext/{asset}', function($asset) {
                return $asset;
            });
        };

        $cases[] = ['GET', 'ext/asset', $callback, 'asset'];
        $cases[] = ['GET', 'ext/asset.json', $callback, 'asset jsonencoded'];

        // 12 -------------------------------------------------------------------------------------->
        // Test \d{3,4} style quantifiers
        $callback = function($r) {
            $r->addRoute('GET', 'server/{ip:\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}}/{name}/{newname}?', function($ip, $name, $newname = null) {
                return trim("$ip $name $newname");
            });
        };

        $cases[] = ['GET', 'server/10.10.10.10/server1', $callback, '10.10.10.10 server1'];
        $cases[] = ['GET', 'server/0.0.0.0/server2', $callback, '0.0.0.0 server2'];
        $cases[] = ['GET', 'server/123.2.23.111/server3/server4', $callback, '123.2.23.111 server3 server4'];

        // 13 -------------------------------------------------------------------------------------->
        // Test \d{3,4} style quantifiers
        $callback = function($r) {
            $r->addRoute('GET', 'date/{year:\d{4}}/{month:\d+}?', function($year, $month) {
                return trim("$year $month");
            });
        };

        $cases[] = ['GET', 'date/1990/05', $callback, '1990 05'];

        // 14 -------------------------------------------------------------------------------------->
        // Test \d{3,4} style quantifiers
        $callback = function($r) {
            $r->addRoute('GET', 'date/{year:\d{4}}/{month:\d{2}}', function($year, $month) {
                return trim("$year $month");
            });
        };

        $cases[] = ['GET', 'date/2010/06', $callback, '2010 06'];

        return $cases;
    }
}
