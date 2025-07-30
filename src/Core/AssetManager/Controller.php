<?php
namespace Vendimia\Core\AssetManager;

1use Vendimia\Controller\WebController;
use Vendimia\Http\Response;
use Vendimia\Exception\{VendimiaException, ResourceNotFoundException};
use Vendimia\Routing\MethodRoute as Route;

use InvalidArgumentException;

use const Vendimia\DEBUG;

/**
 * Public asset manager.
 *
 * This controller will preprocess CSS and JS
 */
class Controller extends WebController
{
    #[Route\Get('css/{*code}')]
    public function css($code)
    {
        [$module, $sources] = explode(':', $code, 2);
        $sources = explode(',', $sources);

        $model = $this->object->new(
            Model\Css::class,
            module: $module,
            sources: $sources,
        );

        try {
            $css = $model->render();
        } catch (InvalidArgumentException $e) {
            if (DEBUG) {
                throw new ResourceNotFoundException(
                    $e->getMessage(),
                    [
                        'Paths' => $model->getResourceLocator()->getLastSearchedPaths(),
                    ]
                );
            }
        }

        $response = Response::fromString($css)
            ->withHeader('Content-Type', 'text/css');

        return $response;
    }

    #[Route\Get('js/{*code}')]
    public function js($code, Model\Js $js_model)
    {
        if (!str_contains($code, ':')) {
            throw new ResourceNotFoundException("JS resource code malformed", extra: [
                "code" => $code,
            ]);
        }
        [$module, $sources] = explode(':', $code, 2);
        $sources = explode(',', $sources);

        $js_model->setSources($sources, $module);

        $js = $js_model->render();

        $response = Response::fromString($js)
            ->withHeader('Content-Type', 'text/javascript');

        return $response;
    }
}
