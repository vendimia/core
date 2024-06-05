<?php

namespace Vendimia\Core\ExceptionHandler;

use Throwable;

abstract class ExceptionHandlerAbstract
{
    /**
     * Process and return method arguments from a trace
     */
    public static function processTraceArgs($args, $separator = ', '): string
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

            $result[] = htmlspecialchars($processed_arg);
        }

        return join($separator, $result);
    }

    /**
     * Retrive a few lines of a source file
     */
    public static function readSourceLines($file, $line, $count = 8)
    {
        $lines = [];

        $start = $line - intval($count / 2);
        if ($start < 0) {
            $count -= $start;
            $start = 0;
        }

        $f = fopen($file, 'r');

        for ($i = 0; $i < $start; $i++) {
            fgets($f);
        }

        $i = 0;
        while (($i < $count) && !feof($f)) {
            $lines[$start + $i + 1] = htmlentities(fgets($f));
            $i++;
        }
        fclose($f);

        return $lines;
    }

    abstract public function handle(Throwable $throwable): never;
}