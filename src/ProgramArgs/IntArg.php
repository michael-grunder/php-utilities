<?php

namespace Mgrunder\Utilities\ProgramArgs;

use Mgrunder\Utilities\ProgramArgs\ArgType;

class IntArg extends Arg {
    private $min = PHP_INT_MIN;
    private $max = PHP_INT_MAX;

    public function __construct(string $name, string|array $keys)
    {
        parent::__construct($name, $keys);
    }

    public function setMin(int $min) {
        $this->min = $min;
        return $this;
    }

    public function setMax(int $max) {
        $this->max = $max;
        return $this;
    }

    public function setRange(?int $min, ?int $max) {
        if ($max && $min && $max < $min)
            throw new \InvalidArgumentException("Max value must be greater than min value");

        $this->min = $min;
        $this->max = $max;

        return $this;
    }

    protected function validate($value): bool {
        if ( ! $this->isIntOrIntString($value))
            $this->invalidValue($value, 'not a number');

        $value = (int)$value;

        if ($value < $this->min)
            $this->invalidValue($value, "less than {$this->min}");
        if ($value > $this->max)
            $this->invalidValue($value, "greater than {$this->max}");

        return $value;
    }
}
