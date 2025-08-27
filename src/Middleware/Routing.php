<?php
namespace Vendimia\Middleware;

use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Vendimia\Core\HttpErrorCodeRenderer;
use Vendimia\Exception\UnmatchedRouteException;
use Vendimia\Http\{Request, Response};
use Vendimia\Routing\{Manager, Rule};
use Vendimia\Core\ProjectInfo;
use Vendimia\ObjectManager\ObjectManager;
use Vendimia\Interface\Path\ResourceLocatorInterface;
use Vendimia\View\View;
use Vendimia\Interface\Controller\ControllerInterface;

use Vendimia\Exception\ResourceNotFoundException;
use RuntimeException;

class Routing implements MiddlewareInterface
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
        $route_source = $request->getAttribute('route-source', 'web');
        $route_file = "routes/{$route_source}.php";

        $routing_rules = require $route_file;

        $route_manager = $this->object->new(Manager::class);
        $route_manager->setRules($routing_rules);

        $route = $route_manager->match($request);

        if (is_null($route)) {
            $this->object->new(HttpErrorCodeRenderer::class,
                request: $request,
                route_manager: $route_manager
            )->render404();
        }

        // Grabamos la ruta 'matched'
        $this->object->save($route);

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

            if (!$controller instanceof ControllerInterface) {
                $class = $controller::class;
                throw new RuntimeException("{$class} class does not implements ControllerInterface interface.");
            }

            // Si tiene un método 'initialize', lo ejecutamos
            if(method_exists($controller, 'initialize')) {
                $this->object->callMethod($controller, 'initialize');
            }

            // Una ruta aun puede generar un UnmatchedRouteException
            try {
                $response = $controller->execute($route->target[1], ...$route->args);
            } catch (UnmatchedRouteException $e) {
                $this->object->new(HttpErrorCodeRenderer::class,
                    request: $request,
                    route_manager: $route_manager
                )->render404();
            }

        } elseif ($route->target_type == Rule::TARGET_CALLABLE) {
            $response = $this->object->call($route->target);
        } elseif ($route->target_type == Rule::TARGET_VIEW) {
            $view = $this->object->new(View::class);
            $view->setLayout('default');
            $view->setSource($route->target);

            $response = $view->renderResponse();
        }

        return $response;
    }
}
