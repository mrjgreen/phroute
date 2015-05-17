<?php namespace Phroute\Phroute\Driver;


use Phroute\Phroute\Exception\HttpMethodNotAllowedException;
use Phroute\Phroute\Exception\HttpRouteNotFoundException;
use Phroute\Phroute\Route;

abstract class AbstractDispatcher implements DispatcherInterface
{
    /**
     * @var array
     */
    protected $staticRoutes;

    /**
     * @param array $staticRoutes
     */
    public function __construct(array $staticRoutes)
    {
        $this->staticRoutes = $staticRoutes;
    }

    /**
     * Perform the route dispatching. Check static routes first followed by variable routes.
     *
     * @param $httpMethod
     * @param $uri
     * @return mixed
     * @throws HttpRouteNotFoundException
     */
    public function dispatchRoute($httpMethod, $uri)
    {
        if (isset($this->staticRoutes[$uri]))
        {
            return $this->dispatchStaticRoute($httpMethod, $uri);
        }

        return $this->dispatchVariableRoute($httpMethod, $uri);
    }

    /**
     * Handle the dispatching of static routes.
     *
     * @param $httpMethod
     * @param $uri
     * @return mixed
     * @throws HttpMethodNotAllowedException
     */
    protected function dispatchStaticRoute($httpMethod, $uri)
    {
        $routes = $this->staticRoutes[$uri];

        if (!isset($routes[$httpMethod]))
        {
            $httpMethod = $this->checkFallbacks($routes, $httpMethod);
        }

        return $routes[$httpMethod];
    }

    /**
     * Check fallback routes: HEAD for GET requests followed by the ANY attachment.
     *
     * @param $routes
     * @param $httpMethod
     * @return mixed
     * @throws HttpMethodNotAllowedException
     */
    protected function checkFallbacks($routes, $httpMethod)
    {
        $additional = array(Route::ANY);

        if($httpMethod === Route::HEAD)
        {
            $additional[] = Route::GET;
        }

        foreach($additional as $method)
        {
            if(isset($routes[$method]))
            {
                return $method;
            }
        }

        throw new HttpMethodNotAllowedException("Method $httpMethod is not allowed", array_keys($routes), $routes);
    }

    /**
     * @param array $matchedData
     * @param array $matches
     */
    protected function assignVariables(array &$matchedData, array $matches)
    {
        foreach (array_values($matchedData) as $i => $varName)
        {
            if(!isset($matches[$i + 1]) || $matches[$i + 1] === '')
            {
                unset($matchedData[$varName]);
            }
            else
            {
                $matchedData[$varName] = $matches[$i + 1];
            }
        }
    }

    /**
     * Handle the dispatching of variable routes.
     *
     * @param $httpMethod
     * @param $uri
     * @return mixed
     * @throws HttpMethodNotAllowedException
     * @throws HttpRouteNotFoundException
     */
    abstract protected function dispatchVariableRoute($httpMethod, $uri);
}