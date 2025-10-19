<?php

namespace Vendimia\Exception;

use Exception;
use Throwable;

/**
 * Exception class for most of Vendimia-thrown exceptions. It has an extra
 * 'extra' parameter.
 */
class VendimiaException extends Exception
{
    public function __construct(
        string $message = "",
        int $code = 0,
        ?Throwable $previous = null,

        /** Extra information for this exception */
        protected array $extra = [],
    )
    {
        parent::__construct($message, $code, $previous);
    }

    /**
     * Returns extra information provided by the error
     */
    public function getExtra(): array
    {
        return $this->extra;
    }
}
