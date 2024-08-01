<?php

namespace JunoPhpClient\IO\Protocol;

use JunoPhpClient\Client\ServerOperationStatus;

class JunoMessage
{
    private string $key;
    private ?string $value;
    private int $version;
    private int $expiry;
    private int $timeToLiveSec;
    private string $nameSpace;
    private string $applicationName;
    private OperationType $opType;
    private ServerOperationStatus $status;
    private int $creationTime;
    private int $reqStartTime;
    private int $reqHandlingTime; // millisecond duration on JunoServ

    private int $messageSize;
    private string $reqId;
    private bool $isPayloadCompressed;
    private int $compressionAchieved;
    private CompressionType $compressionType;

    public function __construct(
        string $key,
        ?string $value,
        int $version,
        int $expiry,
        int $ttl,
        OperationType $opType
    ) {
        $this->key = $key;
        $this->value = $value;
        $this->version = $version;
        $this->expiry = $expiry;
        $this->timeToLiveSec = $ttl;
        $this->opType = $opType;
    }

    public function getValue()
    {
        return $this->value;
    }
    
    public function setValue($value)
    {
        $this->value = $value;
    }

    public function getKey()
    {
        return $this->key;
    }

    public function setKey($key)
    {
        $this->key = $key;
    }

    public function getVersion()
    {
        return $this->version;
    }

    public function setVersion($version)
    {
        $this->version = $version;
    }

    public function getExpiry()
    {
        return $this->expiry;
    }

    public function setExpiry($expiry)
    {
        $this->expiry = $expiry;
    }

    public function getTimeToLiveSec()
    {
        return $this->timeToLiveSec;
    }

    public function setTimeToLiveSec($timeToLiveSec)
    {
        $this->timeToLiveSec = $timeToLiveSec;
    }


    public function getOpType()
    {
        return $this->opType;
    }

    public function setOpType($opType)
    {
        $this->opType = $opType;
    }

    public function getStatus()
    {
        return $this->status;
    }

    public function setStatus($status)
    {
        $this->status = $status;
    }

    public function getNameSpace()
    {
        return $this->nameSpace;
    }

    public function setNameSpace($nameSpace)
    {
        $this->nameSpace = $nameSpace;
    }

    public function getApplicationName()
    {
        return $this->applicationName;
    }

    public function setApplicationName($applicationName)
    {
        $this->applicationName = $applicationName;
    }

    public function getCreationTime()
    {
        return $this->creationTime;
    }

    public function setCreationTime($creationTime)
    {
        $this->creationTime = $creationTime;
    }

    public function getReqStartTime()
    {
        return $this->reqStartTime;
    }

    public function setReqStartTime($reqStartTime)
    {
        $this->reqStartTime = $reqStartTime;
    }

    public function setReqHandlingTime($rht)
    {
        $this->reqHandlingTime = $rht;
    }

    public function getReqHandlingTime()
    {
        return $this->reqHandlingTime;
    }

    public function getMessageSize()
    {
        return $this->messageSize;
    }

    public function setMessageSize($messageSize)
    {
        $this->messageSize = $messageSize;
    }

    public function getReqId()
    {
        return $this->reqId;
    }

    public function setReqId($reqId)
    {
        $this->reqId = $reqId;
    }

    public function isPayloadCompressed()
    {
        return $this->isPayloadCompressed;
    }

    public function setPayloadCompressed($isPayloadCompressed)
    {
        $this->isPayloadCompressed = $isPayloadCompressed;
    }

    public function getCompressionAchieved()
    {
        return $this->compressionAchieved;
    }

    public function setCompressionAchieved($compressionAchieved)
    {
        $this->compressionAchieved = $compressionAchieved;
    }

    public function getCompressionType()
    {
        return $this->compressionType;
    }

    public function setCompressionType($compressionType)
    {
        $this->compressionType = $compressionType;
    }
}

enum OperationType: string
{
    case Nop = 'NOP';
    case Create = 'CREATE';
    case Get = 'GET';
    case Update = 'UPDATE';
    case Set = 'SET';
    case CompareAndSet = 'COMPAREANDSET';
    case Destroy = 'DESTROY';

    public function getCode(): int
    {
        return match ($this) {
            self::Nop => 0,
            self::Create => 1,
            self::Get => 2,
            self::Update => 3,
            self::Set => 4,
            self::CompareAndSet => 5,
            self::Destroy => 6,
        };
    }

    public function getOpType(int $code): OperationType
    {
        return match ($code) {
            0 => self::Nop,
            1 => self::Create,
            2 => self::Get,
            3 => self::Update,
            4 => self::Set,
            5 => self::CompareAndSet,
            6 => self::Destroy,
        };
    }
}
