<?php

namespace Vendimia\Core\RequestParameter;

use Vendimia\Http\Request;
use Vendimia\ObjectManager\AttributeParameterAbstract;

/**
 * Extends AttributeParameterAbstract for a common __construct
 */
abstract class RequestParameterAbstract extends AttributeParameterAbstract
{
    public function __construct(
        protected Request $request,
        ?string $name = null,
    )
    {
        if (!is_null($name)) {
            $this->name = $name;
        }
    }
}
