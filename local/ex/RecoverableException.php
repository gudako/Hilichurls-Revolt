<?php
namespace local\ex;

class RecoverableException extends \Exception
{
    // Redefine the exception so message isn't optional
    public function __construct($message, $code = 0, \Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}