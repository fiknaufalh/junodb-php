<?php
namespace JunoPhpClient\Client;

use JunoPhpClient\Exception\JunoException;
use JunoPhpClient\IO\ConnectionPool;
use JunoPhpClient\Util\JunoConfig;
use JunoPhpClient\Util\RetryHandler;
use JunoPhpClient\Util\JunoLogger;

class JunoClient {
    private $connectionPool;
    private $config;
    private $retryHandler;
    private $logger;

    public function __construct(JunoConfig $config, JunoLogger $logger) {
        $this->config = $config;
        $this->connectionPool = new ConnectionPool($config);
        $this->retryHandler = new RetryHandler(
            $config->get('max_retries', 3),
            $config->get('retry_delay', 100)
        );
        $this->logger = $logger;
    }

    /**
     * Creates a new key-value pair in JunoDB.
     *
     * @param string $key The key to create
     * @param mixed $value The value to associate with the key
     * @param int|null $lifetime The lifetime of the key in seconds (optional)
     * @return bool True if the creation was successful
     * @throws JunoException If the creation fails
     */
    public function create($key, $value, $lifetime = null) {
        $this->logger->info("Creating key: {$key}");
        return $this->retryHandler->execute(function() use ($key, $value, $lifetime) {
            $connection = $this->connectionPool->getConnection();
            try {
                $lifetime = $lifetime ?? $this->config->get('default_record_lifetime_sec', 259200);
                $response = $connection->send('CREATE', $key, $value, $lifetime);
                if ($response['status'] !== 'success') {
                    throw new JunoException("Failed to create key: " . $key);
                }
                return true;
            } finally {
                $this->connectionPool->releaseConnection($connection);
            }
        });
    }

    /**
     * Retrieves a value from JunoDB.
     *
     * @param string $key The key to retrieve
     * @return mixed The value associated with the key
     * @throws JunoException If the retrieval fails
     */
    public function get($key) {
        $this->logger->info("Getting key: {$key}");
        return $this->retryHandler->execute(function() use ($key) {
            $connection = $this->connectionPool->getConnection();
            try {
                $response = $connection->send('GET', $key);
                if ($response['status'] !== 'success') {
                    throw new JunoException("Failed to get key: " . $key);
                }
                return $response['value'];
            } finally {
                $this->connectionPool->releaseConnection($connection);
            }
        });
    }

    /**
     * Updates an existing key-value pair in JunoDB.
     *
     * @param string $key The key to update
     * @param mixed $value The new value to associate with the key
     * @param int|null $lifetime The new lifetime of the key in seconds (optional)
     * @return bool True if the update was successful
     * @throws JunoException If the update fails
     */
    public function update($key, $value, $lifetime = null) {
        $this->logger->info("Updating key: {$key}");
        return $this->retryHandler->execute(function() use ($key, $value, $lifetime) {
            $connection = $this->connectionPool->getConnection();
            try {
                $lifetime = $lifetime ?? $this->config->get('default_record_lifetime_sec', 259200);
                $response = $connection->send('UPDATE', $key, $value, $lifetime);
                if ($response['status'] !== 'success') {
                    throw new JunoException("Failed to update key: " . $key);
                }
                return true;
            } finally {
                $this->connectionPool->releaseConnection($connection);
            }
        });
    }

    /**
     * Sets a key-value pair in JunoDB (creates if not exists, updates if exists).
     *
     * @param string $key The key to set
     * @param mixed $value The value to associate with the key
     * @param int|null $lifetime The lifetime of the key in seconds (optional)
     * @return bool True if the set operation was successful
     * @throws JunoException If the set operation fails
     */
    public function set($key, $value, $lifetime = null) {
        $this->logger->info("Setting key: {$key}");
        return $this->retryHandler->execute(function() use ($key, $value, $lifetime) {
            $connection = $this->connectionPool->getConnection();
            try {
                $lifetime = $lifetime ?? $this->config->get('default_record_lifetime_sec', 259200);
                $response = $connection->send('SET', $key, $value, $lifetime);
                if ($response['status'] !== 'success') {
                    throw new JunoException("Failed to set key: " . $key);
                }
                return true;
            } finally {
                $this->connectionPool->releaseConnection($connection);
            }
        });
    }

    /**
     * Destroys a key-value pair in JunoDB.
     *
     * @param string $key The key to destroy
     * @return bool True if the destroy operation was successful
     * @throws JunoException If the destroy operation fails
     */
    public function destroy($key) {
        $this->logger->info("Destroying key: {$key}");
        return $this->retryHandler->execute(function() use ($key) {
            $connection = $this->connectionPool->getConnection();
            try {
                $response = $connection->send('DESTROY', $key);
                if ($response['status'] !== 'success') {
                    throw new JunoException("Failed to destroy key: " . $key);
                }
                return true;
            } finally {
                $this->connectionPool->releaseConnection($connection);
            }
        });
    }

    /**
     * Performs a compare-and-set operation in JunoDB.
     *
     * @param string $key The key to compare and set
     * @param mixed $value The new value to set if comparison succeeds
     * @param mixed $context The context to compare against
     * @param int|null $lifetime The new lifetime of the key in seconds (optional)
     * @return bool True if the compare-and-set operation was successful
     * @throws JunoException If the compare-and-set operation fails
     */
    public function compareAndSet($key, $value, $context, $lifetime = null) {
        $this->logger->info("Comparing and setting key: {$key}");
        return $this->retryHandler->execute(function() use ($key, $value, $context, $lifetime) {
            $connection = $this->connectionPool->getConnection();
            try {
                $lifetime = $lifetime ?? $this->config->get('default_record_lifetime_sec', 259200);
                $response = $connection->send('CAS', $key, $value, $lifetime, $context);
                if ($response['status'] !== 'success') {
                    throw new JunoException("Failed to compare and set key: " . $key);
                }
                return true;
            } finally {
                $this->connectionPool->releaseConnection($connection);
            }
        });
    }

    /**
     * Performs a batch operation in JunoDB.
     *
     * @param array $requests An array of requests to process in batch
     * @return array The results of the batch operation
     * @throws JunoException If the batch operation fails
     */
    public function doBatch(array $requests) {
        $this->logger->info("Performing batch operation");
        return $this->retryHandler->execute(function() use ($requests) {
            $connection = $this->connectionPool->getConnection();
            try {
                $response = $connection->sendBatch($requests);
                if ($response['status'] !== 'success') {
                    throw new JunoException("Failed to execute batch operation");
                }
                return $response['results'];
            } finally {
                $this->connectionPool->releaseConnection($connection);
            }
        });
    }
}
