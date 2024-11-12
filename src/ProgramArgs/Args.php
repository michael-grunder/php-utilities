<?php

namespace Mgrunder\Utilities\ProgramArgs;

require_once __DIR__ . '/../../vendor/autoload.php';

use Mgrunder\Utilities\ProgramArgs\Arg;
use Mgrunder\Utilities\ProgramArgs\Flag;

use Symfony\Component\Yaml\Yaml;

class Args {
    /**
     * @var array<bool>
     */
    private array $short;

    /**
     * @var array<bool>
     */
    private array $long;

    /**
     * @var array<Arg>
     */
    private array $args;

    /**
     * @var array<mixed>
     *
     * Cli options parse via getopt
     */
    private array $opt;

    /**
     * @var array<mixed>
     *
     * Options parsed via yaml file
     */
    private array $cfg = [];

    /**
     * @var array<string>
     */
    private array $remainder;

    private function buildGetOptString(): array {
        $short = '';
        $long  = [];

        foreach ($this->args as $arg) {
            if ($arg->short()) {
                $short .= $arg->short();
                if ( ! ($arg InstanceOf Flag)) {
                    $short .= ':';
                }
            }

            if ($arg->long()) {
                $long[] = $arg->long() . ($arg InstanceOf Flag ? '' : ':');
            }
        }

        return [$short, $long];
    }

    private function execGetOpt() {
        global $argv;

        [$short, $long] = $this->buildGetOptString();

        $this->opt = getopt($short, $long, $optind);

        $this->remainder = array_slice($argv, $optind);
    }

    public function printUsage(string $program) {
        $usage = "Usage: $program";
        $short = '';
        $long = [];

        $aux = $this->args;
        ksort($aux);

        $cmdargs = [];

        foreach ($aux as $arg) {
            $usage .= ' ';
            if ($arg->required()) {
                $usage .= '<';
            } else {
                $usage .= '[';
            }

            if ($arg->short()) {
                $short .= $arg->short();
                if ( ! ($arg InstanceOf Flag)) {
                    $short .= ':';
                }
                $usage .= '-' . $arg->short();
            }

            if ($arg->long()) {
                if ($arg->short()) {
                    $usage .= '|';
                }

                $long[] = $arg->long();
                if ( ! ($arg InstanceOf Flag)) {
                    $long[] = ':';
                }
                $usage .= '--' . $arg->long();
            }

            if ($arg->required()) {
                $usage .= '>';
            } else {
                $usage .= ']';
            }
        }

        echo "$usage\n";
        echo "Options:\n";

        foreach ($this->args as $arg) {
            echo "  ";
            if ($arg->short()) {
                echo '-' . $arg->short();
                if ($arg->long()) {
                    echo ', ';
                }
            }

            if ($arg->long()) {
                echo '--' . $arg->long();
            }

            echo "\t" . ($arg->description() ?? '') . "\n";
        }
    }

    public function __construct(array $args) {
        $args[] = (new Flag('help', ['h', 'help']))
            ->setDescription('Show this help message');

        foreach ($args as $arg) {
            if ($arg->short()) {
                if (isset($this->short[$arg->short()]))
                    throw new \Exception("Short argument {$arg->short} already exists");
                $this->short[$arg->short()] = true;
            }
            if ($arg->long()) {
                if (isset($this->long[$arg->long()]))
                    throw new \Exception("Long argument {$arg->long} already exists");
                $this->short[$arg->long()] = true;
            }

            $this->args[$arg->name()] = $arg;
        }

        $this->execGetOpt();
    }

    public function setConfigFile(string $file, array $paths = []) {
        $paths[] = '.';

        foreach ($paths as $path) {
            if ( ! file_exists($path . '/' . $file))
                continue;

            $this->cfg = Yaml::parseFile($file);
            return;
        }
    }

    public function get(string $name): mixed {
        if ( ! isset($this->args[$name]))
            throw new \Exception("Argument $name not found");

        return $this->args[$name]->get($this->opt, $this->cfg);
    }

    public function getString(string $name): string {
        $val = $this->get($name);

        assert(is_scalar($val));

        return $val;
    }

    public function getInt(string $name): int {
        $val = $this->get($name);

        assert(is_scalar($val));

        return (int)$val;
    }

    public function getBool(string $name): bool {
        $val = $this->get($name);

        assert(is_scalar($val));

        return boolval($val);
    }

    public function getFloat(string $name): float {
        $val = $this->get($name);

        assert(is_scalar($val));

        return floatval($val);
    }

    public function dumpValues(): void{
        foreach ($this->args as $name => $arg) {
            printf("%s: %s\n", $name, print_r($this->get($name), true));
        }
    }

    public function remainder(): array {
        return $this->remainder;
    }
}
