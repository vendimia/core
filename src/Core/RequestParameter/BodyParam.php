<?php
namespace Vendimia\Core\RequestParameter;

use Vendimia\Http\Request;
use Vendimia\ObjectManager\AttributeParameterAbstract;

/**
 * Fetchs a parameter from the request parsed body
 */
class BodyParam extends AttributeParameterAbstract
{
    public function __construct(
        private Request $request,
        ?string $name = null,
    )
    {
        if (!is_null($name)) {
            $this->name = $name;
        }
    }

    public function getValue()
    {
        return $this->request->parsed_body[$this->name];
    }
}