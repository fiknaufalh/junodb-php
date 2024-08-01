<?php
namespace JunoPhpClient\IO\Protocol;
class MetaMessageSourceField extends MetaMessageTagAndType
{
  private int $componentSize;
  private int $appNameLength;
  private bool $isIP6;
  private int $port;
  private string $ip4;
  private ?string $ip6 = null;
  private string $appName;
  public function __construct(int $tagAndSizeType)
  {
    parent::__construct($tagAndSizeType);
  }

  public function getPort(): int
  {
    return $this->port;
  }

  public function setPort(int $port): void
  {
    $this->port = $port;
  }

  public function getIp4(): string
  {
    return $this->ip4;
  }

  public function setIp4(string $ip4): void
  {
    $this->ip4 = $ip4;
  }

  public static function getIp4String(string $w): string
  {
    if (strlen($w) < 4) {
      return '';
    }
    $octets = unpack('C*', $w);
    return implode('.', $octets);
  }

  public function getAppName(): string
  {
    return $this->appName;
  }

  public function getBufferLength(): int
  {
    $this->componentSize = 1 + 1 + 2 + ($this->isIP6 ? 16 : 4) + (($this->appName === null) ? 0 : strlen($this->appName));
    $offset = $this->componentSize % 4;
    if ($offset !== 0) {
      $this->componentSize += 4 - $offset;
    }
    return $this->componentSize;
  }

  public function setAppName(string $appName): void
  {
    $this->appName = $appName;
    $this->appNameLength = strlen($appName);
  }

  public function readBuf(string $in): self
  {
    $index = 0;
    $this->componentSize = ord($in[$index++]);
    $this->appNameLength = ord($in[$index++]);
    $this->isIP6 = ($this->appNameLength & 0x80) === 0x80;
    $this->appNameLength &= 0x7F;
    $this->port = unpack('n', substr($in, $index, 2))[1];
    $index += 2;

    if ($this->isIP6) {
      $this->ip6 = substr($in, $index, 16);
      $index += 16;
    } else {
      $this->ip4 = substr($in, $index, 4);
      $index += 4;
    }

    $this->appName = substr($in, $index, $this->appNameLength);
    $index += $this->appNameLength;

    $tail = ($this->componentSize & 0xff) - $index;
    if ($tail > 0) {
      // Skip padding
      $index += $tail;
    }

    return $this;
  }

  public function writeBuf(string &$out): void
  {
    $indexStart = strlen($out);
    $out .= pack('C', $this->componentSize);
    $out .= pack('C', $this->appNameLength | ($this->isIP6 ? 0x80 : 0));
    $out .= pack('n', $this->port);

    if ($this->isIP6) {
      Assert::isTrue("IP6", $this->ip6 !== null);
      $out .= $this->ip6;
    } else {
      $out .= $this->ip4;
    }

    if ($this->appName !== null) {
      $out .= $this->appName;
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