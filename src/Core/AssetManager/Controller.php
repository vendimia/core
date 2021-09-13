<?php
namespace Vendimia\Core\AssetManager;

use Vendimia\Controller\WebController;
use Vendimia\Http\Response;
use Vendimia\Exception\ResourceNotFoundException;
use InvalidArgumentException;

use const Vendimia\DEBUG;

/** 
 * Public asset manager.
 * 
 * This controller will preprocess CSS and JS 
 */
class Controller extends WebController
{
    public function css($code)
    {
        [$module, $sources] = explode(':', $code);
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

    public function js($code, Model\Js $js_model)
    {
        [$module, $sources] = explode(':', $code);
        $sources = explode(',', $sources);

        $js_model->setSources($sources, $module);

        $js = $js_model->render();

        $response = Response::fromString($js)
            ->withHeader('Content-Type', 'text/javascript');

        return $response;
   }
}