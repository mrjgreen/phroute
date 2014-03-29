<?php

namespace FastRoute;

use FastRoute\Exception\BadRouteException;

/**
 * Parses routes of the following form:
 *
 * "/user/{name}/{id:[0-9]+}"
 */
class RouteParser {

    const VARIABLE_REGEX = <<<'REGEX'
~\{
    \s* ([a-zA-Z][a-zA-Z0-9_]*) \s*
    (?:
        : \s* ([^{}]*(?:\{(?-1)\}[^{}*])*)
    )?
\}\??~x
REGEX;
    const DEFAULT_DISPATCH_REGEX = '[^/]+';

    public function parse($route)
    {
        if (!preg_match_all(
                        self::VARIABLE_REGEX, $route, $matches, PREG_OFFSET_CAPTURE | PREG_SET_ORDER
                ))
        {
            return [$route];
        }

        $offset = 0;
        $routeData = [];
        
        $optional = false;
        
        foreach ($matches as $i => $set) {
            if (substr($set[0][0], -1) === '?')
            {
                $optional = true;
            }
            elseif($optional)
            {
                throw new BadRouteException('Parameter ' . ($i) . ' in route ' . $route . ' is optional. All following parameters must also be optional.');
            }
                        
            if ($set[0][1] > $offset)
            {
                $static = explode('/', substr($route, $offset, $set[0][1] - $offset));
                
                $first = array_shift($static);
                
                if($first)
                {
                    $routeData[] = $first;
                }
                
                foreach($static as $staticPart)
                {
                    $routeData[] = '/';
                    $staticPart and $routeData[] = $staticPart;
                    
                }
                
            }

            $routeData[] = [
                $set[1][0],
                (isset($set[2]) ? trim($set[2][0]) : self::DEFAULT_DISPATCH_REGEX),
                $optional
            ];
            
            
            
            $offset = $set[0][1] + strlen($set[0][0]);
        }

        if ($offset != strlen($route))
        {
            $routeData[] = substr($route, $offset);
        }

        return $routeData;
    }

}
