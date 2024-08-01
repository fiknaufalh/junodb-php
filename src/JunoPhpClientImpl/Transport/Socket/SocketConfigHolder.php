<?php

namespace JunoPhpClient\Transport\Socket;

use JunoPhpClient\Client\JunoClientConfigHolder;
use JunoPhpClient\Transport\TransportConfigHolder;

class SocketConfigHolder implements TransportConfigHolder
{
    private string $inetAddress;
    private string $host;
    private int $port;
    private bool $useSSL;
    private int $connectTimeout;
    private int $connectionLifeTime;
    private int $connectionPoolSize;
    private int $responseTimeout;
    private bool $bypassLTM;
    private string $ns;
    private bool $reconnectOnFail;
    private $ctx;

    public function __construct(JunoClientConfigHolder $config)
    {
        $this->inetAddress = $config->getServer();
        $this->connectTimeout = $config->getConnectionTimeoutMsecs();
        $this->connectionLifeTime = $config->getConnectionLifeTime();
        $this->useSSL = $config->getUseSSL();
        $this->port = $config->getPort();
        $this->host = $config->getHost();
        $this->connectionPoolSize = $config->getConnectionPoolSize();
        $this->responseTimeout = $config->getResponseTimeout();
        $this->bypassLTM = $config->getByPassLTM();
        $this->ns = $config->getRecordNamespace();
        $this->reconnectOnFail = $config->getReconnectOnFail();
    }

    public function getPort(): int
    {
        return $this->port;
    }
    
    public function getHost(): string
    {
        return $this->host;
    }

    public function getConnectTimeout(): int
    {
        return $this->connectTimeout;
    }

    public function getInetAddress(): string
    {
        return $this->inetAddress;
    }
    
    public function getConnectionLifeTime(): int
    {
        return $this->connectionLifeTime;
    }

    public function useSSL(): bool
    {
        return $this->useSSL;
    }
    
    public function getConnectionPoolSize(): int
    {
        return $this->connectionPoolSize;
    }

    public function getResponseTimeout(): int
    {
        return $this->responseTimeout;
    }
    
    public function getCtx()
    {
        return $this->ctx;
    }

    public function setCtx($ctx): void
    {
        $this->ctx = $ctx;
    }
    
    public function getBypassLTM(): bool
    {
        return $this->bypassLTM;
    }

    public function getReconnectOnFail(): bool
    {
        return $this->reconnectOnFail;
    }
    
    public function getRecordNamespace(): string
    {
        return $this->ns;
    }
    
    public function isTestMode(): bool
    {
        return false;
    }

    public function getJunoPool(): string
    {
        if (strpos($this->host, 'junoserv-') === 0) {
            $junoPool = explode('-', $this->host, 2);
            return $junoPool[0];
        } else {
            return $this->host . ':' . $this->port;
        }
    }
}