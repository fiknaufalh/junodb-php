<?php

namespace JunoPhpClient\Client\IO;

class JunoResponse extends AbstractResponse
{
    private ?string $value;
    private RecordContext $rcx;

    public function __construct(
        string $key,
        ?string $value,
        int $version,
        int $ttl,
        int $createTime,
        OperationStatus $status
    ) {
        parent::__construct($key, $status);
        $this->value = $value;
        $this->rcx = new RecordContext($key, $version, $createTime, $ttl);
    }

    public function getRecordContext(): RecordContext
    {
        return $this->rcx;
    }

    public function getKey(): string
    {
        return $this->rcx->getKey();
    }

    public function getValue(): ?string
    {
        return $this->value;
    }

    public function getVersion(): int
    {
        return $this->rcx->getVersion();
    }

    public function getTtl(): int
    {
        return $this->rcx->getTtl();
    }

    public function getStatus(): OperationStatus
    {
        return parent::status();
    }

    public function getCreationTime(): int
    {
        return $this->rcx->getCreationTime();
    }
}