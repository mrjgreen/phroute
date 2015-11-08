<?php


class RouteCollectorTest extends \PHPUnit_Framework_TestCase
{
    public function r()
    {
        return new \Phroute\Phroute\RouteCollector(new \Phroute\Phroute\Router\FastRoute());
    }

    public function testItDispatchesIndexRoutes()
    {
        $r = $this->r();

        $r->get('/', function() {
            return 'response';
        });

        $test1 = $r->dispatch('GET', '/');
        $test2 = $r->dispatch('GET', '');

        $this->assertEquals('response', $test1);
        $this->assertEquals('response', $test2);
    }

    public function testItDispatchesStaticRoutes()
    {
        $r = $this->r();

        $r->get('/some/thing', function() {
            return 'blah';
        });

        $thing = $r->dispatch('GET', 'some/thing');

        $this->assertEquals('blah', $thing);
    }

    public function testItDispatchesDynamicRoutes()
    {
        $r = $this->r();

        $r->get('/some/{thing}/{else}', function($thing, $else) {
            return [$thing, $else];
        });

        $thing = $r->dispatch('GET', 'some/foo/bar');

        $this->assertEquals(['foo', 'bar'], $thing);
    }
}