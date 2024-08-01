<?php

namespace JunoPhpClient\Client\IO;

abstract class AbstractRequest
{
    protected string $key;

    protected function __construct(string $key)
    {
        $this->key = $key;
    }

    public function key(): string
    {
        return $this->key;
    }
}