<?php

namespace Mgrunder\Utilities\ProgramArgs;

use Mgrunder\Utilities\ProgramArgs\Arg;

class RangeArg extends Arg {
    private $min = null;
    private $max = null;

    private bool $is_float = false;

    public function setFloat(bool $is_float): RangeArg {
        $this->is_float = $is_float;
        return $this;
    }

    public function setMin(int|float $min): RangeArg {
        $this->min = $min;
        return $this;
    }

    public function setMax(int|float $max): RangeArg {
        $this->max = $max;
        return $this;
    }

    protected function parser($value) {
        $bits = explode('-', $bits);

        if (count($bits) != 1 && count($bits) != 2)
            $this->invalidValue($value, "not a range");

        return [$bits[0], $bits[1] ?? $bits[0]];
    }

    protected function validate($value): bool {
        [$lo, $hi] = $value;

        if ( ! $this->is_float) {
            if ( ! $this->isIntOrIntString($lo))
                $this->invalidValue($lo, 'not an integer');
            if ( ! $this->isIntOrIntString($hi))
                $this->invalidValue($hi, 'not an integer');
        } else {
            if ( ! $this->isFloatOrFloatString($lo))
                $this->invalidValue($lo, 'not a number');
            if ( ! $this->isFloatOrFloatString($hi))
                $this->invalidValue($hi, 'not a number');
        }

        if ($lo < $this->min)
            $this->invalidValue($lo, "less than {$this->min}");
        if ($hi > $this->max)
            $this->invalidValue($hi, "greater than {$this->max}");

        return true;
    }
}
