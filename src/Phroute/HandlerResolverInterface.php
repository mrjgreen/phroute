<?php namespace Phroute\Router;

interface HandlerResolverInterface {
	
	/**
	 * Create an instance of the given handler.
	 *
	 * @param $handler
	 * @return array
	 */
	
	public function resolve($handler);
}
