<?php

namespace Mgrunder\Utilities\ProgramArgs;

require_once __DIR__ . '/../../vendor/autoload.php';

use Mgrunder\Utilities\ProgramArgs\DynamicArg;

abstract class RandRange extends DynamicArg {
    private const REGEX = '/\{rand(?::(\d*(?:\.\d+)?)(?::(\d*(?:\.\d+)?))?)?\}/';

    protected function getParts(string $value): ?array {
        if ( ! preg_match(self::REGEX, $value, $matches))
            return null;

        return [$matches[1] ?? null, $matches[2] ?? null];
    }
}
