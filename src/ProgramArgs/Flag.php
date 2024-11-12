<?php

namespace Mgrunder\Utilities\ProgramArgs;

use Mgrunder\Utilities\ProgramArgs\Arg;

class Flag extends Arg {
    public function __construct($name, string|array $keys) {
        parent::__construct($name, $keys);
    }

    public function required(): bool {
        return false;
    }

    public function get(array $opt, array $cfg): mixed {
        foreach (array_filter([$this->short(), $this->long()]) as $key) {
            if (isset($opt[$key]) || isset($_GET[$key]) || isset($_POST[$key])) {
                return true;
            }
        }

        return isset($cfg[$this->category()][$this->long()]);
    }
}
