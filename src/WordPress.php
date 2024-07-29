<?php

namespace Mgrunder\Utilities;

class WordPress {
    public static function getOCPClient($cfgfile) {
        $data = file_get_contents($cfgfile);
        if ( ! $data)
            throw new \Exception("Can't read data from '$cfgfile'");

        $regex = "/'client'\s+=>\s+'(.*)'/";
        if ( ! preg_match($regex, $data, $matches) || count($matches) != 2)
            throw new \Exception("Can't parse client string from '$cfgfile'");

        return $matches[1];
    }

    public static function getOCPClientOrAbort($cfgfile) {
        try {
            return self::getOCPClient($cfgfile);
        } catch (\Exception $ex) {
            Utilities::panicAbort($ex);
        }
    }

    public static function setOCPClient($cfgfile, $client, $prefetch = NULL) {
        $client = strtolower($client);
        if ($client == 'redis')
            $client = 'phpredis';
        else if ($client != 'relay' && $client != 'phpredis')
            throw new \Exception("Error:  Invalid client '$client'");

        $regex = "s/'client'.*=>.*/'client' => '$client',/";
        Utilities::sedFileInPlace($cfgfile, $regex, true);

        if ($prefetch === NULL)
            $prefetch = $client == 'phpredis' ? 'true' : 'false';
        else
            $prefetch = $prefetch ? 'true' : 'false';

        $regex = "s/'prefetch'.*=>.*/'prefetch' => $prefetch,/";

        Utilities::sedFileInPlace($cfgfile, $regex, true);
    }

    public static function setOCPClientOrAbort($cfgfile, $client) {
        try {
            self::setOCPClient($cfgfile, $client);
        } catch (\Exception $ex) {
            Utilities::panicException($ex);
        }
    }

    protected static function tryGetUri($uri, $timeout = 3, $retries = 1) {
        $curl = curl_init();

        do {
            curl_setopt($curl, CURLOPT_URL, $uri);
            curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, $timeout);
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        } while (curl_errno($curl) == 28 && $retries-- > 0);

        $resp = json_decode(curl_exec($curl), true);
        if ( ! isset($resp['update'])) {
            Utilities::panicAbort("Malformed reply '$resp'");
        }

        return $resp['update'];
    }

    public static function setOCPClientHttp($host, $client, $prefetch = NULL) {
        $n = rand();
        $p = hash('sha256', "$n:$client");
        $u = "http://$host/update-ocp-client.php?n=$n&payload=$p";

        if ($prefetch == 'true' || $prefetch == 'false')
            $u .= "&p=$prefetch";

        return self::tryGetUri($u);
    }
}
