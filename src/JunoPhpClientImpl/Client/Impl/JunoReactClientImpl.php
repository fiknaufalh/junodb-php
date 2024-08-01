<?php

namespace JunoPhpClient\Client\Impl;

use JunoPhpClient\Client\JunoReactClient;
use JunoPhpClient\Client\JunoClientConfigHolder;
use JunoPhpClient\Client\IO\JunoRequest;
use JunoPhpClient\Client\IO\JunoResponse;
use JunoPhpClient\Client\IO\RecordContext;
use JunoPhpClient\Exception\JunoException;
use JunoPhpClient\IO\JunoAsyncConnection;
use JunoPhpClient\Util\JunoClientUtil;
use React\Promise\PromiseInterface;

class JunoReactClientImpl implements JunoReactClient
{
    private JunoClientConfigHolder $configHolder;
    private JunoAsyncConnection $connection;
    private bool $isAsync = true;

    public function __construct(JunoClientConfigHolder $config, $ctx, bool $isAsync = true)
    {
        $this->configHolder = $config;
        $this->connection = new JunoAsyncConnection($config);
        $this->isAsync = $isAsync;
    }

    public function create(string $key, string $value): PromiseInterface
    {
        return $this->processSingle('CREATE', $key, $value);
    }

    public function createWithTTL(string $key, string $value, int $timeToLiveSec): PromiseInterface
    {
        return $this->processSingle('CREATE', $key, $value, $timeToLiveSec);
    }

    public function get(string $key): PromiseInterface
    {
        return $this->processSingle('GET', $key);
    }

    public function getWithTTL(string $key, int $timeToLiveSec): PromiseInterface
    {
        return $this->processSingle('GET', $key, null, $timeToLiveSec);
    }

    public function update(string $key, string $value): PromiseInterface
    {
        return $this->processSingle('UPDATE', $key, $value);
    }

    public function updateWithTTL(string $key, string $value, int $timeToLiveSec): PromiseInterface
    {
        return $this->processSingle('UPDATE', $key, $value, $timeToLiveSec);
    }

    public function set(string $key, string $value): PromiseInterface
    {
        return $this->processSingle('SET', $key, $value);
    }

    public function setWithTTL(string $key, string $value, int $timeToLiveSec): PromiseInterface
    {
        return $this->processSingle('SET', $key, $value, $timeToLiveSec);
    }

    public function delete(string $key): PromiseInterface
    {
        return $this->processSingle('DELETE', $key);
    }

    public function compareAndSet(RecordContext $context, string $value, int $timeToLiveSec): PromiseInterface
    {
        return $this->processSingle('CAS', $context->getKey(), $value, $timeToLiveSec, $context);
    }

    public function doBatch(array $requests): PromiseInterface
    {
        return $this->connection->sendBatch($requests);
    }

    public function getProperties(): array
    {
        return $this->configHolder->getProperties();
    }

    private function processSingle(string $operation, string $key, ?string $value = null, ?int $timeToLiveSec = null, ?RecordContext $context = null): PromiseInterface
    {
        $req = new JunoRequest($key, $value, $context ? $context->getVersion() : 0, $timeToLiveSec, $context ? $context->getCreationTime() : 0, JunoRequest\OperationType::from($operation));
        
        try {
            $reqMsg = JunoClientUtil::validateInput($req, JunoMessage\OperationType::from($operation), $this->configHolder);
            return $this->connection->send($operation, $key, $value, $timeToLiveSec, $context)
                ->then(
                    function ($response) use ($reqMsg) {
                        $respMsg = JunoClientUtil::decodeOperationMessage($response, $reqMsg->getKey(), $this->configHolder);
                        return new JunoResponse($reqMsg->getKey(), $respMsg->getValue(), $respMsg->getVersion(),
                            $respMsg->getTimeToLiveSec(), $respMsg->getCreationTime(), $respMsg->getStatus()->getOperationStatus());
                    },
                    function ($error) {
                        throw new JunoException($error->getMessage());
                    }
                );
        } catch (JunoException $e) {
            return \React\Promise\reject($e);
        }
    }
}