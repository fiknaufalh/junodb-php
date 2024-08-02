<?php
namespace JunoPhpClient\Exception;

use RuntimeException;

/**
 * Exception thrown when there is an input error.
 */
class JunoInputException extends RuntimeException
{
    /**
     * {@link OperationStatus}, for use by error mapper.
     */

    /**
     * Construct a JunoInputException.
     */
    public function __construct(string $message = "")
    {
        // Guard against null messages, since we have seen those occur
        parent::__construct($message);
    }
}