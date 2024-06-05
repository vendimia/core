<?php

namespace Vendimia\Core\ExceptionHandler;

use Throwable;
use ReflectionClass;

use Vendimia\Exception\VendimiaException;
use Vendimia\ObjectManager\ObjectManager;
use Vendimia\Http\Request;
use Vendimia\Routing\MatchedRoute;
use Vendimia\View\View;

use const Vendimia\DEBUG;

/**
 * Shows detailed information about an exception using HTML.
 *
 * This must not be used in production
 */
class Web extends ExceptionHandlerAbstract
{
    /**
     * Uses a view for rendering a 500
     */
    public function handle(Throwable $throwable): never
    {
        $object = ObjectManager::retrieve();

        $request = $object->get(Request::class);

        $object->new(View::class)->renderHttpStatus(500, [
            "throwable" => $throwable,
            "request" => $request,
            "matched_rule" => $object->get(MatchedRoute::class),
            "query_params" => self::processTraceArgs($request->query_params),
            "parsed_body" => self::processTraceArgs($request->parsed_body),
        ]);
    }
}