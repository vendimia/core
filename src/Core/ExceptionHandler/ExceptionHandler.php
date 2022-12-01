<?php

namespace Vendimia\Core\ExceptionHandler;

use Vendimia\Logger\Logger;
use Throwable;

/**
 * Default unhandled exceptions handler handler. For handing.
 */
class ExceptionHandler
{
    public function __construct(
        private ?ExceptionHandlerAbstract $handler,
        private ?Logger $logger = null,
    )
    {

    }

    /**
     * Sets the exception handler
     */
    public function setHandler(ExceptionHandlerAbstract $handler)
    {
        $this->handler = $handler;
    }

    public function setLogger(Logger $logger)
    {
        $this->logger = $logger;
    }

    public function __invoke(Throwable $throwable)
    {
        $this->logger->alert($throwable->getMessage(), [
            "exception" => $throwable
        ]);
        $this->handler->handle($throwable);
    }
}