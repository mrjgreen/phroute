<?php namespace Phroute\Phroute\Dispatch;

class ParameterDispatcher implements DispatcherInterface {

    /**
     * Create an instance of the given handler and calls it with any variables from the URL.
     *
     * @param $handler
     * @param $variables
     * @return mixed
     */
    public function dispatch($handler, $variables)
    {
        list($handler, $parameters) = $handler;

        if(is_array($handler) && is_string($handler[0]))
        {
            $handler[0] = new $handler[0];
        }

        return call_user_func_array($handler, $variables);
    }
}