<?php

namespace JunoPhpClient\Client;

use JunoPhpClient\Conf\JunoPropertiesProvider;
use JunoPhpClient\Exception\JunoClientConfigException;
use JunoPhpClient\Util\JunoClientUtil;
use JunoPhpClient\Util\JunoConstants;

class JunoClientConfigHolder
{
    protected JunoPropertiesProvider $junoProp;
    private array $serversAddress;

    public function __construct(JunoPropertiesProvider $junoProp)
    {
        JunoClientUtil::throwIfNull($junoProp, "config");
        try {
            $this->junoProp = $junoProp;
            $this->validateAll();
            $this->serversAddress = self::getServerAddress($this->junoProp);
        } catch (\Exception $ce) {
            throw new JunoClientConfigException($ce->getMessage(), $ce);
        }
    }

    public function printProperties(): string
    {
        return $this->junoProp->__toString();
    }

    public function getProperties(): array
    {
        $prop = [];
        $prop['juno.response.timeout_msec'] = $this->getResponseTimeout();
        $prop['juno.connection.timeout_msec'] = $this->getConnectionTimeoutMsecs();
        $prop['juno.connection.pool_size'] = $this->getConnectionPoolSize();
        $prop['juno.default_record_lifetime_sec'] = $this->getDefaultLifetimeSecs();
        $prop['juno.server.host'] = $this->getHost();
        $prop['juno.server.port'] = $this->getPort();
        $prop['juno.application_name'] = $this->getApplicationName();
        $prop['juno.record_namespace'] = $this->getRecordNamespace();
        $prop['juno.usePayloadCompression'] = $this->getUsePayloadCompression();
        $prop['juno.connection.byPassLTM'] = $this->getByPassLTM();
        $prop['juno.connection.reconnect_on_fail'] = $this->getReconnectOnFail();
        $prop['juno.operation.retry'] = $this->isRetryEnabled();
        return $prop;
    }

    protected function validateAll(): void
    {
        $this->getConnectionTimeoutMsecs();
        $this->getApplicationName();
        $this->getRecordNamespace();
        $this->getDefaultLifetimeSecs();
        $this->getConnectionLifeTime();
        $this->getConnectionPoolSize();
        $this->getHost();
        $this->getMaxKeySize();
        $this->getResponseTimeout();
        $this->getMaxValueSize();
    }

    public static function getServerAddress(JunoPropertiesProvider $junoProp): array
    {
        $host = trim($junoProp->getHost());
        $port = $junoProp->getPort();

        if (empty($host)) {
            throw new JunoClientConfigException("Juno server not configured...");
        }

        if ($port < 1) {
            throw new JunoClientConfigException("Invalid Juno server port...");
        }

        return [$host, $port];
    }


    public function getApplicationName(): string
    {
        $appName = $this->junoProp->getAppName();
        if (empty($appName)) {
            throw new JunoClientConfigException("Juno configuration value for property, juno.application_name cannot be null or empty");
        }
        if (strlen($appName) > JunoConstants::APP_NAME_MAX_LEN) {
            $msg = "Application Name length exceeds MAX LENGTH of " . JunoConstants::APP_NAME_MAX_LEN . " bytes";
            throw new JunoClientConfigException($msg);
        }
        return $appName;
    }

    private function getMaxRecordNameSpaceLength(): int
    {
        $recordNameSpaceLength = $this->validateAndReturnDefaultInt(
            'juno.max_record_namespace_length',
            $this->junoProp->getMaxNameSpaceLength(),
            0,
            PHP_INT_MAX,
            JunoPropertyDefaultValue::MAX_NAMESPACE_LENGTH
        );
        return $recordNameSpaceLength;
    }

    public function getRecordNamespace(): string
    {
        $ns = $this->junoProp->getRecordNamespace();
        if (empty($ns)) {
            throw new JunoClientConfigException("Juno configuration value for property, juno.record_namespace cannot be null or empty");
        }
        if (strlen($ns) > $this->getMaxRecordNameSpaceLength()) {
            $msg = "Namespace length exceeds MAX LENGTH of " . $this->getMaxRecordNameSpaceLength() . " bytes";
            throw new JunoClientConfigException($msg);
        }
        return $ns;
    }

    private function getMaxConnectionLifeTime(): int
    {
        return $this->validateAndReturnDefaultInt(
            'juno.max_connection_lifetime',
            $this->junoProp->getMaxConnectionLifetime(),
            6000,
            PHP_INT_MAX,
            JunoPropertyDefaultValue::MAX_CONNECTION_LIFETIME_MS
        );
    }

