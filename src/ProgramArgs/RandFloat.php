<?php

namespace Mgrunder\Utilities\ProgramArgs;

require_once __DIR__ . '/../../vendor/autoload.php';

use Mgrunder\Utilities\ProgramArgs\RandRange;

class RandFloat extends RandRange {
    public function parse(string $value): mixed {
        $parts = $this->getParts($value);
        if ($parts === null)
            return $value;

        $min = (float)($parts[0] ?? ($this->min ?? PHP_FLOAT_MIN));
        $max = (float)($parts[1] ?? ($this->max ?? PHP_FLOAT_MAX));
        $pre = $parts[2];

        $val = $min + lcg_value() * ($max - $min);
        if ($pre !== null)
            $val = round($val, (int)$pre);

        return $val;
    }
}
