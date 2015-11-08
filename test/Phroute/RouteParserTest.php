<?php


use Phroute\Phroute\Route;
use Phroute\Phroute\RoutePart;

class RouteParserTest extends \PHPUnit_Framework_TestCase
{

    /**
     * @dataProvider routeProvider
     * @param $route
     * @param $expected
     */
    public function testItParsesRoutes($route, $expected)
    {
        $p = new \Phroute\Phroute\RouteParser();

        $parsed = $p->parse($route);

        $this->assertEquals($expected, $parsed->getParts());
    }

    public function routeProvider()
    {
        return [
            ['/', [
                new RoutePart('', false, false),
            ]],
            ['/some/{thing}', [
                new RoutePart('some', false, false),
                new RoutePart('/', false, false),
                new RoutePart('([^/]+)', 'thing', false)
            ]],
            ['/some/{thing:i}', [
                new RoutePart('some', false, false),
                new RoutePart('/', false, false),
                new RoutePart('([0-9]+)', 'thing', false)
            ]],
            ['/some/{thing:i}?', [
                new RoutePart('some', false, false),
                new RoutePart('(?:/([0-9]+))?', 'thing', true)
            ]]
        ];
    }
}