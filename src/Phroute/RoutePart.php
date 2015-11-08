<?php namespace Phroute\Phroute;

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