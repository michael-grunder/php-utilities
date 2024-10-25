<?php

namespace Mgrunder\Utilities\ProgramArgs;

require_once __DIR__ . '/../../vendor/autoload.php';

use Mgrunder\Utilities\ProgramArgs\DynamicArg;

class RandList extends DynamicArg {
    private const REGEX = '/{rand:([^}]*)}/';

    public function parse(string $value): mixed {
        if ( ! preg_match(self::REGEX, $value, $parts))
            return $value;

        $options = explode(':', $parts[1]);

        return $options[array_rand($options)];
    }
}
