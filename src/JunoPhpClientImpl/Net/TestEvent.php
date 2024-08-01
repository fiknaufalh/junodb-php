<?php

namespace JunoPhpClient\Net;

enum TestEvent: int
{
    case INTERRUPTED = 1;
    case INTERRUPTED_2 = 2;
    case EXCEPTION = 3;
    case EXCEPTION_2 = 4;
    case EXCEPTION_3 = 5;
    case SEND_FAIL = 6;
    case READ_FAIL = 7;
    case CONNECTION_LOST = 8;
    case MISSING_RESPONSE = 9;
    case DNS_DELAY = 10;

    private static int $mask = 0xffffffff;

    public function getValue(): int
    {
        return $this->value;
    }

    public function maskedValue(): int
    {
        if ((self::$mask & $this->code()) == 0) {
            return 0;
        }

        self::$mask ^= $this->code();
        return $this->value;
    }

    public function triggerException(): void
    {
        switch ($this) {
            case self::INTERRUPTED:
            case self::INTERRUPTED_2:
                throw new \Exception("Test mode: event " . $this->value);
            case self::EXCEPTION:
            case self::EXCEPTION_2:
            case self::EXCEPTION_3:
                throw new \RuntimeException("Test Mode: event " . $this->value);
        }
    }

    private function code(): int
    {
        return 1 << $this->value;
    }
}