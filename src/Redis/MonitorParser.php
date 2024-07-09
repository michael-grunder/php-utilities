<?php

namespace Mgrunder\Utilities\Redis;

class MonitorLog {
    private $fp;
    private int $lineno = 0;
    private int|string $serializer;
    private int|string $compression;

    public function __construct(string $filename, $serializer = 'none', $compression = 'none') {
        $this->fp = fopen($filename, 'r');
        if ($this->fp === false)
            panicAbort("Could not open file '%s' for reading", $filename);

        $this->serializer = $serializer;
        $this->compression = $compression;
    }

    public function next(): MonitorLine|null|false {
        $line = fgets($this->fp);
        if ($line === false)
            return false;

        $this->lineno++;

        $line = str_replace("\n", '', $line);
        $info = MonitorLine::parse($line, $this->serializer, $this->compression);

        if ($info === NULL) {
            printWarning("Can't parse line %d: %s", $this->lineno, $line);
            return NULL;
        }

        return $info;
    }
}

class MonitorLine {
    const READ_CMD  = (1 << 0);
    const WRITE_CMD = (1 << 1);
    const FLUSH_CMD = (1 << 2);
    const DEL_CMD   = (1 << 3);
    const ADMIN_CMD = (1 << 4);

    private static array $cmd_flags = [
        'CLIENT' => self::ADMIN_CMD,
        'DBSIZE' => self::ADMIN_CMD,
        'DEL' => self::DEL_CMD,
        'EXISTS' => self::READ_CMD,
        'FLUSHALL' => self::WRITE_CMD | self::FLUSH_CMD,
        'FLUSHDB' => self::WRITE_CMD | self::FLUSH_CMD,
        'GET' => self::READ_CMD,
        'HELLO' => self::ADMIN_CMD,
        'HGET' => self::READ_CMD,
        'HGETALL' => self::READ_CMD,
        'HMSET' => self::WRITE_CMD,
        'INFO' => self::ADMIN_CMD,
        'MGET' => self::READ_CMD,
        'MSET' => self::WRITE_CMD,
        'PING' => self::ADMIN_CMD,
        'SELECT' => self::ADMIN_CMD,
        'SET' => self::WRITE_CMD,
        'SETEX' => self::WRITE_CMD,
        'UNLINK' => self::DEL_CMD,
        'ZADD' => self::WRITE_CMD,
    ];

    public float $timestamp;
    public string $client;
    public string $server;
    public string $cmd;
    public array $args;

    public $serializer;
    public $compression;

    private static function decompress($data, int $compression) {
        switch ($compression) {
            case Redis::COMPRESSION_ZSTD:
                return @zstd_uncompress($data);
            case Redis::COMPRESSION_LZF:
                return @lzf_decompress($data);
            case Redis::COMPRESSION_LZ4:
            default:
                return $data;
        }
    }

    private static function compress($data, int $compression) {
        switch ($compression) {
            case Redis::COMPRESSION_ZSTD:
                return @zstd_compress($data);
            case Redis::COMPRESSION_LZF:
                return @lzf_compress($data);
            case Redis::COMPRESSION_LZ4:
            default:
                return $data;
        }
    }

    public static function unserialize($data, int $serializer) {
        return match ($serializer) {
            Redis::SERIALIZER_IGBINARY => @igbinary_unserialize($data),
            //Redis::SERIALIZER_MSGPACK => @msgpack_unpack($data),
            Redis::SERIALIZER_JSON => json_decode($data, true),
            Redis::SERIALIZER_NONE => $data,
        };
    }

    public static function serialize($data, int $serializer) {
        return match ($serializer) {
            Redis::SERIALIZER_IGBINARY => @igbinary_serialize($data),
            //Redis::SERIALIZER_MSGPACK => @msgpack_pack($data),
            Redis::SERIALIZER_JSON => json_encode($data),
            Redis::SERIALIZER_NONE => $data,
        };
    }

    public static function pack($data, int $serializer, int $compressor) {
        $serialized = self::serialize($data, $serializer);
        if ($serialized === false)
            return $data;

        $enc = self::compress($serialized, $compressor);
        if ($enc === false)
            return $serialized;

        return $enc;
    }

    public static function unpack($data, int $serializer, int $compressor) {
        $dec = @zstd_uncompress($data);
        if ($dec === false)
            return $data;

        $unserialized = @igbinary_unserialize($dec);
        if ($unserialized === false)
            return $dec;
        return $unserialized;
    }

