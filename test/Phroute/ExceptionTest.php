<?php


class ExceptionTest extends \PHPUnit_Framework_TestCase
{

    public function testStatusCodeAndHeadersAreAvailableIn404Exception()
    {
        $error404 = new \Phroute\Phroute\Exception\HttpRouteNotFoundException();

        $this->assertEquals(404, $error404->getStatusCode());
        $this->assertEquals([], $error404->getHeaders());
    }

    public function testStatusCodeAndHeadersAreAvailableIn405Exception()
    {
        $error404 = new \Phroute\Phroute\Exception\HttpMethodNotAllowedException(['GET','OPTIONS']);

        $this->assertEquals(405, $error404->getStatusCode());
        $this->assertEquals(['Allow' => 'GET, OPTIONS'], $error404->getHeaders());
    }
}