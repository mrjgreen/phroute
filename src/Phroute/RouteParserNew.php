<?php namespace Phroute\Phroute;

use Phroute\Phroute\Exception\BadRouteException;
/**
 * Parses routes of the following form:
 *
 * "/user/{name}/{id:[0-9]+}?"
 */

class RoutePart
{
    public $value;

    public $variable;

    public $optional;

    public function __construct($value, $variable = false, $optional = false)
    {
        $this->value = $value;

        $this->variable = $variable;

        $this->optional = $variable && $optional;
    }

    public function __toString()
    {
        return $this->value;
    }
}

class RouteParserNew {

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
     * @return Route
     */
    public function parse($route)
    {
        $route = strtr($route, $this->regexShortcuts);

        $parts = explode('/', $route);

        $variables = array();
        $finalParts = array();
        $reverseParts = array();

        foreach ($parts as $i => $part) {

            if($matches = $this->extractVariableRouteParts($part))
            {
                foreach($matches as $set)
                {
                    $varName = $set[1][0];

                    if (isset($variables[$varName]))
                    {
                        throw new BadRouteException("Cannot use the same placeholder '$varName' twice");
                    }

                    $variables[$varName] = $varName;

                    $regexPart = (isset($set[2]) ? trim($set[2][0]) : self::DEFAULT_DISPATCH_REGEX);

                    $match = '(' . $regexPart . ')';

                    $isOptional = substr($set[0][0], -1) === '?';

                    if($isOptional)
                    {
                        $match = '(?:' . $match . ')?';
                    }

                    $reverseParts[] = [
                        'variable'  => true,
                        'optional'  => $isOptional,
                        'name'      => $set[1][0]
                    ];

                    $finalParts[] = new RoutePart($match, true, $isOptional);
                }
            }
            else
            {
                $reverseParts[] = [
                    'variable'  => false,
                    'value'     => $part
                ];

                $finalParts[] = new RoutePart($part);
            }

            $reverseParts[] = [
                'variable'  => false,
                'value'     => '/'
            ];

            $finalParts[] = new RoutePart('/');
        }

        array_pop($reverseParts);
        array_pop($finalParts);

        var_dump(implode('', $finalParts));

        return new Route(implode('', $finalParts), $finalParts, $variables, $reverseParts);
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

}
