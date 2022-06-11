<?php

namespace Vendimia\Core\ExceptionHandler;

abstract class ExceptionHandlerAbstract
{
    /**
     * Process and return method arguments from a trace
     */
    protected static function processTraceArgs($args, $separator = ', '): string
    {
        // Si no es iterable, lo retornamos de vuelta
        if (!is_iterable($args)) {
            return (string)$args;
        }

        $result = [];
        foreach ($args as $param => $arg) {
            $processed_arg = '';
            if (is_string($param)) {
                $processed_arg = "{$param}: ";
            }
            if (is_null($arg)) {
                $processed_arg .= 'NULL';
            } elseif (is_array($arg)) {
                $processed_arg .= '[' . self::processTraceArgs($arg) . ']';
            } elseif (is_object($arg)) {
                $processed_arg .= get_class($arg);// . ' ' . $short_name;
            } elseif (is_string($arg)) {
                $processed_arg .= '"' . $arg . '"';
            } else {
                $processed_arg .= $arg;
            }

            $result[] = $processed_arg;
        }

        return join($separator, $result);
    }
}