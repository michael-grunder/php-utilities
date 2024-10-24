<?php

namespace Mgrunder\Utilities\ProgramArgs;

require_once __DIR__ . '/../../vendor/autoload.php';

use Mgrunder\Utilities\ProgramArgs\RandRange;

class RandInt extends RandRange {
    public function parse(string $value): mixed {
        $parts = $this->getParts($value);
        if ($parts === null)
            return $value;

        $min = (int)($parts[0] ?? ($this->min ?? PHP_INT_MIN));
        $max = (int)($parts[1] ?? ($this->max ?? PHP_INT_MAX));

        return rand($min, $max);
    }
}
