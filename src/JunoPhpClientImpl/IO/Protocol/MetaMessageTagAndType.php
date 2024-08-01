<?php
namespace JunoPhpClient\IO\Protocol;

class MetaMessageTagAndType
{
  protected FieldType $fieldType;
  protected int $fieldSize;
  protected bool $isVariable;

  public function __construct(int $tagAndSizeType)
  {
    $tmp = $tagAndSizeType & 0xFF;
    $this->fieldType = FieldType::from(min($tmp & 0x1F, 10));

    if (($tmp >> 5) === 0) {
      $this->isVariable = true;
      $this->fieldSize = 0;
    } else {
      $this->fieldSize = $tmp >> 5;
      $this->isVariable = false;
    }
  }

  public function isVariable(): bool
  {
    return $this->isVariable;
  }

  public function getFieldType(): FieldType
  {
    return $this->fieldType;
  }

  public function getFieldSize(): int
  {
    return 1 << (1 + $this->fieldSize);
  }

  public function getValue(): int
  {
    $value = $this->fieldType->value;
    if (!$this->isVariable) {
      $value |= $this->fieldSize << 5;
    }
    return $value;
  }

  public function writeBuf(string &$out): void
  {
    throw new \RuntimeException("writeBuf not implemented in sub class.");
  }

  public function getBufferLength(): int
  {
    return 0;
  }
}

enum FieldType: int
{
  case Dummy = 0;
  case TimeToLive = 1;
  case Version = 2;
  case CreationTime = 3;
  case ExpirationTime = 4;
  case RequestID = 5;
  case SourceInfo = 6;
  case LastModificationTime = 7;
  case OriginatorReqID = 8;
  case CorrelationID = 9;
  case RequestHandlingTime = 10;
}
