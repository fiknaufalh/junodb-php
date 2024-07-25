<?php
namespace JunoPhpClient\IO;

use JunoPhpClient\Exception\JunoException;
use JunoPhpClient\Util\JunoConfig;
use JunoPhpClient\Util\PayloadCompressor;
use React\Promise\Deferred;
use React\Socket\ConnectionInterface;
use React\Socket\Connector;
use React\Promise\PromiseInterface;

class JunoAsyncConnection {
    private $config;
    private $connector;
    private $connection;

    public function __construct(JunoConfig $config) {
        $this->config = $config;
        $this->connector = new Connector([
            'timeout' => $config->get('connection.timeout_msec', 1000) / 1000
        ]);
    }

    private function getConnection() {
        if ($this->connection === null) {
            $deferred = new Deferred();
            $host = $this->config->get('server.host');
            $port = $this->config->get('server.port');
            
            $this->connector->connect("tcp://$host:$port")->then(
                function (ConnectionInterface $connection) use ($deferred) {
                    $this->connection = $connection;
                    $deferred->resolve($connection);
                },
                function (\Exception $e) use ($deferred) {
                    $deferred->reject(new JunoException("Failed to connect to JunoDB server: " . $e->getMessage()));
                }
            );
            
            return $deferred->promise();
        }
        
        return \React\Promise\resolve($this->connection);
    }

    public function send($operation, $key, $value = null, $lifetime = null, $context = null) {
        $request = $this->buildRequest($operation, $key, $value, $lifetime, $context);
        
        return $this->getConnection()->then(
            function (ConnectionInterface $connection) use ($request) {
                $deferred = new Deferred();
                
                $connection->write($this->prepareData($request));
                
                $connection->once('data', function ($data) use ($deferred) {
                    $response = $this->processResponse($data);
                    $deferred->resolve($response);
                });
                
                return $deferred->promise();
            }
        );
    }

    private function buildRequest($operation, $key, $value = null, $lifetime = null, $context = null) {
        $request = [
            'operation' => $operation,
            'key' => $key,
            'value' => $value,
            'lifetime' => $lifetime,
            'context' => $context,
        ];
        return json_encode($request);
    }

    private function prepareData($data) {
        if ($this->config->get('usePayloadCompression', false)) {
            return PayloadCompressor::compress($data);
        }
        return $data;
    }

    private function processResponse($data) {
        if ($this->config->get('usePayloadCompression', false)) {
            $data = PayloadCompressor::decompress($data);
        }
        return json_decode($data, true);
    }

    public function sendBatch(array $requests): PromiseInterface {
        $batchRequest = json_encode(['batch' => $requests]);
        
        return $this->getConnection()->then(
            function (ConnectionInterface $connection) use ($batchRequest) {
                $deferred = new Deferred();
                
                $connection->write($this->prepareData($batchRequest));
                
                $connection->once('data', function ($data) use ($deferred) {
                    $response = $this->processResponse($data);
                    $deferred->resolve($response);
                });
                
                return $deferred->promise();
            }
        );
    }
}
