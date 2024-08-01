<?php

namespace JunoPhpClient\Net;

use JunoPhpClient\IO\Protocol\MessageHeader;
use JunoPhpClient\IO\Protocol\MessageType;
use JunoPhpClient\IO\Protocol\MessageRQ;
use JunoPhpClient\IO\Protocol\MessageOpcode;
use JunoPhpClient\IO\Protocol\MetaOperationMessage;
use JunoPhpClient\IO\Protocol\OperationMessage;
use JunoPhpClient\Client\ServerOperationStatus;
use Monolog\Logger;

class PingMessage extends OperationMessage
{
  private static ?string $localAddress = null;
  public function __construct(?string $appName, int $opaque)
  {
    $header = new MessageHeader(new Logger('PingMessage'));

    $header->setMsgType(MessageType::OperationalMessage->value);
    $header->setFlags(0); // This field is not significant for client.
    $header->setMessageRQ(MessageRQ::TwoWayRequest->value);
    $header->setOpcode(MessageOpcode::Nop->value);
    $header->setOpaque($opaque);
    $header->setStatus(ServerOperationStatus::BadMsg->value);
    $this->setHeader($header);

    $mo = new MetaOperationMessage(0, OperationMessage::TYPE_META);
    if ($appName === null) {
      $appName = "JunoInternal";
    }
    $mo->addSourceField($this->getLocalAddress(), 0, $appName);

    $this->setMetaComponent($mo);
  }

  public function pack(): string
  {
    $out = '';
    $this->writeBuf($out);
    return $out;
  }

  private static function getLocalAddress(): string
  {
    if (self::$localAddress !== null) {
      return self::$localAddress;
    }

    try {
      self::$localAddress = gethostbyname(gethostname());
    } catch (\Exception $e) {
      self::$localAddress = '127.0.0.1';
    }
    return self::$localAddress;
  }

  public static function isPingResp(OperationMessage $op, BaseProcessor $processor): bool
  {
    if ($processor->useLTM()) {
      return false;
    }

    $header = $op->getHeader();
    if ($header === null || ($header->getOpcode() !== MessageOpcode::Nop->value)) {
      return false;  // not a Nop response
    }

    $mo = $op->getMetaComponent();
    if ($mo === null) {
      return false;
    }

    $source = $mo->getSourceField();
    if ($source === null || $source->getAppName() === null) {
      return false;
    }

    $str = $source->getAppName();
    if ($str !== "JunoInternal") {
      return false;
    }

    // Extract ip from ping response.
    $w = $source->getIp4();
    if ($w === null || strlen($w) < 4) {
      error_log("Ping resp ip=null");
      $processor->setPingIp("");
      return true;
    }

    if ($w[0] === chr(127)) {
      error_log("Ping resp ip=127.*.*.*");
      $processor->setPingIp("");
      return true;
    }

    if ($w === self::getLocalAddress()) {
      error_log("Ping resp ip same as local addr");
      $processor->setPingIp("");
      return true;
    }

    $str = long2ip(unpack('N', $w)[1]);

    // Pass ip to sending thread
    error_log("Ping resp ip=" . $str);
    $processor->setPingIp($str);
    return true;
  }
}
