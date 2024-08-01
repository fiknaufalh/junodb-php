<?php

namespace JunoPhpClient\Net;

use JunoPhpClient\IO\Protocol\OperationMessage;
use JunoPhpClient\Transport\Socket\SocketConfigHolder;
use SplQueue;

class RequestQueue
{
  private static array $reqMap = [];
  private SplQueue $queue;
  private WorkerPool $workerPool;
  private int $responseTimeout;
  private float $average = 0.0;
  private static float $FAILURE_THRESHOLD = 0.3;
  private static int $INTERVAL = 1000;
  private static int $SAFETY_BUFFER = 30;
  private int $recycleAttempt = 0;
  private int $nextRecycleAttemptDue;
  private array $propertyChangeListeners = [];
  public function __construct(SocketConfigHolder $cfg)
  {
    $this->queue = new SplQueue();
    $this->workerPool = new WorkerPool($cfg, $this);
    $this->responseTimeout = $cfg->getResponseTimeout();
    $this->nextRecycleAttemptDue = time();
  }

  public static function getInstance(SocketConfigHolder $cfg): self
  {
    $inetAddress = $cfg->getHost() . ':' . $cfg->getPort();
    if (!isset(self::$reqMap[$inetAddress])) {
      self::$reqMap[$inetAddress] = new self($cfg);
    }
    return self::$reqMap[$inetAddress];
  }

  public function enqueue(OperationMessage $req): bool
  {
    try {
      $this->queue->push($req);
      return true;
    } catch (\Exception $e) {
      error_log("Adding message to request queue: " . $e->getMessage());
      return false;
    }
  }

  public function dequeue(): ?OperationMessage
  {
    try {
      return $this->queue->pop();
    } catch (\UnderflowException $e) {
      return null;
    }
  }

  public function isConnected(): bool
  {
    return $this->workerPool->isConnected();
  }

  public static function clear(): void
  {
    self::$reqMap = [];
  }

  public function size(): int
  {
    return $this->queue->count();
  }

  public function addPropertyChangeListener(callable $listener): void
  {
    $this->propertyChangeListeners[] = $listener;
  }

  public function incrementFailedAttempts(): void
  {
    $this->checkForUnresponsiveConnection(0, 1);
  }

  public function incrementSuccessfulAttempts(): void
  {
    $this->checkForUnresponsiveConnection(1, 0);
  }

  private function checkForUnresponsiveConnection(int $success, int $failed): void
  {
    $totalAttempts = $success + $failed + self::$SAFETY_BUFFER;
    $alpha = 0.1;
    $currentValue = $failed / $totalAttempts;
    $ema = $alpha * $currentValue + (1 - $alpha) * $this->average;
    $this->average = is_nan($ema) || is_infinite($ema) ? $this->average : $ema;
    if ($this->reconnectOnFailEnabled() && $this->average >= self::$FAILURE_THRESHOLD) {
      $this->recycleAttempt++;
      $this->nextRecycleAttemptDue = time();
      $this->firePropertyChange('recycleNow', -1, 0);
    }
  }

  private function reconnectOnFailEnabled(): bool
  {
    if (time() < $this->nextRecycleAttemptDue) {
      return false;
    }

    if ($this->recycleAttempt >= 2) {
      $this->firePropertyChange('RECYCLE_CONNECT_TIMEOUT', -1, 1);
      $this->nextRecycleAttemptDue = time() + 180; // 3 minutes
      $this->recycleAttempt = 0;
      return false;
    }

    return true;
  }

  public function resetValues(): void
  {
    $this->average = 0.0;
  }

  public function getAverage(): float
  {
    return $this->average;
  }

  private function firePropertyChange(string $propertyName, $oldValue, $newValue): void
  {
    $event = [
      'source' => $this,
      'propertyName' => $propertyName,
      'oldValue' => $oldValue,
      'newValue' => $newValue
    ];
    foreach ($this->propertyChangeListeners as $listener) {
      $listener($event);
    }
  }
}
