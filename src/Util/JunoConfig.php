<?php
namespace JunoPhpClient\Util;

class JunoConfig {
    private $config;

    public function __construct(array $config) {
        $this->config = $config;
    }

    public function get($key, $default = null) {
        return $this->config[$key] ?? $default;
    }

    public function set($key, $value) {
        $this->config[$key] = $value;
    }

    public function getAll() {
        return $this->config;
    }
}