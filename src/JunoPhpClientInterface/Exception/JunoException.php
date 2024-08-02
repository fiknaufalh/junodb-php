<?php
namespace JunoPhpClient\Exception;

use RuntimeException;
use Throwable;

/**
 * The base exception type thrown by the JunoClient. Clients can access a
 * ThrowableWrapper that will conveniently allow {@link JunoException}s
 * to be wrapped around other {@link Throwable}s.
 */
class JunoException extends RuntimeException
{
    /**
     * Construct a JunoException.
     */
    public function __construct(string $message = "", Throwable $cause = null)
    {
        // Guard against null messages, since we have seen those occur
        parent::__construct($message, 0, $cause);
    }
}