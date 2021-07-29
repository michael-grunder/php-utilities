<?php

namespace Mgrunder\Utilities;

class HttpBench {
    protected static function getColonKeyVal($line, $key) {
        $regex = "/$key:\s+([0-9]+)/";
        if (preg_match($regex, $line, $matches) && count($matches) == 2) {
            return $matches[1];
        } else {
            return false;
        }
    }

    protected static function getTimeColonKeyVal($line, $key, $to_ms = true) {
        $regex = "/$key:\s+([0-9.]+(µs|us|ms|s))/";
        if (preg_match($regex, $line, $matches) && count($matches) == 3) {
            return $to_ms ? Utilities::toMilliseconds($matches[1]) : $matches[1];
        } else {
            return false;
        }
    }

    public static function execWelle($uri, $requests, $concurrency, &$errors = 0) {
        $cmd = "/home/mike/.cargo/bin/welle -n $requests -c $concurrency \"$uri\"";
        exec($cmd, $output, $exitcode);
        if ( $exitcode != 0)
            throw new \Exception("non-zero exit code from command '$cmd'");

        return self::parseWelle(implode("\n", $output), $errors);
    }

    public static function parseWelle($output, &$errors = 0) {
        $info = [
            'total_req' => self::getColonKeyVal($output, "Total Requests"),
            'concurrency' => self::getColonKeyVal($output, "Concurrency Count"),
            'completed' => self::getColonKeyVal($output, "Total Completed Requests"),
            'errors' => self::getColonKeyVal($output, "Total Errored Requests"),
            '5XX_errors' => self::getColonKeyVal($output, "Total 5XX Requests"),
            'tot_time' => self::getTimeColonKeyVal($output, "Total Time Taken"),
            'avg_time' => self::getColonKeyVal($output, "Avg Time Taken"),
            'tot_in_flight' => self::getTimeColonKeyVal($output, "Total Time In Flight"),
            'avg_in_flight' => self::getTimeColonKeyVal($output, "Avg Time In Flight"),
        ];

        foreach (['50', '66', '75', '80', '90', '95', '99', '100'] as $pct) {
            $info['hist'][$pct] = self::getTimeColonKeyVal($output, $pct . '%');
        }

        $errors = $info['errors'];

        return $info;
    }

    public static function execWrk($uri, $threads, $connections, $duration, &$errors) {
        $cmd = "/home/mike/bin/wrk -t{$threads} -c{$connections} -d{$duration} \"$uri\"";
        exec($cmd, $output, $exitcode);
        if ( $exitcode != 0)
            throw new \Exception("Non-zero exit code from command '$cmd'");

        $result = self::ParseWrk(implode("\n", $output), $errors);
        if ( ! $result)
            throw new \Exception("Couldn't parse 'wrk' output from command '$cmd'");

        return $result;
    }

    public static function parseWrk($output, &$errors = 0) {
        $r1 = '/([0-9]+) threads and ([0-9]+) connections/';
        if ( ! preg_match($r1, $output, $m1) || count($m1) != 3) {
            return false;
        }

        $r2 = '/Requests\/sec:\s+([0-9.]+)/';
        if ( ! preg_match($r2, $output, $m2) || count($m2) != 2)
            return false;

        $r3 = '/Latency\s+([0-9.]+(us|ms|s))\s+([0-9.]+(us|ms|s))\s+([0-9.]+(us|ms|s))/';
        if ( ! preg_match($r3, $output, $m3) || count($m3) != 7)
            return false;

        $r4 = '/Req\/Sec\s+([0-9.]+)\s+([0-9.]+)\s+([0-9.]+)/';
        if ( ! preg_match($r4, $output, $m4) || count($m4) != 4)
            return false;

        $r5 = '/Non-2xx or 3xx responses:\s+([0-9]+)/';
        if ( preg_match($r5, $output, $m5) && count($m5) == 2) {
            $errors = $m5[1];
        }

        $r6 = '/([0-9]+) requests in/';
        if ( ! preg_match($r6, $output, $m6) || count($m6) != 2)
            return false;

        $res['threads'] = $m1[1];
        $res['connections'] = $m1[2];
        $res['total_req'] = $m6[1];
        $res['req_per_sec'] = $m2[1];
        $res['latency_avg'] = Utilities::toMilliseconds($m3[1]);
        $res['latency_stddev'] = Utilities::toMilliseconds($m3[3]);
        $res['latency_max'] = Utilities::toMilliseconds($m3[5]);
        $res['req_per_sec_avg'] = $m4[1];
        $res['req_per_sec_stddev'] = $m4[2];
        $res['req_per_sec_max'] = $m4[3];

        return $res;
    }
}
