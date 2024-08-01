<?php

namespace JunoPhpClient\Util;

use JunoPhpClient\Client\ServerOperationStatus;
use JunoPhpClient\IO\Protocol\JunoMessage;
use JunoPhpClient\IO\Protocol\OperationMessage;
use JunoPhpClient\Net\RequestQueue;

class SendBatch
{
    private int $batchOpaque;
    private RequestQueue $reqQueue;
    private array $reqIdReqMsgMap;
    private int $reqCount;

    public function __construct(int $batchOpaque, RequestQueue $reqQueue, array $reqIdReqMsgMap, int &$reqCount)
    {
        $this->batchOpaque = $batchOpaque;
        $this->reqQueue = $reqQueue;
        $this->reqIdReqMsgMap = $reqIdReqMsgMap;
        $this->reqCount = &$reqCount;
    }

    public function __invoke(): int
    {
        try {
            foreach ($this->reqIdReqMsgMap as $uuid => $jMsg) {
                $operationMessage = $this->createOperationMessage($jMsg, $this->batchOpaque);
                $rc = $this->reqQueue->enqueue($operationMessage);
                if (!$rc) {
                    $jMsg->setStatus(ServerOperationStatus::QueueFull);
                } else {
                    $this->reqCount++;
                }
                if ($this->reqCount == 1) {
                    $this->notifyFirst();
                }
            }
        } catch (\Exception $e) {
            error_log("JUNO_BATCH_SEND " . JunoStatusCode::ERROR->value . " Exception while enqueuing the request");
        }
        return $this->reqCount;
    }

    private function createOperationMessage(JunoMessage $jMsg, int $opaque): OperationMessage
    {
        // Implement the logic to create OperationMessage from JunoMessage
        // This would be similar to the Java implementation
        // ...
    }

    private function notifyFirst(): void
    {
        // Implement notification logic if needed
        // ...
    }
}