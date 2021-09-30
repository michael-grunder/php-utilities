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

    public static function parseMonitorFP($fp, &$errors = 0) {
        $res = [];

        while ($line = fgets($fp)) {
            if (($resp = self::parseMonitorCmd($line))) {
                $res[] = $resp;
            } else {
                $errors++;
            }
        }

        return $res;
    }

    public static function parseMonitorFile($file, &$errors) {
        $fp = fopen($file, 'r');
        if ( ! $fp)
            throw new \Exception("Failed to open file '$file'");

        return self::parseMonitorFp($fp, $errors);
    }

    public static function parseMonitorCmd($cmd, $decode = true) {
        $re_info = '/([0-9.]+) \[([0-9]+) ([0-9.]+:[0-9]+)] /';
        $re_args = '/"[^"\\\\]*(?:\\\\.[^"\\\\]*)*"/s';

        $cmd = str_replace(' [::1]:', ' 127.0.0.1:', $cmd);

        if ( ! preg_match($re_info, $cmd, $client) || count($client) != 4)
            return false;

        $cmd = substr($cmd, strlen($client[0]));
        if ( ! preg_match_all($re_args, $cmd, $argexp) || count($argexp) < 1)
            return false;

        $args = $argexp[0];
        array_walk($args, function (&$v) use ($decode) {
            $v = substr($v, 1, strlen($v) - 2);
            if ($decode)
                $v = stripcslashes($v);
        });

        return [$client, $args];
    }
}
