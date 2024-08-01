<?php

namespace JunoPhpClient\Util;

class JunoMetrics
{
    public const JUNO_LATENCY_METRIC = 'juno.client.operation';
    public const JUNO_OPERATION_METRIC = 'juno.client.operation.status';
    public const SUCCESS = 'SUCCESS';
    public const ERROR = 'ERROR';
    public const WARNING = 'WARNING';
    public const EXCEPTION = 'EXCEPTION';
    public const TYPE = 'type';
    public const NAME = 'name';
    public const STATUS = 'status';
    public const ERROR_CAUSE = 'cause';
    public const METRIC_PREFIX = 'juno.client.';
    public const CONNECT_METRIC = self::METRIC_PREFIX . 'connect.count';
    public const SPAN_METRIC = self::METRIC_PREFIX . 'span';
    public const EVENT_METRIC = self::METRIC_PREFIX . 'event.count';
    public const ERROR_METRIC = self::METRIC_PREFIX . 'error.count';

    public static function recordOpTimer(string $metricName, string $operation, string $pool, string $status, int $timeInMs): void
    {
        // Implement metric recording logic
    }

    public static function recordTimer(string $type, string $name, string $status, int $timeInMs): void
    {
        // Implement timer recording logic
    }

    public static function recordOpCount(string $pool, string $op_type, string $errorType, ?string $errorCause = null): void
    {
        // Implement operation count recording logic
    }

    public static function recordConnectCount(string $endpoint, string $status, string $cause): void
    {
        // Implement connect count recording logic
    }

    public static function recordEventCount(string $type, string $name, string $status): void
    {
        // Implement event count recording logic
    }

    public static function recordErrorCount(string $type, string $name, string $cause): void
    {
        // Implement error count recording logic
    }
}