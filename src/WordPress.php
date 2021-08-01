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

    public static function setOCPClient($cfgfile, $client) {
        $cfgfile_bak = $cfgfile . ".bak";

        if ( ! copy($cfgfile, $cfgfile_bak))
            throw new \Exception("Error:  Can't backup '$cfgfile' -> '$cfgfile_bak', aborting!");

        $client = strtolower($client);
        if ($client == 'redis')
            $client = 'phpredis';
        else if ($client != 'relay' && $client != 'phpredis')
            throw new \Exception("Error:  Invalid client '$client'");

        $regex = "s/'client'.*=>.*/'client' => '$client',/";
        Utilities::sedFileInPlace($cfgfile, $regex, true);

        $prefetch = $client == 'phpredis' ? 'true' : 'false';
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

    public static function setOCPClientHttp($iv, $key, $cipher, $uri, $client) {
        $uri = "http://$uri/update-ocp-client.php?payload=" .
            Secure::encryptPayload("client=$client", $iv, $key, $cipher);
        $resp = file_get_contents($uri);
        if ( ! $resp)
            Utilities::panicAbort("Can't hit endpoint '$uri'");
        $dec = json_decode($resp, true);
        if ( ! isset($dec['update']))
            Utilities::panicAbort("Malformed reply '$resp'");

        return $dec['update'];
    }

}
