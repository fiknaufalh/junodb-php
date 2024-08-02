<?php

namespace JunoPhpClient\Client\IO;

class JunoRequest extends AbstractRequest
{
    private ?int $timeToLiveSec;
    private ?string $value;
    private int $version;
    private int $creationTime;
    private OperationType $type;

    public function __construct(
        string $key,
        ?string $value,
        int $version,
        ?int $timeToLiveSec,
        int $creationTime,
        OperationType $type
    ) {
        parent::__construct($key);
        $this->value = $value;
        $this->timeToLiveSec = $timeToLiveSec;
        $this->version = $version;
        $this->creationTime = $creationTime;
        $this->type = $type;
    }

    public function getTimeToLiveSec(): ?int
    {
        return $this->timeToLiveSec;
    }

    public function getValue(): ?string
    {
        return $this->value;
    }

    public function getVersion(): int
    {
        return $this->version;
    }

    public function getCreationTime(): int
    {
        return $this->creationTime;
    }

    public function getType(): OperationType
    {
        return $this->type;
    }
}

enum OperationType: string
{
    case Create = 'CREATE';
    case Get = 'GET';
    case Update = 'UPDATE';
    case Set = 'SET';
    case Destroy = 'DESTROY';

    public function getCode(): int
    {
        return match ($this) {
            self::Create => 1,
            self::Get => 2,
            self::Update => 3,
            self::Set => 4,
            self::Destroy => 5,
        };
    }

    public function getOpType(): string
    {
        return match ($this) {
            self::Create => 'CREATE',
            self::Get => 'GET',
            self::Update => 'UPDATE',
            self::Set => 'SET',
            self::Destroy => 'DESTROY',
        };
    }
}