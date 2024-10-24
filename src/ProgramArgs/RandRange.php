<?php

namespace Mgrunder\Utilities\ProgramArgs;

require_once __DIR__ . '/../../vendor/autoload.php';

use Mgrunder\Utilities\ProgramArgs\DynamicArg;

abstract class RandRange extends DynamicArg {
    private const REGEX = '/\{rand(?::(\d*(?:\.\d+)?)(?::(\d*(?:\.\d+)?))?(?::(\d+))?)?\}/';

    protected int|float|null $min;
    protected int|float|null $max;

    /**
     * @param string $value
     * @return array{0: string, 1: string, 2: string}|null
     */
    protected function getParts(string $value): ?array {
        if ( ! preg_match(self::REGEX, $value, $matches))
            return null;

        return [$matches[1] ?? null, $matches[2] ?? null, $matches[3] ?? null];
    }

    public function __construct(int|float|null $min = null,
                                int|float|null $max = null)
    {
        $this->min = $min;
        $this->max = $max;
    }
}
