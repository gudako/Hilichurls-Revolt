<?php
function getThrowableTraceAsString(Throwable $throwable) {
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
        $rtn .= sprintf("#%s %s(%s): %s%s%s(%s)\n", $count, $frame['file'], $frame['line'],
            $frame['class'] ?? '', $frame['type'] ?? '', $frame['function'], $args);
        $count++;
    }
    return $rtn;
}
