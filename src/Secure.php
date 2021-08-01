<?php

namespace Mgrunder\Utilities;

class Secure {
    public static function loadSecrets($file) {
        if ( ! is_file($file) || ! is_readable($file))
            Utilities::panicAbort("Can't find or read from secrets file '$file'");

        $data = array_filter(explode("\n", @file_get_contents($file)));
        if ( ! $data || count($data) != 3)
            Utilities::panicAbort("Panic:  Malformed secret data\n");

        return $data;
    }

    public static function encryptPayload($input, $iv, $key, $cipher) {
        return openssl_encrypt($input, $cipher, $key, 0, $iv);
    }

    public static function decryptPayload($input, $iv, $key, $cipher) {
        return openssl_decrypt($input, $cipher, $key, 0, $iv);
    }
}
?>
