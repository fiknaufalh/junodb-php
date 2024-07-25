<?php
namespace JunoPhpClient\Util;

class SSLUtil {
    public static function getSSLContext($certPath, $keyPath) {
        $context = stream_context_create();
        stream_context_set_option($context, 'ssl', 'local_cert', $certPath);
        stream_context_set_option($context, 'ssl', 'local_pk', $keyPath);
        stream_context_set_option($context, 'ssl', 'verify_peer', false);
        return $context;
    }
}
