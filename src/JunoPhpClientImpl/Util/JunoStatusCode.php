<?php

namespace JunoPhpClient\Util;

enum JunoStatusCode: string
{
    case SUCCESS = "0";
    case FATAL = "1";
    case ERROR = "2";
    case EXCEPTION = "3";
    case WARNING = "4";
    case UNKNOWN = "U";

    public static function getStatusCode(string $status): JunoStatusCode
    {
        return match ($status) {
            "0" => JunoStatusCode::SUCCESS,
            "1" => JunoStatusCode::FATAL,
            "2" => JunoStatusCode::ERROR,
            "3" => JunoStatusCode::EXCEPTION,
            "4" => JunoStatusCode::WARNING,
            default => JunoStatusCode::UNKNOWN,
        };
    }

    public function getStatusCodeValue(): string
    {
        return match ($this) {
            JunoStatusCode::SUCCESS => "0",
            JunoStatusCode::FATAL => "1",
            JunoStatusCode::ERROR => "2",
            JunoStatusCode::EXCEPTION => "3",
            JunoStatusCode::WARNING => "4",
            JunoStatusCode::UNKNOWN => "U",
        };
    }

    public function getStatusCodeDescription(): string
    {
        return match ($this) {
            JunoStatusCode::SUCCESS => "Success",
            JunoStatusCode::FATAL => "Fatal",
            JunoStatusCode::ERROR => "Error",
            JunoStatusCode::EXCEPTION => "Exception",
            JunoStatusCode::WARNING => "Warning",
            JunoStatusCode::UNKNOWN => "Unknown",
        };
    }   
}