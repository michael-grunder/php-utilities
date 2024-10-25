<?php

namespace Mgrunder\Utilities\ProgramArgs;

require_once __DIR__ . '/../../vendor/autoload.php';

use Mgrunder\Utilities\ProgramArgs\RandRange;

class RandString extends RandRange {
    public function parse(string $value): mixed {
        $parts = $this->getParts($value);
        if ($parts === null)
            return $value;

        $min    = (int)($parts[0] ?? 0);
        $max    = (int)($parts[1] ?? 32);
        $suffix = $parts[3] ?? '';

        if ($min < 0)
            return $value . $suffix;

        if ($max == 0)
            return '';

        $len = rand($min, $max);
        if ($len == 0)
            return $suffix;

        return substr(bin2hex(random_bytes($len)), 0, $len) . $suffix;
    }
}
