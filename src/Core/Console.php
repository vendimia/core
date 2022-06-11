<?php

namespace Vendimia\Core;

/**
 * Methods for writing on a terminal/console
 */
class Console
{
    const ANSI_COLOR = [
        'black' => 0,
        'red' => 1,
        'green' => 2,
        'yellow' => 3,
        'blue' => 4,
        'magenta' => 5,
        'cyan' => 6,
        'white' => 7,
    ];

    /**
     * GNU Readline hinting for avoiding spacing confusion.
     */
    private $rl_on = '';
    private $rl_off = '';

    /** Disable color usage */
    private $disable_colors = false;

    public function __construct($output = STDOUT)
    {
        if (!stream_isatty($output)) {
            $this->disable_colors = true;
        }
    }

    /**
     * Force output without ANSI colors
     */
    public function disableColors()
    {
        $this->disable_colors = true;
    }

    /**
     * Replace color codes with ANSI codes.
     *
     * E.g.:
     *
     *  This is a [|red red string|]
     *
     */
    public function parse($string)
    {
        $result = preg_replace_callback('/\[\|(.+?) (.+?)\|\]/', function($matches) {
            [$dummy, $color, $text] = $matches;

            return $this->color($color, $text);
        }, $string);

        return $result;
    }

    /**
     * Returns a ANSI-colored string
     */
    public function color($color, $text)
    {
        if ($this->disable_colors) {
            return $text;
        }

        $result = $this->rl_on
            . "\x1b[" . (30+self::ANSI_COLOR[$color]) . ";1m"
            . $this->rl_off

            . $text

            . $this->rl_on
            . "\x1b[0m"
            . $this->rl_off
        ;

        return $result;
    }

    /**
     * Writes to the console a parsed string, followed by a \n
     */
    public function write($text)
    {
        echo $this->parse($text) . "\n";
    }
}