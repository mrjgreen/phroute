<?php namespace Phroute\Phroute;

use Phroute\Phroute\Driver\DispatcherInterface;

class RouteDataArray implements RouteDataInterface {

    /**
     * @var DispatcherInterface
     */
    private $dispatcher;

    /**
     * @var array
     */
    private $filters;

    /**
     * @param DispatcherInterface $dispatcher
     * @param array $filters
     */
    public function __construct(DispatcherInterface $dispatcher, array $filters)
    {
        $this->dispatcher = $dispatcher;

        $this->filters = $filters;
    }

    /**
     * @return DispatcherInterface
     */
    public function getDispatcher()
    {
        return $this->dispatcher;
    }

    /**
     * @return mixed
     */
    public function getFilters()
    {
        return $this->filters;
    }
}
