<?php
namespace Vendimia\Core;

use Vendimia\Http\Request;
use Vendimia\ObjectManager\AttributeParameterAbstract;

/**
 * Fetchs an argument from the request parsed body
 */
class BodyArg extends AttributeParameterAbstract
{
    public function __construct(
        private Request $request,
    )
    {

    }

    public function getValue()
    {
        return $this->request->parsed_body[$this->name];
    }
}