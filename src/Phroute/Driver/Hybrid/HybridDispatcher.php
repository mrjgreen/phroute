<?php namespace Phroute\Phroute\Driver\Hybrid;

use Phroute\Phroute\Driver\AbstractDispatcher;
use Phroute\Phroute\Exception\HttpMethodNotAllowedException;
use Phroute\Phroute\Exception\HttpRouteNotFoundException;

class HybridDispatcher extends AbstractDispatcher
{
    private $variableRoutes;

    /**
     * @param array $staticRoutes
     * @param VariableRouteNode $variableRoutes
     */
    public function __construct(array $staticRoutes, VariableRouteNode $variableRoutes)
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
        $parts = explode('/', $uri);

        $node = $this->variableRoutes;

        $match = array();

        foreach($parts as $urlPart)
        {
            $matched = false;

            if(isset($node->static[$urlPart]))
            {
                $node = $node->static[$urlPart];

                $matched = true;
            }
            else
            {
                foreach($node->regexes as $regex => $child)
                {
                    if (preg_match('~^' . $regex . '$~', $urlPart, $matches))
                    {
                        isset($matches[1]) and $match[count($match) + 1] = $matches[1];

                        $node = $child;

                        $matched = true;
                    }
                }
            }

            if(!$matched)
            {
                throw new HttpRouteNotFoundException('Route ' . $uri . ' does not exist');
            }
        }

        if(!$node->data)
        {
            throw new HttpRouteNotFoundException('Route ' . $uri . ' does not exist');
        }

        if(!isset($node->data[$httpMethod]))
        {
            $httpMethod = $this->checkFallbacks($node->data, $httpMethod);
        }

        $data = $node->data[$httpMethod];

        $this->assignVariables($data[2], $match);

        return $data;
    }
}