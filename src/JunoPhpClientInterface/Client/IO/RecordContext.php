<?php

namespace JunoPhpClient\Client\IO;

class RecordContext
{
    private string $key;
    private int $version;
    private int $creationTime;
    private int $timeToLiveSec;

    public function __construct(string $key, int $version, int $creationTime, int $ttl)
    {
        $this->key = $key;
        $this->version = $version;
        $this->creationTime = $creationTime;
        $this->timeToLiveSec = $ttl;
    }

    public function getKey(): string
    {
        return $this->key;
    }

    public function getVersion(): int
    {
        return $this->version;
    }

    public function getCreationTime(): int
    {
        return $this->creationTime;
    }

    public function getTtl(): int
    {
        return $this->timeToLiveSec;
    }
}