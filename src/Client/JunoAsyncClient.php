<?php
namespace JunoPhpClient\Client;

use JunoPhpClient\Exception\JunoException;
use JunoPhpClient\IO\JunoAsyncConnection;
use JunoPhpClient\Util\JunoConfig;
use JunoPhpClient\Util\JunoLogger;
use React\Promise\PromiseInterface;

class JunoAsyncClient {
    private $connection;
    private $config;
    private $logger;

    public function __construct(JunoConfig $config, JunoLogger $logger) {
        $this->config = $config;
        $this->connection = new JunoAsyncConnection($config);
        $this->logger = $logger;
    }

    public function create($key, $value, $lifetime = null): PromiseInterface {
        $this->logger->info("Async creating key: {$key}");
        $lifetime = $lifetime ?? $this->config->get('default_record_lifetime_sec', 259200);
        return $this->connection->send('CREATE', $key, $value, $lifetime)->then(
            function ($response) use ($key) {
                if ($response['status'] !== 'success') {
                    $this->logger->error("Failed to create key: {$key}");
                    throw new JunoException("Failed to create key: " . $key);
                }
                $this->logger->info("Successfully created key: {$key}");
                return true;
            }
        );
    }

    public function get($key): PromiseInterface {
        $this->logger->info("Async getting key: {$key}");
        return $this->connection->send('GET', $key)->then(
            function ($response) use ($key) {
                if ($response['status'] !== 'success') {
                    $this->logger->error("Failed to get key: {$key}");
                    throw new JunoException("Failed to get key: " . $key);
                }
                $this->logger->info("Successfully got key: {$key}");
                return $response['value'];
            }
        );
    }

    public function update($key, $value, $lifetime = null): PromiseInterface {
        $this->logger->info("Async updating key: {$key}");
        $lifetime = $lifetime ?? $this->config->get('default_record_lifetime_sec', 259200);
        return $this->connection->send('UPDATE', $key, $value, $lifetime)->then(
            function ($response) use ($key) {
                if ($response['status'] !== 'success') {
                    $this->logger->error("Failed to update key: {$key}");
                    throw new JunoException("Failed to update key: " . $key);
                }
                $this->logger->info("Successfully updated key: {$key}");
                return true;
            }
        );
    }

    public function set($key, $value, $lifetime = null): PromiseInterface {
        $this->logger->info("Async setting key: {$key}");
        $lifetime = $lifetime ?? $this->config->get('default_record_lifetime_sec', 259200);
        return $this->connection->send('SET', $key, $value, $lifetime)->then(
            function ($response) use ($key) {
                if ($response['status'] !== 'success') {
                    $this->logger->error("Failed to set key: {$key}");
                    throw new JunoException("Failed to set key: " . $key);
                }
                $this->logger->info("Successfully set key: {$key}");
                return true;
            }
        );
    }

    public function destroy($key): PromiseInterface {
        $this->logger->info("Async destroying key: {$key}");
        return $this->connection->send('DESTROY', $key)->then(
            function ($response) use ($key) {
                if ($response['status'] !== 'success') {
                    $this->logger->error("Failed to destroy key: {$key}");
                    throw new JunoException("Failed to destroy key: " . $key);
                }
                $this->logger->info("Successfully destroyed key: {$key}");
                return true;
            }
        );
    }

    public function compareAndSet($key, $value, $context, $lifetime = null): PromiseInterface {
        $this->logger->info("Async comparing and setting key: {$key}");
        $lifetime = $lifetime ?? $this->config->get('default_record_lifetime_sec', 259200);
        return $this->connection->send('CAS', $key, $value, $lifetime, $context)->then(
            function ($response) use ($key) {
                if ($response['status'] !== 'success') {
                    throw new JunoException("Failed to compare and set key: " . $key);
                }
                return true;
            }
        );
    }

    public function doBatch(array $requests): PromiseInterface {
        $this->logger->info("Async performing batch operation");
        $batchRequests = array_map(function($req) {
            return $this->buildRequest($req['operation'], $req['key'], $req['value'] ?? null, $req['lifetime'] ?? null, $req['context'] ?? null);
        }, $requests);
    
        return $this->connection->sendBatch($batchRequests)->then(
            function ($response) {
                if ($response['status'] !== 'success') {
                    throw new JunoException("Failed to execute batch operation");
                }
                return $response['results'];
            }
        );
    }
    
    private function buildRequest($operation, $key, $value = null, $lifetime = null, $context = null) {
        return [
            'operation' => $operation,
            'key' => $key,
            'value' => $value,
            'lifetime' => $lifetime,
            'context' => $context,
        ];
    }
}
