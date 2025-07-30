<?php

namespace Vendimia\CommandLine;

use Vendimia\Clap\Parser;

/**
 * Vendimia command-line commands initialization
 */
class CommandLine
{
    public static function build(): Parser
    {
        $parser = new Parser();
        $parser->register(NewCommand::class);

        return $parser;
    }
}
