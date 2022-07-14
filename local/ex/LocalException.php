<?php
namespace local\ex;
use Throwable;

class LocalException extends Detection
{
    private bool $continuable;

    // Redefine the exception so message isn't optional
    public function __construct($message, $continuable = false, $code = 0, Throwable $previous = null)
    {
        $this->continuable = $continuable;
        parent::__construct($message, $code, $previous);
    }

    protected function GetSeverity(): int
    {
        return SEVERITY_CLIENTSIDE_REFLECT;
    }

    protected function GetPostExAction(): int
    {
        return $this->continuable? POSTEX_CONTINUABLE:POSTEX_HANGINPLACE;
    }
}