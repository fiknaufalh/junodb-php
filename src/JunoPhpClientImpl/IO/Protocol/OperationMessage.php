<?php
namespace JunoPhpClient\IO\Protocol;
use Monolog\Logger;
class OperationMessage
{
  public const TYPE_PAYLOAD = 0x1;
  public const TYPE_META = 0x2;
  private ?MessageHeader $header = null;
  private ?PayloadOperationMessage $payloadComponent = null;
  private ?MetaOperationMessage $metaComponent = null;
  private ?string $serverIp = null;

  public function getHeader(): ?MessageHeader
  {
    return $this->header;
  }

  public function setHeader(MessageHeader $header): void
  {
    $this->header = $header;
  }

  public function getLength(): int
  {
    return $this->header->getMessageSize();
  }

  public function getPayloadComponent(): ?PayloadOperationMessage
  {
    return $this->payloadComponent;
  }

  public function setPayloadComponent(PayloadOperationMessage $payloadComponent): void
  {
    $this->payloadComponent = $payloadComponent;
  }

  public function getMetaComponent(): ?MetaOperationMessage
  {
    return $this->metaComponent;
  }

  public function setMetaComponent(MetaOperationMessage $metaComponent): void
  {
    $this->metaComponent = $metaComponent;
  }

  public function getServerIp(): ?string
  {
    return $this->serverIp;
  }

  public function setServerIp(string $serverIp): void
  {
    $this->serverIp = $serverIp;
  }

  public function readBuf(string $in): self
  {
    $start = 0;
    if ($this->header === null) {
      $this->header = new MessageHeader(new Logger('OperationMessage'));
      $this->header->readBuf(substr($in, $start, MessageHeader::size()));
      $start += MessageHeader::size();
    }
    $total = $start + $this->header->getMessageSize() - MessageHeader::size();
    $index = $start;

    while ($total - $index > 0) {
      $componentSize = unpack('N', substr($in, $index, 4))[1];
      $index += 4;
      $tag = ord($in[$index++]);

      switch ($tag) {
        case self::TYPE_PAYLOAD:
          $this->payloadComponent = new PayloadOperationMessage($componentSize, $tag);
          $this->payloadComponent->readBuf(substr($in, $index));
          break;
        case self::TYPE_META:
          $this->metaComponent = new MetaOperationMessage($componentSize, $tag);
          $this->metaComponent->readBuf(substr($in, $index));
          break;
        default:
          throw new \RuntimeException("Invalid type");
      }

      $index = $this->readBufPadding($start, $in, 8);
      $start = $index;
    }

    return $this;
  }

  public function writeBuf(string &$out): void
  {
    $size = 0;
    if ($this->metaComponent !== null) {
      $size += $this->metaComponent->getBufferLength();
    }
    if ($this->payloadComponent !== null) {
      $size += $this->payloadComponent->getBufferLength();
    }

    $offset = $size % 8;
    if ($offset !== 0) {
      $size += (8 - $offset);
    }
    $size += MessageHeader::size();
    $this->header->setMessageSize($size);

    // Header
    $this->header->writeBuf($out);

    // Add meta Component
    $index = strlen($out);
    if ($this->metaComponent !== null) {
      $this->metaComponent->writeBuf($out);
      $this->writeBufPadding($index, $out, 8);
    }

    // Add Payload Component
    $index = strlen($out);
    if ($this->payloadComponent !== null) {
      $this->payloadComponent->writeBuf($out);
      $this->writeBufPadding($index, $out, 8);
    }
  }

  private static function readBufPadding(int $start, string $in, int $padding): int
  {
    $endIndex = $start;
    $offset = ($endIndex - $start) % $padding;
    if ($offset !== 0) {
      $endIndex += $padding - $offset;
    }
    return $endIndex;
  }

  private static function writeBufPadding(int $start, string &$out, int $padding): void
  {
    $endIndex = strlen($out);
    $offset = ($endIndex - $start) % $padding;
    if ($offset !== 0) {
      $out .= str_repeat("\0", $padding - $offset);
    }
  }
}