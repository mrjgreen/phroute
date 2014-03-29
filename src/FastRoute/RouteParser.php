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
        if (!preg_match_all(self::VARIABLE_REGEX, $route, $matches, PREG_OFFSET_CAPTURE | PREG_SET_ORDER))
        {
            return [$this->quote($route)];
        }

        $offset = 0;
        $routeData = [];
        $variables = []; 
        $i = 0;
        foreach ($matches as $set) {
          
            if ($set[0][1] > $offset)
            {
                $static = preg_split('~(/)~u', substr($route, $offset, $set[0][1] - $offset), 0, PREG_SPLIT_DELIM_CAPTURE);
                                
                foreach($static as $staticPart)
                {
                    $staticPart and $routeData[$i++] = $staticPart;
                } 
            }
            
            $varName = $set[1][0];
            
            if (isset($variables[$varName]))
            {
                throw new BadRouteException(sprintf('Cannot use the same placeholder "%s" twice', $varName));
            }
            
            $variables[$varName] = $varName;

            $regexPart = (isset($set[2]) ? trim($set[2][0]) : self::DEFAULT_DISPATCH_REGEX);
            
            $optional = substr($set[0][0], -1) === '?';

            $offset = $set[0][1] + strlen($set[0][0]);
            
            $match = '(' . $regexPart . ')';
            
            if($optional) 
            {
                if(isset($routeData[$i - 1]) && $routeData[$i - 1] === '/')
                {
                    $i--;
                    $match = '(?:/' . $match . ')';
                }
                
                 $match = $match . '?';
            }
            
            $routeData[$i++] = $match;
        }

        if ($offset != strlen($route))
        {
            $routeData[$i] = substr($route, $offset);
        }

        return [implode('',$routeData), $variables];
    }
    
    private function quote($part)
    {
        return preg_quote($part, '~');
    }

}
