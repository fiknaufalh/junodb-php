<?php
namespace JunoPhpClient\Util;

use JunoPhpClient\Exception\JunoException;

class RetryHandler {
    private $maxRetries;
    private $retryDelay;

    public function __construct($maxRetries = 3, $retryDelay = 100) {
        $this->maxRetries = $maxRetries;
        $this->retryDelay = $retryDelay;
    }

    public function execute(callable $operation) {
        $retries = 0;
        while (true) {
            try {
                return $operation();
            } catch (JunoException $e) {
                if (++$retries > $this->maxRetries) {
                    throw $e;
                }
                usleep($this->retryDelay * 1000);
            }
        }
    }
}
