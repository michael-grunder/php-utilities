<?php

namespace Mgrunder\Utilities;

class GetOpt {
    public static function get(array $opt, string $key, $default = null) {
        return $opt[$key] ?? $_GET[$key] ?? $_POST[$key] ?? $default;
    }

    public static function getIntOption(array $opt, string $key, int $default) {
        return (int) self::get($opt, $key, $default);
    }

    public static function getRangeOption(array $opt, string $key, int|float $min,
                                          int|float $max, string $default) : array
    {
        $val = self::get($opt, $key, $default);
        $exp = explode('-', $val);

        if (count($val) == 2) {
            [$min, $max] = $exp;
        } else if (count($val) == 1) {
            [$min, $max] = [$val, $val];
        }

        if ($max < $min)
            [$min, $max] = [$max, $min];

        return [$min, $max];
    }

    public static function getValidOption(array $opt, string $key, array $valid, $default) {
        $val = self::get($opt, $key, $default);

        if ( ! in_array($val, $valid)) {
            $valid_str = implode(', ', $valid);
            throw new \Exception("Invalid value '$val' for '$key', (valid: $valid_str)");
        }

        return $val;
    }
}
