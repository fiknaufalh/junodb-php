<?php
namespace JunoPhpClient\IO\Protocol;
class MetaOperationMessage
{
  private int $componentSize;
  private int $tag;
  private int $version;
  private int $ttl;
  private int $creationTime;
  private int $expirationTime;
  private int $requestHandlingTime;
  private string $requestId;
  private ?string $requestUuid = null;
  /** @var MetaMessageTagAndType[] */
  private array $fieldList = [];

  public function __construct(int $componentSize, int $tag)
  {
    $this->componentSize = $componentSize;
    $this->tag = $tag;
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

  public function getVersion(): int
  {
    return $this->version;
  }

  public function setVersion(int $version): void
  {
    $this->version = $version;
  }

  public function getTtl(): int
  {
    return $this->ttl;
  }

  public function setTtl(int $ttl): void
  {
    $this->ttl = $ttl;
  }

  public function getCreationTime(): int
  {
    return $this->creationTime;
  }

  public function setCreationTime(int $creationTime): void
  {
    $this->creationTime = $creationTime;
  }

  public function getExpirationTime(): int
  {
    return $this->expirationTime;
  }

  public function setExpirationTime(int $expirationTime): void
  {
    $this->expirationTime = $expirationTime;
  }

  public function getRequestHandlingTime(): int
  {
    return $this->requestHandlingTime;
  }

  public function setRequestHandlingTime(int $requestHandlingTime): void
  {
    $this->requestHandlingTime = $requestHandlingTime;
  }

  public function getRequestId(): string
  {
    return $this->requestId;
  }

  public function getRequestUuid(): string
  {
    return $this->requestUuid;
  }

  public function setRequestId(string $requestId): void
  {
    $this->requestId = $requestId;
  }

  public function setRequestUuid(string $requestUuid): void
  {
    $this->requestUuid = $requestUuid;
  }

  public function getRequestIdString(): string
  {
    if ($this->requestUuid === null) {
      return 'not_set';
    }
    return $this->requestUuid;
  }

  public function getCorrelationIDString(): string
  {
    $id = null;
    foreach ($this->fieldList as $item) {
      if ($item instanceof MetaMessageCorrelationIDField) {
        $id = $item->getCorrelationId();
        break;
      }
    }

    return $id ?? 'not_set';
  }

  public function addSourceField(string $ip4, int $port, string $appName): void
  {
    $source = new MetaMessageSourceField(FieldType::SourceInfo->value);
    $source->setIp4($ip4);
    $source->setPort($port);
    $source->setAppName($appName);

    $this->fieldList[] = $source;
  }

  public function getSourceField(): ?MetaMessageSourceField
  {
    foreach ($this->fieldList as $item) {
      if ($item instanceof MetaMessageSourceField) {
        return $item;
      }
    }

    return null;
  }
  
  public function getBufferLength(): int
  {
    $size = 6 + count($this->fieldList);
    $offset = $size % 4;
    if ($offset !== 0) {
      $size += 4 - $offset;
    }

    foreach ($this->fieldList as $tagAndSizeType) {
      if (!$tagAndSizeType->isVariable()) {
        $size += $tagAndSizeType->getFieldSize();
      } else {
        $size += $tagAndSizeType->getBufferLength();
      }
    }

    $offset = $size % 8;
    if ($offset !== 0) {
      $size += 8 - $offset;
    }

    $this->componentSize = $size;
    return $size;
  }

  public function readBuf(string $in): self
  {
    $indexStart = 0;
    $fields = ord($in[$indexStart++]);
    $tagAndSizeTypes = substr($in, $indexStart, $fields);
    $indexStart += $fields;

    // Escape padding here, padding to 4 bytes.
    $indexStart = $this->readBufPadding($indexStart - 4 - 1, $in, 4);

    for ($i = 0; $i < $fields; $i++) {
      $tagAndSizeType = new MetaMessageTagAndType(ord($tagAndSizeTypes[$i]));
      $type = $tagAndSizeType->getFieldType();

      switch ($type) {
        case FieldType::CreationTime:
        case FieldType::ExpirationTime:
        case FieldType::RequestHandlingTime:
        case FieldType::RequestID:
        case FieldType::TimeToLive:
        case FieldType::Version:
          $field = new MetaMessageFixedField(ord($tagAndSizeTypes[$i]));
          $field->readBuf(substr($in, $indexStart));
          $this->fieldList[] = $field;
          $indexStart += $field->getLength();

          if ($type === FieldType::CreationTime) {
            $this->creationTime = $field->getContent();
          } elseif ($type === FieldType::ExpirationTime) {
            $this->expirationTime = $field->getContent();
          } elseif ($type === FieldType::RequestHandlingTime) {
            $this->requestHandlingTime = $field->getContent();
          } elseif ($type === FieldType::RequestID) {
            $this->setRequestId($field->getVariableContent());
          } elseif ($type === FieldType::TimeToLive) {
            $this->ttl = $field->getContent();
          } elseif ($type === FieldType::Version) {
            $this->version = $field->getContent();
          }
          break;

        case FieldType::SourceInfo:
          $field = new MetaMessageSourceField(ord($tagAndSizeTypes[$i]));
          $field->readBuf(substr($in, $indexStart));
          $this->fieldList[] = $field;
          $indexStart += $field->getBufferLength();
          break;

        default:
          // Here we just need to skip the bytes for unknown Field type
          if ($tagAndSizeType->isVariable()) {
            $size = ord($in[$indexStart++]); // The size of the variable length tag is found at the first byte of the Tag body
            $indexStart += $size - 1; // Since the size is inclusive of itself so (size - 1).
          } else {
            $len = $tagAndSizeType->getFieldSize();
            $indexStart += $len;
          }
          break;
      }
    }

    return $this;
  }

  public function writeBuf(string &$out): void
  {
    $indexStart = strlen($out);
    $out .= pack('N', $this->componentSize);
    $out .= pack('C', $this->tag);
    $out .= pack('C', count($this->fieldList));

    foreach ($this->fieldList as $field) {
      $out .= pack('C', $field->getValue());
    }

    // Add padding if necessary
    $this->writeBufPadding($indexStart, $out, 4);

    // Write the actual Meta data (Body)
    foreach ($this->fieldList as $field) {
      $field->writeBuf($out);
    }
  }

  private function readBufPadding(int $start, string $in, int $padding): int
  {
    $endIndex = $start + 5;
    $offset = ($endIndex - $start) % $padding;
    if ($offset !== 0) {
      $endIndex += $padding - $offset;
    }
    return $endIndex;
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