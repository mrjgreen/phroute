<?php

use Phroute\Phroute\RouteParser;
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
        $p = new RouteParser();

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

    /**
     * @expectedException \Phroute\Phroute\Exception\BadRouteException
     * @expectedExceptionMessage  Cannot use the same placeholder 'foo' twice
     */
    public function testItThrowsExceptionForDuplicateName()
    {
        $parser = new RouteParser();

        $parser->parse('{foo}/{foo}');
    }
}