<?php

namespace Mgrunder\Utilities\ProgramArgs;

class CsvArg extends Arg {
    private array $valid = [];

    public function __construct(string $name, string|array $keys) {
        parent::__construct($name, $keys);
    }

    public function parse($value): mixed {
        return array_filter(explode(',', $value));
    }

    public function setValid(array $valid) {
        $this->valid = $valid;
        return $this;
    }

    protected function validate($value): bool {
        if ( ! $this->valid)
            return true;

        foreach ($value as $v) {
            if ( ! in_array($v, $value)) {
                $valid_str = implode(', ', $this->valid);
                $this->invalidValue($v, 'not a valid value', "valid: ($valid)");
            }
        }

        return true;
    }

    public function setDefault(mixed $default): Arg {
        if (is_array($default))
            $default = implode(',', $default);

        parent::setDefault($default);

        return $this;
    }
}
