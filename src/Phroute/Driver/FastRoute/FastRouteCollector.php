<?php namespace Phroute\Phroute\Driver\FastRoute;

use Phroute\Phroute\Driver\Regex\RegexCollector;

class FastRouteCollector extends RegexCollector
{
    /**
     *
     */
    const APPROX_CHUNK_SIZE = 10;

    /**
     * @return FastRouteDispatcher
     */
    public function getDispatcher()
    {
        $variableRoutes = empty($this->regexToRoutesMap) ? [] : $this->generateVariableRouteData();

        return new FastRouteDispatcher($this->staticRoutes, $variableRoutes);
    }

    /**
     * @return array
     */
    private function generateVariableRouteData()
    {
        $chunkSize = $this->computeChunkSize(count($this->regexToRoutesMap));
        $chunks = array_chunk($this->regexToRoutesMap, $chunkSize, true);
        return array_map([$this, 'processChunk'], $chunks);
    }

    /**
     * @param $count
     * @return float
     */
    private function computeChunkSize($count)
    {
        $numParts = max(1, round($count / self::APPROX_CHUNK_SIZE));
        return ceil($count / $numParts);
    }

    /**
     * @param $regexToRoutesMap
     * @return array
     */
    private function processChunk($regexToRoutesMap)
    {
        $routeMap = [];
        $regexes = [];
        $numGroups = 0;
        foreach ($regexToRoutesMap as $regex => $routes) {
            $firstRoute = reset($routes);
            $numVariables = count($firstRoute[2]);
            $numGroups = max($numGroups, $numVariables);

            $regexes[] = $regex . str_repeat('()', $numGroups - $numVariables);

            foreach ($routes as $httpMethod => $route) {
                $routeMap[$numGroups + 1][$httpMethod] = $route;
            }

            $numGroups++;
        }

        $regex = '~^(?|' . implode('|', $regexes) . ')$~';
        return ['regex' => $regex, 'routeMap' => $routeMap];
    }
}