    public function getConnectionLifeTime(): int
    {
        return $this->validateAndReturnDefaultInt(
            'juno.connection.lifetime',
            $this->junoProp->getConnectionLifetime(),
            5000,
            $this->getMaxConnectionLifeTime(),
            JunoPropertyDefaultValue::CONNECTION_LIFETIME_MS
        );
    }

    private function getMaxConnectionPoolSize(): int
    {
        return $this->validateAndReturnDefaultInt(
            'juno.max_connection_pool_size',
            $this->junoProp->getMaxConnectionPoolSize(),
            1,
            PHP_INT_MAX,
            JunoPropertyDefaultValue::MAX_CONNECTION_POOL_SIZE
        );
    }

    public function getConnectionPoolSize(): int
    {
        return $this->validateAndReturnDefaultInt(
            'juno.connection.pool_size',
            $this->junoProp->getConnectionPoolSize(),
            1,
            $this->getMaxConnectionPoolSize(),
            JunoPropertyDefaultValue::CONNECTION_POOL_SIZE
        );
    }

    public function getHost(): string
    {
        return trim($this->junoProp->getHost());
    }

    public function getPort(): int
    {
        return $this->junoProp->getPort();
    }

    public function getUseSSL(): bool
    {
        return $this->junoProp->useSSL();
    }

    public function getServer(): array
    {
        return $this->serversAddress;
    }

    public function getByPassLTM(): bool
    {
        return $this->junoProp->getByPassLTM();
    }

    public function getReconnectOnFail(): bool
    {
        return $this->junoProp->getReconnectOnFail();
    }

    protected function validateAndReturnDefaultInt(string $key, ?int $prop, int $min, int $max, int $defaultVal): int
    {
        if ($prop === null) {
            return $defaultVal;
        }
        if ($prop < $min) {
            throw new JunoClientConfigException(
                "Juno configuration value for property $key cannot be less than $min"
            );
        }
        if ($prop > $max) {
            throw new JunoClientConfigException(
                "Juno configuration value for property $key cannot be greater than $max"
            );
        }
        return $prop;
    }

    protected function validateAndReturnDefaultFloat(string $key, ?float $prop, float $min, float $max, float $defaultVal): float
    {
        if ($prop === null) {
            return $defaultVal;
        }
        if ($prop < $min) {
            throw new JunoClientConfigException(
                "Juno configuration value for property $key cannot be less than $min"
            );
        }
        if ($prop > $max) {
            throw new JunoClientConfigException(
                "Juno configuration value for property $key cannot be greater than $max"
            );
        }
        return $prop;
    }

    public function getUsePayloadCompression(): bool
    {
        $usePayloadCompression = $this->processBoolProperty('juno.usePayloadCompression', $this->junoProp->isUsePayloadCompression());
        if ($usePayloadCompression !== null && $usePayloadCompression !== $this->junoProp->isUsePayloadCompression()) {
            $this->junoProp->setUsePayloadCompression($usePayloadCompression);
        }
        return $usePayloadCompression;
    }

    public function getConnectionTimeoutMsecs(): int
    {
        $connTimeout = $this->processIntProperty(
            'juno.connection.timeout_msec',
            $this->junoProp->getConnectionTimeout(),
            1,
            JunoPropertyDefaultValue::MAX_CONNECTION_TIMEOUT_MS,
            JunoPropertyDefaultValue::CONNECTION_TIMEOUT_MS
        );
        if ($connTimeout !== null && $connTimeout !== $this->junoProp->getConnectionTimeout()) {
            $this->junoProp->setConnectionTimeout($connTimeout);
        }
        return $connTimeout;
    }

    public function getMaxLifetimeSecs(): int
    {
        $maxLifeTime = $this->processIntProperty(
            'juno.max_record_lifetime_sec',
            $this->junoProp->getMaxLifetime(),
            1,
            PHP_INT_MAX,
            JunoPropertyDefaultValue::MAX_LIFETIME_S
        );
        if ($maxLifeTime !== null && $maxLifeTime !== $this->junoProp->getMaxLifetime()) {
            $this->junoProp->setMaxLifetime($maxLifeTime);
        }
        return $maxLifeTime;
    }

