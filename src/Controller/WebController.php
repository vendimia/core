<?php

namespace Vendimia\Controller;

use Vendimia\Interface\Controller\ControllerInterface;
use Vendimia\ObjectManager\ObjectManager;
use Vendimia\Core\ProjectInfo;
use Vendimia\Core\ResourceLocator;
use Vendimia\Http\Request;
use Vendimia\Http\Response;
use Vendimia\View\View;
use Vendimia\View\AlertMessages;
use Vendimia\Session\SessionManager;
use Vendimia\Logger\Logger;

abstract class WebController implements ControllerInterface
{
    protected View $view;

    public function __construct(
        protected ObjectManager $object,
        protected Request $request,
        protected Response $response,
        protected ProjectInfo $project_info,
        protected ResourceLocator $resource_locator,
        protected AlertMessages $messages,
        protected SessionManager $session,
        protected Logger $logger,
    )
    {
        // Creamos la vista, y le colocamos valores por defecto
        $this->view = $object->new(View::class);
        $this->view->setLayout('default');
        $this->view->setSource($project_info->method);

    }

    public function execute($method, ...$args): Response
    {
        $response = $this->object->callMethod($this, $method, ...$args);

        if ($response) {
            if (is_array($response)) {
                $this->view->addArguments($response);
            }
            if ($response instanceof Response) {
                return $response;
            }
        }
        return $this->view->renderResponse();
    }
}