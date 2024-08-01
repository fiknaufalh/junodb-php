<?php
namespace JunoPhpClient\Net;
use JunoPhpClient\IO\Protocol\OperationMessage;
use JunoPhpClient\Net\TestEvent;
use JunoPhpClient\Util\JunoMetrics;
use JunoPhpClient\Util\JunoStatusCode;
use React\Socket\ConnectionInterface;

class ClientHandler
{
  private IOProcessor $processor;
  public function __construct(IOProcessor $processor)
  {
    $this->processor = $processor;
  }

  public function handleData(ConnectionInterface $connection, string $data): void
  {
    $this->processor->incrementRecvCount();
    $operationMessage = $this->decodeOperationMessage($data);

    if (!PingMessage::isPingResp($operationMessage, $this->processor)) {
      $this->processor->putResponse($operationMessage);
    }

    if ($this->processor->onEvent(TestEvent::READ_FAIL)) {
      $this->exceptionCaught($connection, new \RuntimeException("Test Read Fail"));
    }
  }

  public function handleClose(ConnectionInterface $connection): void
  {
    $this->processor->validateMsgCount();
  }

  public function exceptionCaught(ConnectionInterface $connection, \Throwable $cause): void
  {
    if ($cause instanceof \Error) {
      $connection->close();
      return;
    }

    $trans = [
      'name' => 'JUNO_RECEIVE',
      'server' => $this->processor->getServerAddr(),
      'error' => $cause->getMessage(),
      'status' => JunoStatusCode::ERROR->value,
    ];

    error_log("ClientHandler Error : " . json_encode($trans));
    $connection->close();
    JunoMetrics::recordErrorCount("JUNO_RECEIVE", $this->processor->getRemoteIpAddr(), get_class($cause));
  }

  private function decodeOperationMessage(string $data): OperationMessage
  {
    $operationMessage = new OperationMessage();
    $operationMessage->readBuf($data);
    return $operationMessage;
  }
}