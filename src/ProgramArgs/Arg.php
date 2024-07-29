<?php

namespace Mgrunder\Utilities\ProgramArgs;

class Arg {
    private    bool $required = true;
    private  string $name;
    private ?string $short = null;
    private ?string $long = null;
    private ?string $description = null;
    private   mixed $default = null;

    private $resolved = false;

    private $validator = null;
    private $parser    = null;

    private $value;

    public function __construct(string $name, string|array $keys) {
        $this->name = $name;
        $this->parseKeys($keys);
    }

    protected function invalidValue($value, $why = null) {
        $suffix = $why ? "(reason: $why)" : '';
        throw new \Exception("$value is not valid for '{$this->name}' $suffix");
    }

    protected function parseKeys(string|array $keys) {
        foreach (is_array($keys) ? $keys : [$keys] as $key) {
            // Key can't contain ':' or '-'
            if (strpos($key, ':') !== false || strpos($key, '-') === 0)
                throw new \Exception("Key '$key' can't contain ':' or begin with '-'");

            if (strlen($key) === 1)
                $this->short = $key;
            else
                $this->long = $key;
        }
    }

    public function usageString(string $seperator, bool $bookends): string {
        $bits = [];

        if ($this->short())
            $bits[] = '-' . $this->short();
        if ($this->long())
            $bits[] = '--' . $this->long();

        if ( ! $bookends)
            return implode($seperator, $bits);

        return ($this->required() ? '<' : '[') .
               implode($seperator, $bits)
               ($this->required() ? '>' : ']');
    }

    public function name(): string {
        return $this->name;
    }

    public function short(): ?string {
        return $this->short;
    }

    public function long(): ?string {
        return $this->long;
    }

    public function description(): ?string {
        return $this->description;
    }

    public function setDescription(string $description): self {
        $this->description = $description;
        return $this;
    }

    public function default(): mixed {
        return $this->default;
    }

    public function required(): bool {
        return $this->required;
    }

    public function setDefault(mixed $default): self {
        $this->required = false;
        $this->default = $default;
        return $this;
    }

    function setValidator(callable $cb): self {
        $this->validator = $cb;
        return $this;
    }

    protected function getRaw(array $opt): mixed {
        $check_keys = array_filter([$this->long, $this->short]);
        if ( ! $check_keys)
            throw new \Exception("No keys set for argument {$this->name}");

        foreach ($check_keys as $key) {
            if (isset($opt[$key]))
                return $opt[$key];
            else if (isset($_GET[$key]))
                return $_GET[$key];
            else if (isset($_POST[$key]))
                return $_POST[$key];
        }

        return $this->default;
    }

    public function parse(mixed $value): mixed {
        return $value;
    }

    protected function validate($value): bool {
        if ($this->validator) {
            if ( ! call_user_func($this->validator, $value))
                $this->invalidValue($value, 'failed validation');
        }

        return true;
    }

    protected function resolve(array $opt): mixed {
        $value = $this->parse($this->getRaw($opt));
        if ($value === null && $this->required)
            throw new \Exception("Argument '{$this->name}' is required");

        $this->validate($value);

        return $value;
    }

    public function get(array $opt): mixed {
        if ( ! $this->resolved) {
            $this->value    = $this->resolve($opt);
            $this->resolved = true;
        }

        return $this->value;
    }

    protected function isIntOrIntString($v) {
        return is_int($v) || is_string($v) && preg_match('/^\d+$/', $v);
    }

    protected function isFloatOrFloatString($v) {
        return is_float($v) || is_string($v) && preg_match('/^\d+(\.\d+)?$/', $v);
    }

    /* Helper that uses filter_validate_float */
    public static function validateFloat($v, ?float $min = null, ?float $max = null): bool {
        return filter_var($v, FILTER_VALIDATE_FLOAT) !== false &&
               ($min === null || $v >= $min) &&
               ($max === null || $v <= $max);
    }
}
