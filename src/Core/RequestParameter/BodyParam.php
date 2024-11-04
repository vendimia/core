<?php

namespace Vendimia\Core\RequestParameter;

use Vendimia\Http\Request;
use Vendimia\ObjectManager\AttributeParameterAbstract;

/**
 * Fetchs a parameter from the request parsed body
 */
class BodyParam extends RequestParameterAbstract
{
    public function hasValue(): bool
    {
        return $this->request->parsed_body->has($this->name);
    }

    public function getValue(): mixed
    {
        return $this->request->parsed_body[$this->name];
    }
}
