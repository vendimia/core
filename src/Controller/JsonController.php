<?php

namespace Vendimia\Controller;

use Vendimia\Interface\Controller\ControllerInterface;
use Vendimia\ObjectManager\ObjectManager;
use Vendimia\Core\ProjectInfo;
use Vendimia\Http\Request;
use Vendimia\Http\Response;
use Vendimia\Session\SessionManager;
use Vendimia\Logger\Logger;
use LogicException;

abstract class JsonController implements ControllerInterface
{
    /** If not null, HTTP response code, or array with [code, reason] */
    private $response_code = null;

    public function __construct(
        protected ObjectManager $object,
        protected Request $request,
        protected Response $response,
        protected ProjectInfo $project_info,
        protected SessionManager $session,
        protected Logger $logger,
    )
    {

    }

    /**
     * Sets the response HTTP code and reason
     */
    public function setResponseCode($code, $reason = null)
    {
        if (is_null($reason)) {
            $this->response_code = $code;
        } else {
            $this->response_code = [$code, $reason];
        }
    }

    public function execute($method, ...$args): Response
    {
        $response = $this->object->callMethod($this, $method, ...$args);

        if (!$response instanceof Response &&
            !is_array($response)) {
            throw new LogicException("JsonController method '$method' must return an array, or a Vendimia\\Response object, got " . gettype($response) . " instead");
        }

        if ($response instanceof Response) {
            return $response;
        }

        $payload = json_encode($response);

        $response = Response::fromString($payload)
            ->withHeader('Content-Type', 'application/json')
        ;

        if ($this->response_code) {
            if (is_array($this->response_code)) {
                $response = $response->withStatus(...$this->response_code);
            } else {
                $response = $response->withStatus($this->response_code);
            }
        }

        return $response;
    }

}