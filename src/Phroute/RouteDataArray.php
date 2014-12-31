<?php namespace Phroute;

class RouteDataArray implements RouteDataProviderInterface {


    /**
     * @var array
     */
    private $variableRoutes;

    /**
     * @var array
     */
    private $staticRoutes;

    /**
     * @param array $staticRoutes
     * @param array $variableRoutes
     */
    public function __construct(array $staticRoutes, array $variableRoutes)
    {
        $this->staticRoutes = $staticRoutes;

        $this->variableRoutes = $variableRoutes;
    }

    public function getData()
    {
        return [$this->staticRoutes, $this->variableRoutes];
    }
}
