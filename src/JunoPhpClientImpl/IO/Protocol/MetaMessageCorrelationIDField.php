<?php

namespace JunoPhpClient\IO\Protocol;

class MetaMessageCorrelationIDField extends MetaMessageTagAndType
{
  private int $componentSize;
  private int $correlationIdLength;
  private string $correlationId;
  public function __construct(int $tagAndSizeType)
  {
    parent::__construct($tagAndSizeType);
  }

  public function setCorrelationId(string $correlationId): void
  {
    $this->correlationId = $correlationId;
    $this->correlationIdLength = strlen($correlationId);
    $this->componentSize = 1 + 1 + $this->correlationIdLength;
    $offset = $this->componentSize % 4;
    if ($offset !== 0) {
      $this->componentSize += 4 - $offset;
    }
  }

  public function getBufferLength(): int
  {
    return $this->componentSize;
  }

  public function getCorrelationId(): string
  {
    return $this->correlationId;
  }

  public function writeBuf(string &$out): void
  {
    $indexStart = strlen($out);
    $out .= pack('C', $this->componentSize);
    $out .= pack('C', $this->correlationIdLength);
    if ($this->correlationId !== null) {
      $out .= $this->correlationId;
    }

    $this->writeBufPadding($indexStart, $out, 4);
  }

  private function writeBufPadding(int $start, string &$out, int $padding): void
  {
    $endIndex = strlen($out);
    $offset = ($endIndex - $start) % $padding;
    if ($offset !== 0) {
      $out .= str_repeat("\0", $padding - $offset);
    }
  }
}
