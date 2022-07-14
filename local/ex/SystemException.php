<?php
namespace local\ex;
use Throwable;

class SystemException extends Detection
{
    // Redefine the exception so message isn't optional
    public function __construct($message, $code = 0, Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }

    protected function GetSeverity(): int
    {
        return SEVERITY_DESTRUCTIVE;
    }

    protected function GetPostExAction(): int
    {
        return POSTEX_REDIRECT;
    }
}