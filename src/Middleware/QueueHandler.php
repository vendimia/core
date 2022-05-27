<?php
namespace Vendimia\Middleware;

use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Http\Server\MiddlewareInterface;
use Vendimia\ObjectManager\ObjectManager;

class QueueHandler implements RequestHandlerInterface
{
    private $queue = [];
    private $fallbackHandler;

    public function __construct(
        private ObjectManager $object
    )
    {

    }

    public function setQueue(array $queue)
    {
        $this->queue = $queue;
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        if (0 === count($this->queue)) {
            // FIXME: Nunca debería llegar acá
            return new \Vendimia\Http\Response;
        }

        $middleware_class = array_shift($this->queue);
        $middleware = $this->object->new($middleware_class);

        return $middleware->process($request, $this);
    }
}