<?php

namespace Mgrunder\Utilities\ProgramArgs;

require_once __DIR__ . '/../../vendor/autoload.php';

use Mgrunder\Utilities\ProgramArgs\RandRange;

class RandFloat extends RandRange {
    public function parse(string $value): mixed {
        $parts = $this->getParts($value);
        if ($parts === null)
            return $value;

        $min = (float)($parts[0] ?? PHP_FLOAT_MIN);
        $max = (float)($parts[1] ?? PHP_FLOAT_MAX);

        return $min + lcg_value() * ($max - $min);
    }
}
