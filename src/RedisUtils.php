<?php

namespace Mgrunder\Utilities;

class RedisUtils {
    public static function getRedisClient($class, $host, $port) {
        if ($class == 'relay')
            $class = '\Relay\Relay';
        if ( ! class_exists($class))
            Utilities::panicAbort("Class '$class' doesn't exist");

        $obj = new $class;

        try {
            $obj->connect($host, $port);
        } catch (\Exception $ex) {
            Utilities::panicException($ex);
        }

        return $obj;
    }

    public static function getCommandCounts($redis) {
        $res = [];

        $lines = $redis->info('commandstats');

        foreach ($lines as $ident => $stats) {
            list(, $cmd) = explode('_', $ident);
            if ( ! preg_match('/calls=([0-9]+),/', $stats, $matches) || count($matches) != 2)
                Utilities::panicAbort("Malformed cmdstat line '$stats'");

            $res[$cmd] = $matches[1];
        }

        return $res;
    }

    public static function diffCommandCounts(array $c2, array $c1, $filter = true) {
        $res = [];

        foreach (array_keys($c1) as $k) {
            $res[$k] = isset($c2[$k]) ? abs($c2[$k] - $c1[$k]) : 0;
        }

        return $filter ? array_filter($res) : $res;
    }
}
