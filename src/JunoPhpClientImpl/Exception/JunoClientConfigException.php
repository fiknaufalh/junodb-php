<?php

namespace JunoPhpClient\Exception;

class JunoClientConfigException extends JunoException
{
    public function __construct(string $message, ?\Throwable $cause = null)
    {
        parent::__construct($message, 0, $cause);
    }
}