    public function getDefaultLifetimeSecs(): int
    {
        $lifetime = $this->processIntProperty(
            'juno.default_record_lifetime_sec',
            $this->junoProp->getDefaultLifetime(),
            1,
            $this->getMaxLifetimeSecs(),
            JunoPropertyDefaultValue::DEFAULT_LIFETIME_S
        );
        if ($lifetime !== null && $lifetime !== $this->junoProp->getDefaultLifetime()) {
            $this->junoProp->setDefaultLifetime($lifetime);
        }
        return $lifetime;
    }

    public function isRetryEnabled(): bool
    {
        $enableRetry = $this->processBoolProperty('juno.operation.retry', $this->junoProp->getOperationRetry());
        if ($enableRetry !== null && $enableRetry !== $this->junoProp->getOperationRetry()) {
            $this->junoProp->setOperationRetry($enableRetry);
        }
        return $enableRetry;
    }

    public function getMaxValueSize(): int
    {
        $maxValueSize = $this->processIntProperty(
            'juno.max_value_size',
            $this->junoProp->getMaxValueSize(),
            1,
            PHP_INT_MAX,
            JunoPropertyDefaultValue::MAX_VALUE_SIZE_B
        );
        if ($maxValueSize !== null && $maxValueSize !== $this->junoProp->getMaxValueSize()) {
            $this->junoProp->setMaxValueSize($maxValueSize);
        }
        return $maxValueSize;
    }

    public function getMaxKeySize(): int
    {
        $maxKeySize = $this->processIntProperty(
            'juno.max_key_size',
            $this->junoProp->getMaxKeySize(),
            1,
            PHP_INT_MAX,
            JunoPropertyDefaultValue::MAX_KEY_SIZE_B
        );
        if ($maxKeySize !== null && $maxKeySize !== $this->junoProp->getMaxKeySize()) {
            $this->junoProp->setMaxKeySize($maxKeySize);
        }
        return $maxKeySize;
    }

    public function getResponseTimeout(): int
    {
        $responseTimeout = $this->processIntProperty(
            'juno.response.timeout_msec',
            $this->junoProp->getResponseTimeout(),
            1,
            JunoPropertyDefaultValue::MAX_RESPONSE_TIMEOUT_MS,
            JunoPropertyDefaultValue::RESPONSE_TIMEOUT_MS
        );
        if ($responseTimeout !== null && $responseTimeout !== $this->junoProp->getResponseTimeout()) {
            $this->junoProp->setResponseTimeout($responseTimeout);
        }
        return $responseTimeout;
    }

    private function processIntProperty(string $property, ?int $currentValue, int $min, int $max, int $defaultValue): ?int
    {
        $propertyWithPrefix = $this->junoProp->getConfigPrefix() !== '' ? $this->junoProp->getConfigPrefix() . '.' . $property : $property;
        $rcsValue = $this->getRemoteConfigInt($propertyWithPrefix, $currentValue);
        $intProperty = $currentValue;

        if ($rcsValue !== null && $rcsValue !== $currentValue) {
            try {
                $intProperty = $this->validateAndReturnDefaultInt($propertyWithPrefix, $rcsValue, $min, $max, $defaultValue);
            } catch (JunoClientConfigException $e) {
                // Log the exception
            }
        } else {
            $intProperty = $this->validateAndReturnDefaultInt($propertyWithPrefix, $currentValue, $min, $max, $defaultValue);
        }
        return $intProperty;
    }

    private function processBoolProperty(string $property, ?bool $currentValue): ?bool
    {
        $propertyWithPrefix = $this->junoProp->getConfigPrefix() !== '' ? $this->junoProp->getConfigPrefix() . '.' . $property : $property;
        $rcsValue = $this->getRemoteConfigBool($propertyWithPrefix, $currentValue);
        return $rcsValue !== null ? $rcsValue : $currentValue;
    }

    private function getRemoteConfigInt(string $propertyWithPrefix, ?int $currentValue): ?int
    {
        if ($this->junoProp->getConfig() !== null && $this->junoProp->getConfig()->containsKey($propertyWithPrefix)) {
            try {
                return $this->junoProp->getConfig()->getInt($propertyWithPrefix);
            } catch (\Exception $e) {
                // Log the exception
            }
        }
        return $currentValue;
    }

    private function getRemoteConfigBool(string $propertyWithPrefix, ?bool $currentValue): ?bool
    {
        if ($this->junoProp->getConfig() !== null && $this->junoProp->getConfig()->containsKey($propertyWithPrefix)) {
            try {
                return $this->junoProp->getConfig()->getBoolean($propertyWithPrefix);
            } catch (\Exception $e) {
                // Log the exception
            }
        }
        return $currentValue;
    }
}