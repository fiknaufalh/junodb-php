<?php

namespace JunoPhpClient\Client\Impl;

use JunoPhpClient\Client\JunoClient;
use JunoPhpClient\Client\IO\JunoRequest;
use JunoPhpClient\Client\IO\JunoResponse;
use JunoPhpClient\Client\IO\RecordContext;
use JunoPhpClient\Exception\JunoException;
use JunoPhpClient\Conf\JunoPropertiesProvider;
use JunoPhpClient\Net\RequestQueue;
use JunoPhpClient\Util\JunoClientUtil;

class JunoClientImpl implements JunoClient
{
    private JunoReactClientImpl $reactClient;

    public function __construct(JunoClientConfigHolder $config, ?\OpenSSLAsymmetricKey $ctx)
    {
        $this->reactClient = new JunoReactClientImpl($config, $ctx, false);
    }

    public function create(string $key, string $value): JunoResponse
    {
        return $this->reactClient->create($key, $value)->wait();
    }

    public function createWithTTL(string $key, string $value, int $timeToLiveSec): JunoResponse
    {
        return $this->reactClient->createWithTTL($key, $value, $timeToLiveSec)->wait();
    }

    public function get(string $key): JunoResponse
    {
        return $this->reactClient->get($key)->wait();
    }

    public function getWithTTL(string $key, int $timeToLiveSec): JunoResponse
    {
        return $this->reactClient->getWithTTL($key, $timeToLiveSec)->wait();
    }

    public function update(string $key, string $value): JunoResponse
    {
        return $this->reactClient->update($key, $value)->wait();
    }

    public function updateWithTTL(string $key, string $value, int $timeToLiveSec): JunoResponse
    {
        return $this->reactClient->updateWithTTL($key, $value, $timeToLiveSec)->wait();
    }

    public function set(string $key, string $value): JunoResponse
    {
        return $this->reactClient->set($key, $value)->wait();
    }

    public function setWithTTL(string $key, string $value, int $timeToLiveSec): JunoResponse
    {
        return $this->reactClient->setWithTTL($key, $value, $timeToLiveSec)->wait();
    }

    public function delete(string $key): JunoResponse
    {
        return $this->reactClient->delete($key)->wait();
    }

    public function compareAndSet(RecordContext $jcx, string $value, int $timeToLiveSec): JunoResponse
    {
        return $this->reactClient->compareAndSet($jcx, $value, $timeToLiveSec)->wait();
    }

    public function doBatch(array $request): array
    {
        $responses = $this->reactClient->doBatch($request)->wait();
        return iterator_to_array($responses);
    }

    public function getProperties(): array
    {
        return $this->reactClient->getProperties();
    }
}