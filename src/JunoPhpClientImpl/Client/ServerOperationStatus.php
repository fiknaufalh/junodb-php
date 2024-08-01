<?php

namespace JunoPhpClient\Client;

use JunoPhpClient\Client\IO\OperationStatus;

enum ServerOperationStatus: int
{
    case Success = 0;
    case BadMsg = 1;
    case NoKey = 3;
    case DupKey = 4;
    case BadParam = 7;
    case RecordLocked = 8;
    case NoStorageServer = 12;
    case ServerBusy = 14;
    case VersionConflict = 19;
    case OpStatusSSReadTTLExtendErr = 23;
    case CommitFailure = 25;
    case InconsistentState = 26;
    case Internal = 255;
    case QueueFull = 256;
    case ConnectionError = 257;
    case ResponseTimedout = 258;

    public function getErrorText(): string
    {
        return match ($this) {
            ServerOperationStatus::Success => "no error",
            ServerOperationStatus::BadMsg => "bad message",
            ServerOperationStatus::NoKey => "key not found",
            ServerOperationStatus::DupKey => "dup key",
            ServerOperationStatus::BadParam => "bad parameter",
            ServerOperationStatus::RecordLocked => "record locked",
            ServerOperationStatus::NoStorageServer => "no active storage server",
            ServerOperationStatus::ServerBusy => "Server busy",
            ServerOperationStatus::VersionConflict => "version conflict",
            ServerOperationStatus::OpStatusSSReadTTLExtendErr => "Error extending TTL by SS",
            ServerOperationStatus::CommitFailure => "Commit Failure",
            ServerOperationStatus::InconsistentState => "Inconsistent State",
            ServerOperationStatus::Internal => "Internal error",
            ServerOperationStatus::QueueFull => "Outbound client queue full",
            ServerOperationStatus::ConnectionError => "Connection error",
            ServerOperationStatus::ResponseTimedout => "Response timed out",
        };
    }

    public function getOperationStatus(): OperationStatus
    {
        return match ($this) {
            ServerOperationStatus::Success => OperationStatus::Success,
            ServerOperationStatus::BadMsg => OperationStatus::InternalError,
            ServerOperationStatus::NoKey => OperationStatus::NoKey,
            ServerOperationStatus::DupKey => OperationStatus::UniqueKeyViolation,
            ServerOperationStatus::BadParam => OperationStatus::BadParam,
            ServerOperationStatus::RecordLocked => OperationStatus::RecordLocked,
            ServerOperationStatus::NoStorageServer => OperationStatus::NoStorage,
            ServerOperationStatus::ServerBusy => OperationStatus::InternalError,
            ServerOperationStatus::VersionConflict => OperationStatus::ConditionViolation,
            ServerOperationStatus::OpStatusSSReadTTLExtendErr => OperationStatus::InternalError,
            ServerOperationStatus::CommitFailure => OperationStatus::InternalError,
            ServerOperationStatus::InconsistentState => OperationStatus::Success,
            ServerOperationStatus::Internal => OperationStatus::InternalError,
            ServerOperationStatus::QueueFull => OperationStatus::QueueFull,
            ServerOperationStatus::ConnectionError => OperationStatus::ConnectionError,
            ServerOperationStatus::ResponseTimedout => OperationStatus::ResponseTimeout,
        };
    }
}