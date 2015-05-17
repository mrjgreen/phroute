<?php namespace Phroute\Phroute\Driver\Regex;

use Phroute\Phroute\Driver\AbstractDispatcher;
use Phroute\Phroute\Exception\HttpMethodNotAllowedException;
use Phroute\Phroute\Exception\HttpRouteNotFoundException;

class RegexDispatcher extends AbstractDispatcher
{
    protected $variableRoutes;

    /**
     * @param array $staticRoutes
     * @param array $variableRoutes
     */
    public function __construct(array $staticRoutes, array $variableRoutes)
    {
        parent::__construct($staticRoutes);

        $this->variableRoutes = $variableRoutes;
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
    protected function dispatchVariableRoute($httpMethod, $uri)
    {
        foreach ($this->variableRoutes as $regex => $data)
        {
            if (preg_match('~^' . $regex . '$~', $uri, $matches))
            {
                if (!isset($data[$httpMethod]))
                {
                    $httpMethod = $this->checkFallbacks($data, $httpMethod);
                }

                $this->assignVariables($data[$httpMethod][2], $matches);

                return $data[$httpMethod];
            }
        }

        throw new HttpRouteNotFoundException('Route ' . $uri . ' does not exist');
    }
}