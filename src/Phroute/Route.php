<?php namespace Phroute\Phroute;

class Route {

    /**
     * Constants for common HTTP methods
     */
    const ANY       = 'ANY';
    const GET       = 'GET';
    const HEAD      = 'HEAD';
    const POST      = 'POST';
    const PUT       = 'PUT';
    const PATCH     = 'PATCH';
    const DELETE    = 'DELETE';
    const OPTIONS   = 'OPTIONS';

    private $parts;

    private $variableParts;

    public function __construct($parts)
    {
        $this->parts = $parts;

        $this->variableParts = array_filter($this->parts, function(RoutePart $part){
            return $part->variable !== false;
        });
    }

    public function getParts()
    {
        return $this->parts;
    }

    public function hasVariableParts()
    {
        return count($this->getVariableParts()) > 0;
    }

    public function getVariableParts()
    {
        return $this->variableParts;
    }

    public function getRegex()
    {
        return implode('', $this->parts);
    }
}