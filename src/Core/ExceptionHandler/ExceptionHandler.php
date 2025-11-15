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
        // Si el logger falla, usamos error_log como última oportunidad
        try {
            $this->logger?->critical($throwable->getMessage(), [
                "exception" => $throwable
            ]);
        } catch (Throwable $e) {
            error_log('[vendimia] Exception handler logger failed: ' . $e->getMessage());
            error_log('[vendimia] Original exception: ' . $throwable->getMessage());
        }

        $this->handler->handle($throwable);
    }
}