    private static function to_compression(int|string $compression) {
        if (is_string($compression)) {
            return match (strtolower($compression)) {
                'zstd' => Redis::COMPRESSION_ZSTD,
                'lzf' => Redis::COMPRESSION_LZF,
                'lz4' => Redis::COMPRESSION_LZ4,
                default => Redis::COMPRESSION_NONE,
            };
        } else {
            return $compression;
        }
    }

    private static function to_serializer(int|string $serializer) {
        if (is_string($serializer)) {
            return match (strtolower($serializer)) {
                'igbinary' => Redis::SERIALIZER_IGBINARY,
                'msgpack'  => Redis::SERIALIZER_MSGPACK,
                default    => Redis::SERIALIZER_NONE,
            };
        } else {
            return $serializer;
        }
    }

    public static function parse($log, int|string $serializer = 'none',
                                 int|string $compression = 'none')
    {
        $pattern = '/^(\d+\.\d+)\s+\[(\d+)\s+([^\]]+)\]\s+"(\w+)"(?:\s+(.*))?$/';

        if (preg_match($pattern, $log, $matches)) {
            $ts     = $matches[1];
            $client = $matches[2];
            $server = $matches[3];
            $cmd    = $matches[4];

            $args = [];
            if (!empty($matches[5])) {
                preg_match_all('/"((?:[^"\\\\]|\\\\.)*)"/', $matches[5], $argMatches);
                foreach ($argMatches[1] as $arg) {
                    $args[] = stripcslashes($arg);
                }
            }

            return new MonitorLine($ts, $client, $server, $cmd, $args, $serializer, $compression);
        } else {
            return null;
        }
    }

    public function __construct(float $timestamp, string $client, string $server,
                                string $cmd, array $args, int|string $serializer = 'none',
                                int|string $compression = 'none')
    {
        $this->timestamp = $timestamp;
        $this->client = $client;
        $this->server = $server;
        $this->cmd = strtoupper($cmd);
        $this->args = $args;

        $this->serializer = self::to_serializer($serializer);
        $this->compression = self::to_compression($compression);
    }

    public function is_write(): bool {
        return (self::$cmd_flags[$this->cmd] ?? 0) & self::WRITE_CMD;
    }

    /* Returns the keys involved in the command by name, If any.
       and if the command is a write command, the value is the estimated
       total data size (compressed and serialized if applicable) of the
       values.  so hmset would sum the sizes of all the values., etc

       The size does not include the key name at all, just what the size of the
       value is roughly in Redis. */
    public function keys_and_size() {
        switch ($this->cmd) {
            case 'GET':
            case 'HGETALL':
            case 'HMSET':
                return [$this->args[0] => 0];
            case 'HGETALL':
            case 'HMSET':
            case 'SET':
            case 'SETEX':
            case 'ZADD':
                $key = $this->args[0];
                $val = self::pack($this->args[1], $this->serializer, $this->compression);
                return [$this->args[0] => strlen($val)];
            case 'DEL':
            case 'EXISTS':
            case 'MGET':
            case 'UNLINK':
                $arr = [];
                foreach ($this->args as $key) {
                    $arr[$key] = 0;
                }
                return $arr;
            case 'MSET':
                $arr = [];
                foreach (array_chunk($this->args, 2) as [$key, $value]) {
                    $arr[$key] = strlen(self::pack($value, $this->serializer, $this->compression));
                }
                return $arr;
            case 'CLIENT':
            case 'DBSIZE':
            case 'FLUSHALL':
            case 'FLUSHDB':
            case 'HELLO':
            case 'INFO':
            case 'PING':
            case 'SELECT':
                return [];
        }

        panicAbort("Unknown command: %s", $this->cmd);
    }

    public function keys(): array {
        switch ($this->cmd) {
            case 'GET':
            case 'HGETALL':
            case 'HMSET':
            case 'SET':
            case 'SETEX':
            case 'ZADD':
                return [$this->args[0]];
            case 'DEL':
            case 'EXISTS':
            case 'MGET':
            case 'UNLINK':
                return $this->args;
            case 'MSET':
                return array_filter($arr, function($value, $key) {
                    return $key % 2 === 0;
                }, ARRAY_FILTER_USE_BOTH);
            case 'CLIENT':
            case 'DBSIZE':
            case 'FLUSHALL':
            case 'FLUSHDB':
            case 'HELLO':
            case 'INFO':
            case 'PING':
            case 'SELECT':
                return [];
        }

        panicAbort("Unknown command: %s", $this->cmd);
    }
}
