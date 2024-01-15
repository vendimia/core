<?php

namespace Vendimia\Core\ExceptionHandler;

use Throwable;
use ReflectionClass;
use Vendimia\Exception\VendimiaException;
use Vendimia\ObjectManager\ObjectManager;
use Vendimia\Http\Response;

use const Vendimia\DEBUG;


/**
 * Shows detailed information about an exception using JSON.
 */
class Json extends ExceptionHandlerAbstract
{

    /**
     * Gathers information about a Throwable
     */
    public function getThrowableInformation(Throwable $throwable): array
    {
        $info = [
            'exception' => get_class($throwable),
            'message' => $throwable->getMessage(),
            'file' => $throwable->getFile(),
            'line' => $throwable->getLine(),
            'traceback' => $throwable->getTrace(),
        ];

        if ($throwable instanceof VendimiaException) {
            $payload['extra'] = $throwable->getExtra();
        }

        // Si hay un previous, lo añadimos
        if ($previous = $throwable->getPrevious()) {
            $info['previous'] = $this->getThrowableInformation($previous);
        }

        return $info;
    }

    /**
     * Renders a simple HTML with info of the throwable
     */
    public function handle(Throwable $throwable): never
    {
        $http_code = 500;
        if ($throwable instanceof VendimiaException) {
            $http_code = $payload['extra']['__HTTP_CODE'] ?? 500;
        }

        // Si no estamos en debug, simplemente enviamos el código vacío
        if (!DEBUG) {
            Response::Json([], code: $http_code)
                ->send();
            exit;
        }

        $object = ObjectManager::retrieve();

        $payload = $this->getThrowableInformation($throwable);

        // Evitamos que haya \n
        $reason = explode("\n", $throwable->getMessage())[0];

        Response::Json($payload, code: $http_code, reason: $reason)
            ->send();
        exit;
    }
}