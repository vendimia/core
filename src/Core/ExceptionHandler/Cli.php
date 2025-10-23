<?php

namespace Vendimia\Core\ExceptionHandler;

use Vendimia\Exception\VendimiaException;
use Vendimia\ObjectManager\ObjectManager;
use Vendimia\Core\Console;
use Throwable;
use ReflectionClass;

use const Vendimia\PROJECT_PATH;
use const STDERR;

/**
 * Sends information about an exception to STDERR
 */
class Cli extends ExceptionHandlerAbstract
{
    public function handle(Throwable $throwable): never
    {
        if (defined("STDERR")) {
            $console = new Console(STDERR);
        } else {
            $console = new Console(fopen('php://stdout', 'w'));
            $console->disableColors();
        }

        $object = ObjectManager::retrieve();
        $throwable_class = get_class($throwable);

        $file = $throwable->getFile();

        if (str_starts_with($file, PROJECT_PATH)) {
            $file = '[PP]' . substr($file, strlen(PROJECT_PATH));
        }

        $console->write("[|white {$throwable_class}|]: {$throwable->getMessage()}");
        $console->write("on [|cyan {$file}|]:{$throwable->getLine()}");

        exit(1);
    }

}
