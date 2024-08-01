<?php

namespace JunoPhpClient\Util;

use JunoPhpClient\Exception\JunoClientConfigException;

class BasePropertiesProvider
{
    protected array $config;
    
    protected function __construct(string $url)
    {
        if (empty($url)) {
            throw new \InvalidArgumentException("URL cannot be null or empty");
        }
        $this->config = parse_ini_file($url, true);
        if ($this->config === false) {
            throw new JunoClientConfigException("Unable to read the config properties file");
        }
    }
    
    protected function __construct(array $config)
    {
        $this->config = $config;
    }
    
    protected function getIntProperty(string $key, int $defaultValue): int
    {
        $sval = $this->config[$key] ?? null;
        $ival = $defaultValue;
        if ($sval !== null) {
            try {
                $ival = (int) trim($sval);
            } 
            catch(\Exception $e) {
                throw new JunoClientConfigException("Integer property not valid - Value = " . $sval, $e);
            }
        }
        return $ival;
    }

    protected function getIntegerProperty(string $key): ?int
    {
        $sval = $this->config[$key] ?? null;
        $ival = null;
        if ($sval !== null && strtolower(trim($sval)) !== 'null') {
            try {
                $ival = (int) trim($sval);
            }
            catch(\Exception $e) {
                throw new JunoClientConfigException("Integer property not valid - Value = " . $sval, $e);
            }
        }
        return $ival;
    }

    protected function getLongProperty(string $key, int $defaultValue): int
    {
        $sval = $this->config[$key] ?? null;
        $ival = $defaultValue;
        if ($sval !== null) {
            try {
                $ival = (int) trim($sval);
            }
            catch(\Exception $e) {
                throw new JunoClientConfigException("Long property not valid - Value = " . $sval, $e);
            }
        }
        return $ival;
    }

    protected function getLongProperty(string $key): ?int
    {
        $sval = $this->config[$key] ?? null;
        $ival = null;
        if ($sval !== null) {
            try {
                $ival = (int) trim($sval);
            }
            catch(\Exception $e) {
                throw new JunoClientConfigException("Long property not valid - Value = " . $sval, $e);
            }
        }
        return $ival;
    }

    protected function getBooleanProperty(string $key, bool $defaultValue): bool
    {
        $sval = $this->config[$key] ?? null;
        if ($sval === null) {
            return $defaultValue;
        }
        try {
            return filter_var($sval, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
        }
        catch(\Exception $e) {
            throw new JunoClientConfigException("Boolean property not valid - Value = " . $sval, $e);
        }
    }

    protected function getBooleanProperty(string $key): ?bool
    {
        $sval = $this->config[$key] ?? null;
        if ($sval === null) {
            return null;
        }
        try {
            return filter_var($sval, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
        }
        catch(\Exception $e) {
            throw new JunoClientConfigException("Boolean property not valid - Value = " . $sval, $e);
        }
    }

    protected function getStringProperty(string $key, string $defaultValue): string
    {
        return $this->config[$key] ?? $defaultValue;
    }
}