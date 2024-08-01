<?php
namespace JunoPhpClient\IO\Protocol;
class PayloadOperationMessage
{
  private int $componentSize;
  private int $tag;
  private int $valueLength;
  private int $nameSpaceLength;
  private int $keyLength;
  private string $namespace;
  private string $key;
  private ?string $value;
  private bool $isPloadCompressed;
  private CompressionType $compType;
  public function __construct(int $componentSize, int $tag)
  {
    $this->componentSize = $componentSize;
    $this->tag = $tag;
    $this->isPloadCompressed = false;
    $this->compType = CompressionType::None;
  }

  // Getters and setters...
  public function getComponentSize(): int
  {
    return $this->componentSize;
  }

  public function setComponentSize(int $componentSize): void
  {
    $this->componentSize = $componentSize;
  }

  public function getTag(): int
  {
    return $this->tag;
  }

  public function setTag(int $tag): void
  {
    $this->tag = $tag;
  }

  public function getValueLength(): int
  {
    return $this->valueLength;
  }

  public function setValueLength(int $valueLength): void
  {
    $this->valueLength = $valueLength;
  }

  public function getNameSpaceLength(): int
  {
    return $this->nameSpaceLength;
  }

  public function setNameSpaceLength(int $nameSpaceLength): void
  {
    $this->nameSpaceLength = $nameSpaceLength;
  }

  public function getKeyLength(): int
  {
    return $this->keyLength;
  }

  public function setKeyLength(int $keyLength): void
  {
    $this->keyLength = $keyLength;
  }

  public function getNamespace(): string
  {
    return $this->namespace;
  }

  public function setNamespace(string $namespace): void
  {
    $this->namespace = $namespace;
  }

  public function getKey(): string
  {
    return $this->key;
  }

  public function setKey(string $key): void
  {
    $this->key = $key;
  }

  public function getValue(): ?string
  {
    return $this->value;
  }

  public function setValue(?string $value): void
  {
    $this->value = $value;
  }

  public function isPloadCompressed(): bool
  {
    return $this->isPloadCompressed;
  }

  public function setPloadCompressed(bool $isPloadCompressed): void
  {
    $this->isPloadCompressed = $isPloadCompressed;
  }

  public function getCompType(): CompressionType
  {
    return $this->compType;
  }

  public function setCompType(CompressionType $compType): void
  {
    $this->compType = $compType;
  }

  public function getBufferLength(): int
  {
    $valueFieldLen = 0;
    if ($this->valueLength !== 0) {
      $valueFieldLen = $this->valueLength + 1; // 1 byte for payload type
      if ($this->isPloadCompressed) { // compression enabled
        $valueFieldLen += 1; // 1 byte for size of compression type
        $valueFieldLen += strlen($this->compType->value);
      }
    }

    $size = 12 + strlen($this->namespace) + strlen($this->key) + $valueFieldLen;
    $offset = $size % 8;
    if ($offset !== 0) {
      $size += 8 - $offset;
    }
    $this->componentSize = $size;
    return $size;
  }

  public function readBuf(string $in): self
  {
    $this->nameSpaceLength = ord($in[0]);
    $this->keyLength = unpack('n', substr($in, 1, 2))[1];
    $valueFieldLen = unpack('N', substr($in, 3, 4))[1];

    $offset = 7;
    $this->namespace = substr($in, $offset, $this->nameSpaceLength);
    $offset += $this->nameSpaceLength;

    $this->key = substr($in, $offset, $this->keyLength);
    $offset += $this->keyLength;

    if ($valueFieldLen > 0) {
      $this->valueLength = $valueFieldLen - 1;
      $payloadType = ord($in[$offset++]);
      if ($payloadType == 3) {   // check for payload compression
        $compTypeSize = ord($in[$offset++]); // read size of compression type
        $this->valueLength--;
        $compType = substr($in, $offset, $compTypeSize);
        $offset += $compTypeSize;
        $this->valueLength -= $compTypeSize; // This is actual compressed payload size
        $this->setCompType(CompressionType::fromString($compType));
      }
      $this->value = substr($in, $offset, $this->valueLength);
    } else {
      $this->value = null;
      $this->valueLength = 0;
    }

    return $this;
  }

  public function writeBuf(string &$out): void
  {
    $out .= pack('N', $this->componentSize);
    $out .= pack('C', $this->tag);
    $out .= pack('C', $this->nameSpaceLength);
    $out .= pack('n', $this->keyLength);

    $payloadLen = 0;
    if ($this->valueLength !== 0) {
      $payloadLen = $this->valueLength + 1;
      if ($this->isPloadCompressed) { // compression enabled
        $payloadLen += 1; // 1 byte for compression type size
        $payloadLen += strlen($this->compType->value);
      }
    }
    $out .= pack('N', $payloadLen);
    $out .= $this->namespace;
    $out .= $this->key;

    if ($payloadLen !== 0) {
      if ($this->isPloadCompressed) { // if compression is enabled
        $out .= pack('C', 3);
        $out .= pack('C', strlen($this->compType->value));
        $out .= $this->compType->value;
      } else {
        $out .= "\0";
      }
      $out .= $this->value;
    }
  }

}

enum CompressionType: string
{
  case None = 'None';
  case Snappy = 'Snappy';

  public static function fromString(string $type): self
  {
    return match ($type) {
      'None' => self::None,
      'Snappy' => self::Snappy,
      default => throw new \InvalidArgumentException("Unknown compression type: $type"),
    };
  }


}