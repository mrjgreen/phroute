<?php
/**
 * Created by phroute.
 * Date: 8/25/2017
 * Time: 11:10 PM
 */

namespace Phroute\Phroute;


interface HandlerMethodResolverInterface
{

    /**
     * Call handler function.
     *
     * @param callable $handler
     * @param array $arguments
     * @return mixed
     */

    public function resolve($handler, $arguments);
}