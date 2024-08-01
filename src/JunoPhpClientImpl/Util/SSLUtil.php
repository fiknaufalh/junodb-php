<?php

namespace JunoPhpClient\Util;

class SSLUtil
{
    public static function getSSLContext(string $certPath, string $keyPath): array
    {
        $context = [
            'ssl' => [
                'local_cert' => $certPath,
                'local_pk' => $keyPath,
                'verify_peer' => false,
                'verify_peer_name' => false,
            ]
        ];
        
        return $context;
    }
}