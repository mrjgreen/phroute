<?php namespace Phroute\Phroute;

class Dispatcher {

    private $dispatcher;
    private $handlerResolver;
    private $filters;

    /**
     * Create a new route dispatcher.
     *
     * @param RouteDataInterface $routeData
     * @param HandlerResolverInterface $resolver
     */
    public function __construct(RouteDataInterface $routeData, HandlerResolverInterface $resolver = null)
    {
        $this->dispatcher = $routeData->getDispatcher();

        $this->filters = $routeData->getFilters();

        $this->handlerResolver = $resolver ?: new HandlerResolver();
    }

    /**
     * Dispatch a route for the given HTTP Method / URI.
     *
     * @param $httpMethod
     * @param $uri
     * @return mixed|null
     */
    public function dispatch($httpMethod, $uri)
    {
        list($handler, $filters, $vars) = $this->dispatcher->dispatchRoute($httpMethod, trim($uri, '/'));

        list($beforeFilter, $afterFilter) = $this->parseFilters($filters);

        if(($response = $this->dispatchFilters($beforeFilter)) !== null)
        {
            return $response;
        }
        
        $resolvedHandler = $this->handlerResolver->resolve($handler);
        
        $response = call_user_func_array($resolvedHandler, $vars);

        return $this->dispatchFilters($afterFilter, $response);
    }

    /**
     * Dispatch a route filter.
     *
     * @param $filters
     * @param null $response
     * @return mixed|null
     */
    private function dispatchFilters($filters, $response = null)
    {
        while($filter = array_shift($filters))
        {
        	$handler = $this->handlerResolver->resolve($filter);
        	
            if(($filteredResponse = call_user_func($handler, $response)) !== null)
            {
                return $filteredResponse;
            }
        }
        
        return $response;
    }

    /**
     * Normalise the array filters attached to the route and merge with any global filters.
     *
     * @param $filters
     * @return array
     */
    private function parseFilters($filters)
    {        
        $beforeFilter = array();
        $afterFilter = array();
        
        if(isset($filters[Route::BEFORE]))
        {
            $beforeFilter = array_intersect_key($this->filters, array_flip((array) $filters[Route::BEFORE]));
        }

        if(isset($filters[Route::AFTER]))
        {
            $afterFilter = array_intersect_key($this->filters, array_flip((array) $filters[Route::AFTER]));
        }
        
        return array($beforeFilter, $afterFilter);
    }


}
