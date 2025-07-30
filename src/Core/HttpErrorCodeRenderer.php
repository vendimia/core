<?php

namespace Vendimia\Core;

use Vendimia\Http\{Request, Response};
use Vendimia\ObjectManager\ObjectManager;
use Vendimia\Routing\Manager;
use Vendimia\View\View;
use const Vendimia\DEBUG;

/**
 * Renders an user-friendly message for each HTTP error code
 *
 * By default, 404 and 500 code with header Accept or Content-type 'text/html'
 * renders a view located in base\resouces\views\http-status
 *
 */
class HttpErrorCodeRenderer
{
    public function __construct(
        private Request $request,
        private Manager $route_manager,
        private ObjectManager $object,
    )
    {
    }

    private function getDebugInfo(): array
    {
        $rules = [];
        foreach ($this->route_manager->getRules() as $rule) {
            $rules[] = [
                'methods' => is_null($rule['methods']) ? 'ANY' : join(',', $rule['methods']),
                'path' => $rule['path'],
                'target' => is_array($rule['target']) ? join('::', $rule['target']) : '??',
            ];
        }
        $target = $this->request->getMethod() . ' ' .  $this->request->getUri()->getPath();
        $route_source = $this->request->getAttribute('route-source', 'main');
        return compact('target', 'route_source', 'rules');
    }

    public function render404(): never
    {
        // Obtenemos el tipo MIME para responder
        $tipo_mime_respuesta = $this->request->getHeaderLine('Accept');

        // Si no hay Accept, o $tipo_mime_respuesta es '*/*', entonces usamos
        //  content-type
        if (!$tipo_mime_respuesta || $tipo_mime_respuesta == '*/*') {
            $tipo_mime_respuesta = $this->request->getHeaderLine('Content-Type')
                ?: 'text/html';
        }


        // FIXME: Solo tomamos en cuenta la primera opción.
        $tipo_mime_respuesta = trim(explode(',', $tipo_mime_respuesta)[0]);

        $información_depuración = $this->getDebugInfo();

        // Sólo en el caso de text/html renderizamos una vista
        if ($tipo_mime_respuesta == 'text/html') {

            $this->object->new(View::class)->renderHttpStatus(
                404,
                $información_depuración
            );
        }

        // Para todos los demás tipos, en debug enviamos un array con la
        // información de depuración. En producción, un mensaje en blanco
        $response = $this->object->new(Response::class)
            ->withStatus(404)
        ;

        if (DEBUG) {
            $parser_class = Request::getBodyParser($tipo_mime_respuesta);
            $payload = new $parser_class()
                ->parseBack($información_depuración)
            ;

            $response = Response::fromString($payload)
                ->withHeader('Content-Type', $tipo_mime_respuesta)
                ->withStatus(404)
            ;

            $response->send();
        }

        $response->send();
    }
}
