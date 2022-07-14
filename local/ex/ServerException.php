<?php
namespace local\ex;
use Throwable;

class ServerException extends Detection
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
        return $this->continuable?SEVERITY_PARTIAL:SEVERITY_BLOCKING;
    }

    protected function GetPostExAction(): int
    {
        return $this->continuable? POSTEX_CONTINUABLE:POSTEX_REDIRECT;
    }
}