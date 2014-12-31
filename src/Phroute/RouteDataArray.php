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

    /**
     * @return array
     */
    public function getData()
    {
        return [$this->staticRoutes, $this->variableRoutes];
    }

    /**
     * @param RouteCollector $collector
     * @return static
     */
    public static function fromRouteCollector(RouteCollector $collector)
    {
        list($staticRoute, $variableRoutes) = $collector->getData();

        return new static($staticRoute, $variableRoutes);
    }
}
