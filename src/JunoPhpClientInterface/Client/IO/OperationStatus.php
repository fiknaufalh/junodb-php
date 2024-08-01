<?php

namespace JunoPhpClient\Client\IO;

enum OperationStatus: int
{
    case Success = 0;
    case NoKey = 1;
    case BadParam = 2;
    case UniqueKeyViolation = 3;
    case RecordLocked = 4;
    case IllegalArgument = 5;
    case ConditionViolation = 6;
    case InternalError = 7;
    case QueueFull = 8;
    case NoStorage = 9;
    case TTLExtendFailure = 10;
    case ResponseTimeout = 11;
    case ConnectionError = 12;
    case UnknownError = 13;

    public function getErrorText(): string
    {
        return match($this) {
            self::Success => "No error",
            self::NoKey => "Key not found",
            self::BadParam => "Bad parameter",
            self::UniqueKeyViolation => "Duplicate key",
            self::RecordLocked => "Record Locked",
            self::IllegalArgument => "Illegal argument",
            self::ConditionViolation => "Condition in the request violated",
            self::InternalError => "Internal error",
            self::QueueFull => "Outbound client queue full",
            self::NoStorage => "No storage server running",
            self::TTLExtendFailure => "Failure to extend TTL on get",
            self::ResponseTimeout => "Response Timed out",
            self::ConnectionError => "Connection Error",
            self::UnknownError => "Unknown Error",
        };
    }

    public function isTxnOk(): bool
    {
        return match($this) {
            self::Success, 
            self::NoKey, 
            self::UniqueKeyViolation, 
            self::RecordLocked, 
            self::ConditionViolation, 
            self::TTLExtendFailure 
            => true,
            default => false,
        };
    }
}