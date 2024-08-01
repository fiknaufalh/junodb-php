<?php
namespace JunoPhpClient\Util;

class JunoConfig {
    private $config;

    public function __construct(array $config = []) {
        $this->config = $config;

        $this->config['server']['host'] = getenv('JUNO_HOST') 
            ?: ($this->config['server']['host'] ?? 'proxy');
        $this->config['server']['port'] = getenv('JUNO_PORT') 
            ?: ($this->config['server']['port'] ?? 8080);

        // Log configuration
        error_log("JunoConfig initialized with host: " . 
                    $this->config['server']['host'] . 
                    " and port: " . $this->config['server']['port']);
    }

    public function get($key, $default = null) {
        // echo "Key: " . $key . "\nValue: " . $this->config[$key] . "\n";

        $keys = explode('.', $key);
        $value = $this->config;
        foreach ($keys as $k) {
            if (!isset($value[$k])) {
                return $default;
            }
            $value = $value[$k];
        }
        return $value;
    }

    public function set($key, $value) {
        $this->config[$key] = $value;
    }

    public function getAll() {
        return $this->config;
    }
}