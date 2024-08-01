<?php
namespace JunoPhpClient\IO\Protocol;

class Assert
{
  public static function isTrue(string $msg, bool $expression): void
  {
    if (!$expression) {
      throw new \InvalidArgumentException("[Assertion failed] - this expression must be true. " . $msg);
    }
  }
}