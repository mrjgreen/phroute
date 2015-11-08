<?php namespace Phroute\Phroute;

use Phroute\Phroute\Exception\BadRouteException;
/**
 * Parses routes of the following form:
 *
 * "/user/{name}/{id:[0-9]+}?"
 */
class RouteParser {

    /**
     * Search through the given route looking for dynamic portions.
     *
     * Using ~ as the regex delimiter.
     *
     * We start by looking for a literal '{' character followed by any amount of whitespace.
     * The next portion inside the parentheses looks for a parameter name containing alphanumeric characters or underscore.
     *
     * After this we look for the ':\d+' and ':[0-9]+' style portion ending with a closing '}' character.
     *
     * Finally we look for an optional '?' which is used to signify an optional route.
     */
    const VARIABLE_REGEX = 
"~\{
    \s* ([a-zA-Z0-9_]*) \s*
    (?:
        : \s* ([^{]+(?:\{.*?\})?)
    )?
\}\??~x";

    /**
     * The default parameter character restriction (One or more characters that is not a '/').
     */
    const DEFAULT_DISPATCH_REGEX = '[^/]+';

    private $parts;

    private $reverseParts;
    
    private $partsCounter;
    
    private $variables;
    
    private $regexOffset;

    /**
     * Handy parameter type restrictions.
     *
     * @var array
     */
    private $regexShortcuts = array(
        ':i}'  => ':[0-9]+}',
	':a}'  => ':[0-9A-Za-z]+}',
	':h}'  => ':[0-9A-Fa-f]+}',
        ':c}'  => ':[a-zA-Z0-9+_\-\.]+}'
    );

    /**
     * Parse a route returning the correct data format to pass to the dispatch engine.
     *
     * @param $route
     * @return array
     */
    public function parse($route)
    {
        $this->reset();
        
        $route = strtr($route, $this->regexShortcuts);
        
        if (!$matches = $this->extractVariableRouteParts($route))
        {
            $reverse = array(
                'variable'  => false,
                'value'     => $route
            );

            return [[$route], array($reverse)];
        }

        foreach ($matches as $set) {

            $this->staticParts($route, $set[0][1]);
                        
            $this->validateVariable($set[1][0]);

            $regexPart = (isset($set[2]) ? trim($set[2][0]) : self::DEFAULT_DISPATCH_REGEX);
            
            $this->regexOffset = $set[0][1] + strlen($set[0][0]);

            $match = '(' . $regexPart . ')';

            $isOptional = substr($set[0][0], -1) === '?';
            
            if($isOptional)
            {
                $match = $this->makeOptional($match);
            }

            $this->reverseParts[$this->partsCounter] = array(
                'variable'  => true,
                'optional'  => $isOptional,
                'name'      => $set[1][0]
            );

            $this->parts[$this->partsCounter++] = $match;
        }

        $this->staticParts($route, strlen($route));

        return [[implode('', $this->parts), $this->variables], array_values($this->reverseParts)];
    }

    /**
     * Reset the parser ready for the next route.
     */
    private function reset()
    {
        $this->parts = array();
        
        $this->reverseParts = array();
    
        $this->partsCounter = 0;

        $this->variables = array();

        $this->regexOffset = 0;
    }

    /**
     * Return any variable route portions from the given route.
     *
     * @param $route
     * @return mixed
     */
    private function extractVariableRouteParts($route)
    {
        if(preg_match_all(self::VARIABLE_REGEX, $route, $matches, PREG_OFFSET_CAPTURE | PREG_SET_ORDER))
        {
            return $matches;
        }
    }

    /**
     * @param $route
     * @param $nextOffset
     */
    private function staticParts($route, $nextOffset)
    {
        $static = preg_split('~(/)~u', substr($route, $this->regexOffset, $nextOffset - $this->regexOffset), 0, PREG_SPLIT_DELIM_CAPTURE);

        foreach($static as $staticPart)
        {
            if($staticPart)
            {
                $quotedPart = $this->quote($staticPart);

                $this->parts[$this->partsCounter] = $quotedPart;

                $this->reverseParts[$this->partsCounter] = array(
                    'variable'  => false,
                    'value'     => $staticPart
                );
                
                $this->partsCounter++;
            }
        }
    }

    /**
     * @param $varName
     */
    private function validateVariable($varName)
    {
        if (isset($this->variables[$varName]))
        {
            throw new BadRouteException("Cannot use the same placeholder '$varName' twice");
        }

        $this->variables[$varName] = $varName;
    }

    /**
     * @param $match
     * @return string
     */
    private function makeOptional($match)
    {
        $previous = $this->partsCounter - 1;
        
        if(isset($this->parts[$previous]) && $this->parts[$previous] === '/')
        {
            $this->partsCounter--;
            $match = '(?:/' . $match . ')';
        }

        return $match . '?';
    }

    /**
     * @param $part
     * @return string
     */
    private function quote($part)
    {
        return preg_quote($part, '~');
    }
}
