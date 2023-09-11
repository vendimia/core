<?php
namespace Vendimia\Exception;

use Exception;

class VendimiaException extends Exception
{
    private $extra = [];

    public function __construct($message = '', ...$extra)
    {
        parent::__construct($message);
        $this->extra = $extra;
    }

    /**
     * Returns extra information provided by the error
     */
    public function getExtra(): array
    {
        return $this->extra;
    }
}