<?php

namespace Vendimia\Core\RequestParameter;

use Vendimia\Http\Request;
use Vendimia\ObjectManager\AttributeParameterAbstract;

/**
 * Fetchs a parameter from the request parsed body
 */
class QueryParam extends RequestParameterAbstract
{
    public function hasValue(): bool
    {
        return $this->request->query_params->has($this->name);
    }

    public function getValue(): mixed
    {
        return $this->request->query_params[$this->name];
    }
}
