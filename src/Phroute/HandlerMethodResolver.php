<?php
/**
 * Created by phroute.
 * Date: 8/25/2017
 * Time: 11:13 PM
 */

namespace Phroute\Phroute;


class HandlerMethodResolver implements HandlerMethodResolverInterface
{

    /**
     * Call method from instance of the given handler.
     *
     * @param callable $handler
     * @param array $arguments
     * @return mixed
     */
    public function resolve($handler, $arguments)
    {
        return call_user_func_array($handler, $arguments);
    }
}