<?php
namespace JunoPhpClient\Util;

use JunoPhpClient\Client\JunoClient;
use JunoPhpClient\Client\JunoAsyncClient;

class JunoClientFactory {
    public static function newJunoClient(JunoConfig $config, JunoLogger $logger) {
        return new JunoClient($config, $logger);
    }

    public static function newJunoAsyncClient(JunoConfig $config, JunoLogger $logger) {
        return new JunoAsyncClient($config, $logger);
    }
}
