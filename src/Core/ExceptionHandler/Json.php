<?php

namespace Vendimia\Core\ExceptionHandler;

use Throwable;
use ReflectionClass;
use Vendimia\Exception\VendimiaException;
use Vendimia\ObjectManager\ObjectManager;
use Vendimia\Http\Response;

/**
 * Shows detailed information about an exception using JSON.
 */
class Json extends ExceptionHandlerAbstract
{
    /**
     * Renders a simple HTML with info of the throwable
     */
    public function handle(Throwable $throwable): never
    {
        $object = ObjectManager::retrieve();

        $payload = [
            'exception' => get_class($throwable),
            'message' => $throwable->getMessage(),
            'file' => $throwable->getFile(),
            'line' => $throwable->getLine(),
            'traceback' => $throwable->getTrace(),
        ];

        if ($throwable instanceof VendimiaException) {
            $payload['extra'] = $throwable->getExtra();
        }

        // Evitamos que haya \n
        $reason = explode("\n", $throwable->getMessage())[0];

        Response::Json($payload, code: 500, reason: $reason)
            ->send();
        exit;
    }
}