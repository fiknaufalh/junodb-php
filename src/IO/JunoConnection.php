<?php
namespace JunoPhpClient\IO;

use JunoPhpClient\Exception\JunoException;
use JunoPhpClient\Util\JunoConfig;
use JunoPhpClient\Util\PayloadCompressor;

class JunoConnection {
    private $config;
    private $socket;
    private $lastUsedTime;

    public function __construct(JunoConfig $config) {
        $this->config = $config;
        $this->connect();
        $this->lastUsedTime = time();
    }

    private function connect() {
        $host = $this->config->get('server.host');
        $port = $this->config->get('server.port');
        $this->socket = @fsockopen($host, $port, $errno, $errstr, $this->config->get('connection.timeout_msec', 1000) / 1000);
        if (!$this->socket) {
            throw new JunoException("Failed to connect to JunoDB server: $errstr ($errno)");
        }
    }

    public function send($operation, $key, $value = null, $lifetime = null, $context = null) {
        $this->lastUsedTime = time();
        $request = $this->buildRequest($operation, $key, $value, $lifetime, $context);
        $this->writeToSocket($request);
        return $this->readFromSocket();
    }

    public function sendBatch(array $requests) {
        $batchRequest = $this->buildBatchRequest($requests);
        $this->writeToSocket($batchRequest);
        return $this->readFromSocket();
    }

    private function buildRequest($operation, $key, $value = null, $lifetime = null, $context = null) {
        $request = [
            'operation' => $operation,
            'key' => $key,
        ];

        if ($value !== null) {
            $request['value'] = $value;
        }

        if ($lifetime !== null) {
            $request['lifetime'] = $lifetime;
        }

        if ($context !== null) {
            $request['context'] = $context;
        }

        return json_encode($request);
    }

    private function buildBatchRequest(array $requests) {
        $batchRequests = array_map(function($req) {
            return $this->buildRequest($req['operation'], $req['key'], $req['value'] ?? null, $req['lifetime'] ?? null, $req['context'] ?? null);
        }, $requests);

        return json_encode(['batch' => $batchRequests]);
    }
    private function writeToSocket($data) {
        if ($this->config->get('usePayloadCompression', false)) {
            $data = PayloadCompressor::compress($data);
        }
        fwrite($this->socket, $data);
    }

    private function readFromSocket() {
        $response = fread($this->socket, 4096);
        if ($this->config->get('usePayloadCompression', false)) {
            $response = PayloadCompressor::decompress($response);
        }
        return json_decode($response, true);
    }

    public function getLastUsedTime() {
        return $this->lastUsedTime;
    }

    public function close() {
        if ($this->socket) {
            fclose($this->socket);
            $this->socket = null;
        }
    }

    public function __destruct() {
        $this->close();
    }
}
