<?php namespace Phroute\Phroute\Dispatch;

interface DispatcherInterface {

    /**
     * @param $handler
     * @param $variables
     * @return mixed
     */
    public function dispatch($handler, $variables);
}