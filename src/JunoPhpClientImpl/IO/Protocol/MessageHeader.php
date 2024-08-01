<?php

namespace JunoPhpClient\IO\Protocol;

use Psr\Log\LoggerInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\LoggerAwareInterface;
use JunoPhpClient\Exception\JunoException;

class MessageHeader implements LoggerAwareInterface {
    use LoggerAwareTrait;

    private const MESSAGE_HEADER_MAGIC = 0x5050;
    private const PROTOCOL_VERSION = 1;

    private int $magic = 0;
    private int $version = 0;
    private int $msgType = 0;
    private int $messageRQ = 0;
    private int $messageSize = 0;
    private int $opaque = 0;
    private int $opcode;
    private int $flags;
    private int $vbucket;
    private int $status;

    public function __construct(LoggerInterface $logger) {
        $this->setLogger($logger);
        $this->magic = self::MESSAGE_HEADER_MAGIC;
        $this->version = self::PROTOCOL_VERSION;
        $this->msgType = MessageType::OperationalMessage->value;
        $this->messageRQ = MessageRQ::TwoWayRequest->value;
        $this->flags = 0;
    }

    public function getMagic(): int {
        return $this->magic;
    }

    public function getMessageSize(): int {
        return $this->messageSize;
    }

    public function setMessageSize(int $messageSize): void {
        $this->messageSize = $messageSize;
    }

    public function setVersion(int $version): void {
        $this->version = $version;
    }

    public function setMsgType(int $msgType): void {
        $this->msgType = $msgType;
    }

    public function setMessageRQ(int $messageRQ): void {
        $this->messageRQ = $messageRQ;
    }

    public function getOpaque(): int {
        return $this->opaque;
    }

    public function setOpaque(int $opaque): void {
        $this->opaque = $opaque;
    }

    public function getOpcode(): int {
        return $this->opcode;
    }

    public function setOpcode(int $opcode): void {
        $this->opcode = $opcode;
    }

    public function setFlags(int $flags): void {
        $this->flags = $flags;
    }

    public function getStatus(): int {
        return $this->status;
    }

    public function setStatus(int $status): void {
        $this->status = $status;
    }

    public function setMagic(int $magic): void {
        $this->magic = $magic;
    }

    public static function size(): int {
        return 16;
    }

    public function writeBuf($out): void {
      $out .= pack('nC', $this->getMagic(), $this->version);
      $tmp = $this->msgType | ($this->messageRQ << 6);
      $out .= pack('C', $tmp);
      $out .= pack('N', $this->messageSize);
      $out .= pack('N', $this->opaque);
      $out .= pack('C', $this->opcode);

      if ($this->logger instanceof \Monolog\Logger) {
        if ($this->logger->isHandling(\Psr\Log\LogLevel::DEBUG)) {
            $this->logger->debug("Operation: " . $this->opcode);
        }
      } else {
          $this->logger->debug("Operation: " . $this->opcode);
      }

      $out .= pack('C', $this->flags);

      if ($this->messageRQ === MessageRQ::Response->value) {
          $out .= pack('n', $this->status);
      } else {
          $out .= pack('n', $this->vbucket & 0xFFFF);
      }
  }

  public function readBuf($in): self {
      $offset = 0;
      list($this->magic) = unpack('n', substr($in, $offset, 2));
      $offset += 2;

      if (self::MESSAGE_HEADER_MAGIC !== $this->magic) {
          throw new JunoException("Magic check failed");
      }

      list($this->version) = unpack('C', substr($in, $offset, 1));
      $offset += 1;

      list($tmp) = unpack('C', substr($in, $offset, 1));
      $this->msgType = $tmp & 0x3F;
      $this->messageRQ = $tmp >> 6;
      $offset += 1;

      list($this->messageSize) = unpack('N', substr($in, $offset, 4));
      $offset += 4;

      list($this->opaque) = unpack('N', substr($in, $offset, 4));
      $offset += 4;

      list($this->opcode) = unpack('C', substr($in, $offset, 1));
      $offset += 1;

      list($this->flags) = unpack('C', substr($in, $offset, 1));
      $offset += 1;

      $offset += 1; // Skipping reserved byte

      list($this->status) = unpack('C', substr($in, $offset, 1));
      $offset += 1;

      return $this;
  }
}

enum MessageOpcode: int {
    case Nop = 0x0;
    case Create = 0x1;
    case Get = 0x2;
    case Update = 0x3;
    case Set = 0x4;
    case Destroy = 0x5;
    case PrepareCreate = 0x81;
    case Read = 0x82;
    case PrepareUpdate = 0x83;
    case PrepareSet = 0x84;
    case PrepareDelete = 0x85;
    case Delete = 0x86;
    case Commit = 0xC1;
    case Abort = 0xC2;
    case Repair = 0xC3;
    case MarkDelete = 0xC4;
    case Clone = 0xE1;
    case MockSetParam = 0xFE;
    case MockReSet = 0xFF;
}

enum MessageRQ: int {
    case Response = 0;
    case TwoWayRequest = 1;
    case OneWayRequest = 2;
}

enum MessageType: int {
    case OperationalMessage = 0;
    case AdminMessage = 1;
    case ClusterControlMessage = 2;
}
