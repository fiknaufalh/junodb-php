<?php
namespace JunoPhpClient\Net;
abstract class BaseProcessor
{
  protected array $pingRespQueue = [];
  public function useLTM(): bool
  {
    return false;
  }

  public function setPingIp(string $ip): void
  {
    $this->pingRespQueue[] = $ip;
  }

  public function getPingIp(): ?string
  {
    if (empty($this->pingRespQueue)) {
      return null;
    }
    return array_shift($this->pingRespQueue);
  }

  public function clearPingRespQueue(): void
  {
    $this->pingRespQueue = [];
  }
}