<?php

namespace JunoPhpClient\Net;

use JunoPhpClient\Transport\Socket\SocketConfigHolder;
use JunoPhpClient\Util\JunoLogLevel;
use JunoPhpClient\Util\JunoStatusCode;
use JunoPhpClient\Net\TestEvent;

class Scheduler
{
  private int $connectionLifeTime;
  private WorkerPool $pool;
  private SocketConfigHolder $config;
  private array $locks = [];
  private array $waitQueue = [];
  private bool $ready = false;
  private const LOG_CYCLE = 20;

  public function __construct(int $lifeTime, int $poolSize, WorkerPool $pool, SocketConfigHolder $config)
  {
    $this->connectionLifeTime = $lifeTime;
    $this->pool = $pool;
    $this->config = $config;

    for ($i = 0; $i < $poolSize; $i++) {
      $this->locks[] = [
        'lock' => false,
        'owner' => 1,
        'connected' => false
      ];
    }
  }

  private function lockAny(): int
  {
    for ($i = 0; $i < count($this->locks); $i++) {
      if (!$this->locks[$i]['lock']) {
        $this->locks[$i]['lock'] = true;
        return $i;
      }
    }

    $this->pool->addWorker();
    return -1;
  }

  public function acquireConnectLock(int $index): int
  {
    if ($index >= 0 && $this->locks[$index]['lock']) {
      return $index;
    }

    while (true) {
      $k = $this->lockAny();
      if ($k >= 0) {
        return $k;
      }

      $this->waitQueue[] = true;
      return $this->acquireConnectLock($index);
    }
  }

  public function getConnectLogLevel(int $lockIndex): JunoLogLevel
  {
    $level = JunoLogLevel::OFF;
    if ($lockIndex < 0) {
      return $level;
    }

    $id = $this->locks[$lockIndex]['owner'];
    if ($id < self::LOG_CYCLE / 2 || ($id % self::LOG_CYCLE) == 0) {
      $level = JunoLogLevel::INFO;
    }

    return $level;
  }

  public function getDisconnectLogLevel(int $ownerId): JunoLogLevel
  {
    if ($ownerId < self::LOG_CYCLE / 2 || ($ownerId % self::LOG_CYCLE) == 0) {
      return JunoLogLevel::INFO;
    }

    return JunoLogLevel::OFF;
  }

  public function setConnectOwner(int $index): int
  {
    $this->setConnected($index);
    return ++$this->locks[$index]['owner'];
  }

  public function connectOwnerExpired(int $index, int $ownerId): bool
  {
    if ($index < 0) {
      return true;
    }
    if ($this->locks[$index]['lock']) {
      $this->locks[$index]['lock'] = false;
      array_shift($this->waitQueue);
      return false;
    }

    return ($this->locks[$index]['owner'] != $ownerId);
  }

  public function selectTimeSlot(): int
  {
    $x = time() + $this->connectionLifeTime;
    if ($this->connectionLifeTime >= 10000) {
      $x -= mt_rand(0, 5000);
    }

    return $x;
  }

  private function setConnected(int $index): void
  {
    if ($index < 0 || $index >= count($this->locks)) {
      return;
    }

    $this->locks[$index]['connected'] = true;
  }

  public function setDisconnected(int $index, int $ownerId): void
  {
    if ($index < 0 || $index >= count($this->locks)) {
      return;
    }

    if ($ownerId != $this->locks[$index]['owner']) {
      return;
    }

    $this->locks[$index]['connected'] = false;
  }

  public function isConnected(): bool
  {
    foreach ($this->locks as $lock) {
      if ($lock['connected']) {
        return true;
      }
    }
    return false;
  }

  public function isIndexedChannelConnected(int $index): bool
  {
    if ($index < 0 || $index >= count($this->locks)) {
      return false;
    }
    return $this->locks[$index]['connected'];
  }

  public function waitForReady(int $connectionTimeout): bool
  {
    $waitTime = 50;
    $count = 3 * $connectionTimeout / $waitTime;

    for ($i = 0; $i < $count; $i++) {
      if ($this->isConnected()) {
        error_log("Worker pool ready.");
        return true;
      }

      usleep($waitTime * 1000);
    }

    return false;
  }

  public function isTestMode(): bool
  {
    return $this->config->isTestMode();
  }

  public function onEvent(TestEvent $event): bool
  {
    if (!$this->isTestMode()) {
      return false;
    }

    $val = $event->maskedValue();
    if ($val == 0) {
      return false;
    }

    error_log("On test event: " . $event);
    $trans = [
      'name' => 'JUNO_TEST',
      'event' => $val,
      'status' => JunoStatusCode::WARNING->value,
    ];
    error_log(JunoStatusCode::WARNING->value . " " . json_encode($trans));
    return true;
  }

  public function onException(TestEvent $event): void
  {
    if (!$this->onEvent($event)) {
      return;
    }

    $event->triggerException();
  }
}
