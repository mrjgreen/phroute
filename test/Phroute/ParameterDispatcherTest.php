<?php

class TestHandler {
    public function test($one, $two){
        return [$one, $two];
    }
}

class ParameterDispatcherTest extends \PHPUnit_Framework_TestCase
{
    public function testItDispatchesStandardHandlerPassingParamsAsArgs()
    {
        $dispatcher = new \Phroute\Phroute\Dispatch\ParameterDispatcher();

        $handler = function($one, $two) use(&$result){
            return [$one, $two];
        };

        $result = $dispatcher->dispatch([$handler, []], ['one', 'two']);

        $this->assertEquals(['one', 'two'], $result);
    }

    public function testItInstantiatesStringHandlerPassingParamsAsArgs()
    {
        $dispatcher = new \Phroute\Phroute\Dispatch\ParameterDispatcher();

        $handler = ['TestHandler', 'test'];

        $result = $dispatcher->dispatch([$handler, []], ['one', 'two']);

        $this->assertEquals(['one', 'two'], $result);
    }
}