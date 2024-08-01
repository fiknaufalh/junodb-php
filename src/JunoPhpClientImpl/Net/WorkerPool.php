<?php

namespace JunoPhpClient\Net;

use JunoPhpClient\Transport\Socket\SocketConfigHolder;
use React\EventLoop\LoopInterface;
use React\EventLoop\Factory;

class WorkerPool
{
    private static bool $quit = false;
    
    private int $maxWorkers;
    private array $workers = [];
    
    private SocketConfigHolder $config;
    private RequestQueue $requestQueue;
    private Scheduler $scheduler;
    private LoopInterface $loop;

    public function __construct(SocketConfigHolder $cfg, RequestQueue $queue)
    {
        $this->maxWorkers = 2 * $cfg->getConnectionPoolSize();
        $this->config = $cfg;
        $this->requestQueue = $queue;
        $this->loop = Factory::create();
        $this->scheduler = new Scheduler($cfg->getConnectionLifeTime(), $cfg->getConnectionPoolSize(), $this, $cfg);
        
        $this->init($cfg, $queue);    
        $this->scheduler->waitForReady($cfg->getConnectTimeout());
    }

    private function init(SocketConfigHolder $cfg, RequestQueue $q): void
    {
        if (!empty($this->workers)) {
            return;
        }
        
        $this->config = $cfg;
        $this->requestQueue = $q;
        
        self::$quit = false;
        
        // Add all workers.
        for ($i = 0; $i < $this->maxWorkers; $i++) {
            $this->addWorker();
        }
    }

    public function shutdown(): void
    {
        if (empty($this->workers)) {
            return;
        }
        
        error_log("Shutdown IO.");
        self::$quit = true;
        foreach ($this->workers as $worker) {
            $worker->stop();
        }
        
        $this->loop->stop();
        $this->workers = [];
    }

    public function addWorker(): void
    {
        if (count($this->workers) >= $this->maxWorkers) {
            return;
        }
        
        $worker = new IOProcessor($this->config, $this->requestQueue, $this->scheduler, $this->requestQueue->getOpaqueResMap(), $this->loop);
        $this->workers[] = $worker;
        $worker->run();
    }

    public function isConnected(): bool
    {
        return $this->scheduler->isConnected();
    }

    public static function isQuit(): bool
    {
        return self::$quit;
    }

    public function getConfig(): SocketConfigHolder
    {
        return $this->config;
    }

    public function run(): void
    {
        $this->loop->run();
    }
}