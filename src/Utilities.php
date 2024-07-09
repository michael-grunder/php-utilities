<?php

namespace Mgrunder\Utilities;

class Utilities
{
    public static function panicAbort($msg, $prefix = NULL) {
        $msg = ($msg InstanceOf \Exception) ? $msg->getMessage() : $msg;

        if ($prefix) {
            $msg = "$prefix: $msg\n";
        } else {
            $msg = "Error: $msg\n";
        }

        fprintf(STDERR, $msg);
        exit(-1);
    }

    public static function panicException($ex) {
        self::panicAbort($ex->getMessage(), "Exception");
    }

    public static function unimplemented() {
        self::panicAbort("unimplemented");
    }

    public static function gzOpenOrAbort($file, $mode = 'r') {
        if ( ! is_file($file)) {
            self::panicAbort("Can't find file '$file'");
        }

        if ( ! @($fp = gzOpen($file, $mode))) {
            self::panicAbort("Can't open file '$file' in mode '$mode'");
        }

        return $fp;
    }

    public static function getFileLines($filename, $filter = true) {
        $data = file_get_contents($filename);
        if ($filter)
            return array_filter(explode("\n", $data));
        else
            return explode("\n", $data);
    }


    public static function getFileLinesOrAbort($filename, $filter = true) {
        try {
            $lines = @self::getFileLines($filename, $filter);
        } catch (\Exception $ex) {
            self::panicException($ex);
        }

        return $lines;
    }

    public static function sedFileInPlace($filename, $regex, $sponge) {
        if ( ! is_file($filename) || ! is_writable($filename))
            throw new \Exception("Can't find file '$filename' or it's not writable!");

        if ($sponge) {
            $cmd = "sed \"$regex\" \"$filename\" |sponge \"$filename\"";
        } else {
            $cmd = "sed -i \"$regex\" \"$filename\"";
        }

        exec($cmd, $output, $status);
        if ($status != 0)
            throw new \Exception("Failed to apply '$regex' to file '$filename");

        return true;
    }

    public static function sedFileInPlaceOrAbort($filename, $regex, $sponge) {
        try {
            self::sedFileInPlace($filename, $regex, $sponge);
        } catch (\Exception $ex) {
            self::panicException($ex);
        }
    }

    public static function toMilliseconds($input) {
        if ( ! preg_match('/([0-9.]+)((us|ms|s))/', $input, $matches) || count($matches) != 4)
            die("Can't normalize '$input' to milliseconds\n");

        list(, $num, $unit) = $matches;
        if ($unit == 's') {
            return $num * 1000;
        } else if ($unit == 'ms') {
            return $num;
        } else if ($unit == 'us') {
            return $num / 1000;
        } else {
            die("Unknown unit '$unit'\n");
        }
    }

    function activeSleep(string $msg, float $time, float $tick = 0.1,
                         ?callable $cb = null)
    {
        $spinner = ['|', '/', '-', '\\'];
        $startTime = microtime(true);
        $endTime = $startTime + $time;
        $spinnerIndex = 0;
        $maxlen = 0;

        while (microtime(true) < $endTime) {
            $curr = microtime(true);
            $elapsed = $curr - $startTime;
            $remaining = $endTime - $curr;
            $remaining_sec = round($remaining, 1);
            $elapsed_sec = round($elapsed, 1);

            // Format the message
            $formatted_msg = str_replace(
                ['{total}', '{remaining}', '{elapsed}'],
                [
                    number_format($time, 1),
                    number_format($remaining_sec, 1),
                    number_format($elapsed_sec, 1)],
                $msg
            );

            if ($cb) {
                $res = $cb($formatted_msg);
                $formatted_msg = str_replace('{cb}', $res, $formatted_msg);
            }

            echo "\r";

            $msg = sprintf("%s %s", $spinner[$spinnerIndex], $formatted_msg);
            $maxlen = max($maxlen, strlen($msg));

            echo $msg;

            flush();

            usleep((int)($tick * 1000000));

            $spinnerIndex = ($spinnerIndex + 1) % count($spinner);
        }

        printf("\r%{maxlen}s\n", "Done!");
    }
}
