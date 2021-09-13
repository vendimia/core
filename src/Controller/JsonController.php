<?php
namespace Vendimia\Controller;

use Vendimia\Interface\ControllerInterface;
use Vendimia\ObjectManager\ObjectManager;
use Vendimia\Core\ProjectInfo;
use Vendimia\Http\Request;
use Vendimia\Http\Response;
use Vendimia\Session\SessionManager;
use LogicException;

abstract class JsonController
{
    public function __construct(
        protected ObjectManager $object,
        protected Request $request,
        protected Response $response,
        protected ProjectInfo $project_info,
        protected SessionManager $session,
    )
    {

    }

    public function execute($method, ...$args): Response
    {
        $response = $this->object->callMethod($this, $method, ...$args);

        if (!$response instanceof Response &&
            !is_array($response)) {
            throw new LogicException("JsonController method '$method' must return an array, o a Vendimia\\Response object, got " . gettype($response) . " instead");
        }

        if ($response instanceof Response) {
            return $response;
        }

        $payload = json_encode($response);


        return Response::fromString($payload)
            ->withHeader('Content-Type', 'application/json')
        ;
    }

}