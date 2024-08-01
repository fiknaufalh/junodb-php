<?php

namespace JunoPhpClient\Client\IO;

abstract class AbstractResponse
{
    protected string $key;
    protected OperationStatus $status;

    protected function __construct(string $key, OperationStatus $status)
    {
        $this->key = $key;
        $this->status = $status;
    }

    public function key(): string
    {
        return $this->key;
    }

    public function status(): OperationStatus
    {
        return $this->status;
    }
}