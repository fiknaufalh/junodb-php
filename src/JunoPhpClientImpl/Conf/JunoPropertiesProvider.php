<?php

namespace JunoPhpClient\Conf;

use JunoPhpClient\Util\BasePropertiesProvider;

class JunoPropertiesProvider extends BasePropertiesProvider
{
    private ?int $responseTimeout = null;
    private ?int $connectionTimeout = null;
    private ?int $connectionLifetime = null;
    private ?int $connectionPoolSize = null;
    private ?int $defaultLifetime = null;
    private ?int $maxLifetime = null;
    private ?int $maxKeySize = null;
    private ?int $maxValueSize = null;
    private ?int $maxNameSpaceLength = null;
    private ?int $maxResponseTimeout = null;
    private ?int $maxConnectionTimeout = null;
    private ?int $maxConnectionPoolSize = null;
    private ?int $maxConnectionLifetime = null;
    private ?string $host = null;
    private ?int $port = null;
    private ?string $appName = null;
    private ?string $recordNamespace = null;
    private ?bool $useSSL = null;
    private ?bool $usePayloadCompression = null;
    private ?bool $operationRetry = null;
    private ?bool $byPassLTM = null;
    private ?bool $reconnectOnFail = null;
    private ?string $configPrefix = null;

    public function __construct(array $props)
    {
        parent::__construct($props);
        $this->validateAndFillAll();
    }

    public function getResponseTimeout(): ?int
    {
        return $this->responseTimeout;
    }

    public function setResponseTimeout(?int $responseTimeout): void
    {
        $this->responseTimeout = $responseTimeout;
    }

    public function getConnectionTimeout(): ?int
    {
        return $this->connectionTimeout;
    }

    public function setConnectionTimeout(?int $connectionTimeout): void
    {
        $this->connectionTimeout = $connectionTimeout;
    }

    public function getConnectionLifetime(): ?int
    {
        return $this->connectionLifetime;
    }

    public function setConnectionLifetime(?int $connectionLifetime): void
    {
        $this->connectionLifetime = $connectionLifetime;
    }

    public function getConnectionPoolSize(): ?int
    {
        return $this->connectionPoolSize;
    }

    public function setConnectionPoolSize(?int $connectionPoolSize): void
    {
        $this->connectionPoolSize = $connectionPoolSize;
    }

    public function getDefaultLifetime(): ?int
    {
        return $this->defaultLifetime;
    }

    public function setDefaultLifetime(?int $defaultLifetime): void
    {
        $this->defaultLifetime = $defaultLifetime;
    }

    public function getMaxLifetime(): ?int
    {
        return $this->maxLifetime;
    }

    public function setMaxLifetime(?int $maxLifetime): void
    {
        $this->maxLifetime = $maxLifetime;
    }

    public function getMaxKeySize(): ?int
    {
        return $this->maxKeySize;
    }

    public function setMaxKeySize(?int $maxKeySize): void
    {
        $this->maxKeySize = $maxKeySize;
    }

    public function getMaxValueSize(): ?int
    {
        return $this->maxValueSize;
    }

    public function setMaxValueSize(?int $maxValueSize): void
    {
        $this->maxValueSize = $maxValueSize;
    }

    public function getMaxNameSpaceLength(): ?int
    {
        return $this->maxNameSpaceLength;
    }

    public function setMaxNameSpaceLength(?int $maxNameSpaceLength): void
    {
        $this->maxNameSpaceLength = $maxNameSpaceLength;
    }

    public function getMaxResponseTimeout(): ?int
    {
        return $this->maxResponseTimeout;
    }

    public function setMaxResponseTimeout(?int $maxResponseTimeout): void
    {
        $this->maxResponseTimeout = $maxResponseTimeout;
    }

    public function getMaxConnectionTimeout(): ?int
    {
        return $this->maxConnectionTimeout;
    }

    public function setMaxConnectionTimeout(?int $maxConnectionTimeout): void
    {
        $this->maxConnectionTimeout = $maxConnectionTimeout;
    }

    public function getMaxConnectionPoolSize(): ?int
    {
        return $this->maxConnectionPoolSize;
    }

    public function setMaxConnectionPoolSize(?int $maxConnectionPoolSize): void
    {
        $this->maxConnectionPoolSize = $maxConnectionPoolSize;
    }

    public function getMaxConnectionLifetime(): ?int
    {
        return $this->maxConnectionLifetime;
    }

    public function setMaxConnectionLifetime(?int $maxConnectionLifetime): void
    {
        $this->maxConnectionLifetime = $maxConnectionLifetime;
    }

    public function getHost(): ?string
    {
        return $this->host;
    }

    public function setHost(?string $host): void
    {
        $this->host = $host;
    }

    public function getPort(): ?int
    {
        return $this->port;
    }

    public function setPort(?int $port): void
    {
        $this->port = $port;
    }

    public function getAppName(): ?string
    {
        return $this->appName;
    }

    public function setAppName(?string $appName): void
    {
        $this->appName = $appName;
    }

    public function getRecordNamespace(): ?string
    {
        return $this->recordNamespace;
    }

    public function setRecordNamespace(?string $recordNamespace): void
    {
        $this->recordNamespace = $recordNamespace;
    }

    public function getUseSSL(): ?bool
    {
        return $this->useSSL;
    }

    public function setUseSSL(?bool $useSSL): void
    {
        $this->useSSL = $useSSL;
    }

    public function getUsePayloadCompression(): ?bool
    {
        return $this->usePayloadCompression;
    }

    public function setUsePayloadCompression(?bool $usePayloadCompression): void
    {
        $this->usePayloadCompression = $usePayloadCompression;
    }

    public function getOperationRetry(): ?bool
    {
        return $this->operationRetry;
    }

    public function setOperationRetry(?bool $operationRetry): void
    {
        $this->operationRetry = $operationRetry;
    }

    public function getByPassLTM(): ?bool
    {
        return $this->byPassLTM;
    }

    public function setByPassLTM(?bool $byPassLTM): void
    {
        $this->byPassLTM = $byPassLTM;
    }

    public function getReconnectOnFail(): ?bool
    {
        return $this->reconnectOnFail;
    }

    public function setReconnectOnFail(?bool $reconnectOnFail): void
    {
        $this->reconnectOnFail = $reconnectOnFail;
    }

    public function getConfigPrefix(): ?string
    {
        return $this->configPrefix;
    }

    public function setConfigPrefix(?string $configPrefix): void
    {
        $this->configPrefix = $configPrefix;
    }

    public function getConfig(): Conf

    {
        return $this->configValue;
    }

    public function setConfigValue(?string $configValue): void
    {
        $this->configValue = $configValue;
    }


    private function validateAndFillAll(): void
    {
        // Implement validation and population of properties
    }

    // Implement getters and setters for all properties
}