<?php
namespace utils;

/**
 * A static class providing a method for getting a throwable stack as string in a readable format.
 */
final class StackTraceDecoder{
    /**
     * This is a static class. It cannot be constructed.
     */
    private function __construct() {}

    /**
     * Get a throwable stack trace as string in a readable format.
     * @param \Throwable $throwable The throwable object to have its stack trace decoded.
     * @return string The result string. Linebreaks will be put after each stack trace item and at the end of the text.
     *                If there is no stack trace, it returns an empty string.
     */
    static function GetThrowableTraceAsString(\Throwable $throwable): string
    {
        $rtn = '';
        $count = 0;
        foreach ($throwable->getTrace() as $frame) {
            $args = "";
            if (isset($frame['args'])) {
                $args = array();
                foreach ($frame['args'] as $arg) {
                    if (is_string($arg)) $args[] = "'" . $arg . "'";
                    elseif (is_array($arg)) $args[] = "Array";
                    elseif (is_null($arg)) $args[] = 'NULL';
                    elseif (is_bool($arg)) $args[] = ($arg) ? "true" : "false";
                    elseif (is_object($arg)) $args[] = get_class($arg);
                    elseif (is_resource($arg)) $args[] = get_resource_type($arg);
                    else $args[] = $arg;
                }
                $args = join(", ", $args);
            }
            $rtn .= sprintf("#%s %s(%s): %s%s%s(%s)\r\n", $count, $frame['file'], $frame['line'],
                $frame['class'] ?? '', $frame['type'] ?? '', $frame['function'], $args);
            $count++;
        }
        return $rtn;
    }
}
