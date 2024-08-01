<?php
namespace JunoPhpClient\Net;
use JunoPhpClient\IO\Protocol\MessageHeader;
use JunoPhpClient\IO\Protocol\OperationMessage;
use Monolog\Logger;

class MessageDecoder
{
  private bool $parseHeader = true;
  private ?MessageHeader $header = null;
  private int $bodySize = 0;
  public function decode(string $data): ?OperationMessage
  {
    $buffer = $data;
    $bufferLength = strlen($buffer);

    if ($this->parseHeader) {
      if ($bufferLength < 16) {
        return null;
      }
      $this->header = new MessageHeader(new Logger('MessageDecoder'));
      $this->header->readBuf(substr($buffer, 0, 16));
      $this->bodySize = $this->header->getMessageSize() - 16;
      $this->parseHeader = false;
      $buffer = substr($buffer, 16);
      $bufferLength -= 16;
    }

    if ($bufferLength < $this->bodySize) {
      return null;
    }

    $opMsg = new OperationMessage();
    $opMsg->setHeader($this->header);
    $this->header = null;

    $opMsg->readBuf($buffer);
    $serverIp = $_SERVER['REMOTE_ADDR']; // This might need to be adjusted based on your setup
    $opMsg->setServerIp($serverIp);

    $this->parseHeader = true;
    $this->bodySize = 0;

    return $opMsg;
  }
}