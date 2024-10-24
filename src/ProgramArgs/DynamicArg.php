<?php

namespace Mgrunder\Utilities\ProgramArgs;

abstract class DynamicArg {
    abstract public function parse(string $value): mixed;
}
