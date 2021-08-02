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

    public static function diffCommandCounts(array $pre, array $post, $sort = true) {
        $res = [];

        foreach ($post as $cmd => $count) {
            if (isset($pre[$cmd]))
                $res[$cmd] = $count - $pre[$cmd];
            else
                $res[$cmd] = $count;
        }

        if ($sort) {
            uasort($res, function ($a, $b) {
                if ($a == $b)
                    return 0;
                else
                    return $b - $a;
            });
        }

        return $res;
    }
}
