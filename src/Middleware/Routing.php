<?php
namespace Vendimia\Middleware;

use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Vendimia\Http\Response;
use Vendimia\Routing\{Manager, Rule};
use Vendimia\Core\ProjectInfo;
use Vendimia\ObjectManager\ObjectManager;
use Vendimia\Interface\Path\ResourceLocatorInterface;

class Routing extends MiddlewareInterface
{
    public function __construct(
        private ObjectManager $object,
        private ProjectInfo $project_info,
    )
    {
        $resource_locator = $object->get(ResourceLocatorInterface::class);

        $resource_locator->setDefaultType('route');
        $resource_locator->setDefaultExt('php');

    }

    public function process(
        ServerRequestInterface $request,
        RequestHandlerInterface $handler
    ): ResponseInterface
    {
        $route_source = $request->getAttribute('route-source', 'main');
        $route_file = "routes/{$route_source}.php";

        $routing_rules = require $route_file;

        $route_manager = $this->object->new(Manager::class);
        $route_manager->setRules($routing_rules);

        $route = $route_manager->match($request);

        if (is_null($route)) {
            echo "<h1>404</h1>";
            // Renderizamos un 404

            echo "<pre>";
            echo $request->getMethod() . ' ' .  $request->getUri()->getPath() . "\n\n";

            foreach ($route_manager->getRules() as $rule) {
                $parts = [
                    join(',', $rule['methods']),
                    "'" . $rule['path'] . "'",
                    is_array($rule['target']) ? join('::', $rule['target']) : '??',
                ];
                echo join(' ', $parts) . PHP_EOL;
            }
            exit;
        }


        if ($route->target_type == Rule::TARGET_CONTROLLER) {

            // Creamos un poco de información del project
            $class_parts = explode('\\', $route->target[0]);

            // - Module es la 1ra parte del FQCN
            $this->project_info->module = $class_parts[0];

            // - Controller es la última parte del FQCN
            $this->project_info->controller = array_reverse($class_parts)[0];

            // - Method viene en el elemento 1 de $target
            $this->project_info->method = $route->target[1];

            // Ejecutamos el controller
            $controller = $this->object->new($route->target[0]);

            // Si tiene un método 'initialize', lo ejecutamos
            if(method_exists($controller, 'initialize')) {
                $this->object->callMethod($controller, 'initialize');
            }

            $response = $controller->execute($route->target[1], ...$route->args);

        } elseif ($route->target_type == Rule::TARGET_CALLABLE) {
            $response = $this->object->call($route->target);
        }

        return $response;
    }
}
