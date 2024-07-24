<?php
namespace JunoPhpClient\IO;

use JunoPhpClient\Util\JunoConfig;

class ConnectionPool {
    private $config;
    private $connections = [];
    private $maxConnections;
    private $connectionUsage = [];

    public function __construct(JunoConfig $config) {
        $this->config = $config;
        $this->maxConnections = $config->get('max_connections', 10);
    }

    public function getConnection() {
        if (count($this->connections) < $this->maxConnections) {
            $connection = new JunoConnection($this->config);
            $this->connections[] = $connection;
            $this->connectionUsage[spl_object_hash($connection)] = 0;
            return $connection;
        }

        return $this->getLeastBusyConnection();
    }

    private function getLeastBusyConnection() {
        $leastBusyConnection = null;
        $leastUsage = PHP_INT_MAX;

        foreach ($this->connections as $connection) {
            $hash = spl_object_hash($connection);
            if ($this->connectionUsage[$hash] < $leastUsage) {
                $leastBusyConnection = $connection;
                $leastUsage = $this->connectionUsage[$hash];
            }
        }

        if ($leastBusyConnection !== null) {
            $this->connectionUsage[spl_object_hash($leastBusyConnection)]++;
        }
        return $leastBusyConnection;
    }

    public function releaseConnection(JunoConnection $connection) {
        $hash = spl_object_hash($connection);
        if (isset($this->connectionUsage[$hash])) {
            $this->connectionUsage[$hash]--;
        }

        // Optionally, we can close idle connections if they've been unused for a while
        $this->closeIdleConnections();
    }

    private function closeIdleConnections() {
        $idleTimeout = $this->config->get('idle_connection_timeout', 300); // 5 minutes default
        foreach ($this->connections as $index => $connection) {
            $hash = spl_object_hash($connection);
            if ($this->connectionUsage[$hash] == 0 && (time() - $connection->getLastUsedTime() > $idleTimeout)) {
                $connection->close();
                unset($this->connections[$index]);
                unset($this->connectionUsage[$hash]);
            }
        }
    }
}
