<?php

namespace JunoPhpClient\Client\Impl;

use JunoPhpClient\Client\JunoAsyncClient;
use JunoPhpClient\Client\IO\JunoRequest;
use JunoPhpClient\Client\IO\JunoResponse;
use JunoPhpClient\Client\IO\RecordContext;
use JunoPhpClient\Client\IO\OperationStatus;
use JunoPhpClient\Exception\JunoException;
use JunoPhpClient\Conf\JunoPropertiesProvider;
use JunoPhpClient\Net\RequestQueue;
use JunoPhpClient\Util\JunoClientUtil;
use React\Promise\PromiseInterface;
use React\Promise\Deferred;

class JunoAsyncClientImpl implements JunoAsyncClient
{
    private JunoClientConfigHolder $configHolder;
    private RequestQueue $reqQueue;
    private bool $isAsync = true;

    public function __construct(JunoClientConfigHolder $config, ?\OpenSSLAsymmetricKey $ctx, bool $isAsync = true)
    {
        $this->configHolder = $config;
        $socCfg = new SocketConfigHolder($config);
        $socCfg->setCtx($ctx);
        $this->reqQueue = RequestQueue::getInstance($socCfg);
        $this->isAsync = $isAsync;
    }

    public function create(string $key, string $value): PromiseInterface
    {
        $req = new JunoRequest($key, $value, 0, null, JunoRequest\OperationType::Create);
        return $this->processSingle($req, JunoMessage::OperationType::Create);
    }

    public function createWithTTL(string $key, string $value, int $timeToLiveSec): PromiseInterface
    {
        $req = new JunoRequest($key, $value, 0, $timeToLiveSec, JunoRequest\OperationType::Create);
        return $this->processSingle($req, JunoMessage::OperationType::Create);
    }

    public function get(string $key): PromiseInterface
    {
        $req = new JunoRequest($key, null, 0, null, JunoRequest\OperationType::Get);
        return $this->processSingle($req, JunoMessage::OperationType::Get);
    }

    public function getWithTTL(string $key, int $timeToLiveSec): PromiseInterface
    {
        $req = new JunoRequest($key, null, 0, $timeToLiveSec, JunoRequest\OperationType::Get);
        return $this->processSingle($req, JunoMessage::OperationType::Get);
    }

    public function update(string $key, string $value): PromiseInterface
    {
        $req = new JunoRequest($key, $value, 0, null, JunoRequest\OperationType::Update);
        return $this->processSingle($req, JunoMessage::OperationType::Update);
    }

    public function updateWithTTL(string $key, string $value, int $timeToLiveSec): PromiseInterface
    {
        $req = new JunoRequest($key, $value, 0, $timeToLiveSec, JunoRequest\OperationType::Update);
        return $this->processSingle($req, JunoMessage::OperationType::Update);
    }

    public function set(string $key, string $value): PromiseInterface
    {
        $req = new JunoRequest($key, $value, 0, null, JunoRequest\OperationType::Set);
        return $this->processSingle($req, JunoMessage::OperationType::Set);
    }

    public function setWithTTL(string $key, string $value, int $timeToLiveSec): PromiseInterface
    {
        $req = new JunoRequest($key, $value, 0, $timeToLiveSec, JunoRequest\OperationType::Set);
        return $this->processSingle($req, JunoMessage::OperationType::Set);
    }

    public function delete(string $key): PromiseInterface
    {
        $req = new JunoRequest($key, null, 0, null, JunoRequest\OperationType::Destroy);
        return $this->processSingle($req, JunoMessage::OperationType::Destroy);
    }

    public function compareAndSet(RecordContext $jcx, string $value, int $timeToLiveSec): PromiseInterface
    {
        $req = new JunoRequest($jcx->getKey(), $value, $jcx->getVersion(), $timeToLiveSec, JunoRequest\OperationType::Update);
        return $this->processSingle($req, JunoMessage::OperationType::CompareAndSet);
    }

    public function doBatch(array $request): PromiseInterface
    {
        // Implementation for batch operation
        // This would be similar to the Java implementation, but using PHP's async features
    }

    public function getProperties(): array
    {
        return $this->configHolder->getProperties();
    }

    private function processSingle(JunoRequest $req, JunoMessage\OperationType $opType): PromiseInterface
    {
        $deferred = new Deferred();

        try {
            $reqMsg = JunoClientUtil::validateInput($req, $opType, $this->configHolder);
            $opaque = mt_rand();

            $operationMessage = JunoClientUtil::createOperationMessage($reqMsg, $opaque);
            $respQueue = new \SplQueue();
            $this->reqQueue->getOpaqueResMap()[$opaque] = $respQueue;

            if (!$this->reqQueue->enqueue($operationMessage)) {
                $this->reqQueue->getOpaqueResMap()->offsetUnset($opaque);
                throw new JunoException(OperationStatus::QueueFull->getErrorText());
            }

            // Use ReactPHP's event loop to handle the asynchronous response
            \React\EventLoop\Loop::addTimer($this->configHolder->getResponseTimeout() / 1000, function () use ($respQueue, $deferred, $opaque, $reqMsg) {
                if ($respQueue->isEmpty()) {
                    $this->reqQueue->incrementFailedAttempts();
                    $deferred->reject(new JunoException(OperationStatus::ResponseTimeout->getErrorText()));
                } else {
                    $this->reqQueue->incrementSuccessfulAttempts();
                    $responseOpeMsg = $respQueue->dequeue();
                    $respMsg = JunoClientUtil::decodeOperationMessage($responseOpeMsg, $reqMsg->getKey(), $this->configHolder);
                    $junoResp = new JunoResponse($reqMsg->getKey(), $respMsg->getValue(), $respMsg->getVersion(),
                        $respMsg->getTimeToLiveSec(), $respMsg->getCreationTime(), $respMsg->getStatus()->getOperationStatus());
                    $deferred->resolve($junoResp);
                }
                $this->reqQueue->getOpaqueResMap()->offsetUnset($opaque);
            });

        } catch (JunoException $e) {
            $deferred->reject($e);
        } catch (\Exception $e) {
            $deferred->reject(new JunoException(OperationStatus::InternalError->getErrorText(), $e));
        }

        return $deferred->promise();
    }
}