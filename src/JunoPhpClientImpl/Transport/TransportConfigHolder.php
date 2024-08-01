<?php

namespace JunoPhpClient\Transport;

interface TransportConfigHolder
{
    public function getInetAddress(): string;
    
    public function getHost(): string;
    
    public function getPort(): int;

    public function useSSL(): bool;
}