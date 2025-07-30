<?php

namespace Vendimia\CommandLine;

use Vendimia\Helper\FileSystem;
use Vendimia\Logger\Logger;
use Vendimia\ObjectManager\ObjectManager;

use InvalidArgumentException;

use const Vendimia\PROJECT_PATH;

/**
 * CLI command 'new'
 */
class NewCommand
{
    /*
     * Adds a new module to the projectt
     */
    public static function module(string $name): void
    {
        // Si existe un logger, lo usamos
        $logger = null;
        if (class_exists(ObjectManager::class)) {
            $logger = ObjectManager::retrieve()->get(Logger::class);
        }

        // El nombre del módulo debe empezar en mayúscula
        $name = mb_convert_case(trim($name), MB_CASE_TITLE);

        // El nombre debe ser una etiqueta PHP válida
        if (!preg_match('/^[a-zA-Z_\x80-\xff][a-zA-Z0-9_\x80-\xff]*$/', $name)){
            throw new InvalidArgumentException('Module name must be a valid PHP class name');
        }

        $base_path = PROJECT_PATH . '/modules/' . $name;

        $directory_tree = [
            $base_path => [
                'Controller',
                'Model',
                'Form',
                'Database',
                'resources' => [
                    'views',
                    'assets' => [
                        'css',
                        'js',
                        'imgs',
                    ]
                ]
            ],
        ];
        FileSystem::createDirectoryTree($directory_tree);

        // Creamos un DefaultController
        $controller_file = $base_path . '/Controller/DefaultController.php';
        if (file_exists($controller_file)) {
            $logger->notice('IGNORE ' . $controller_file);
        } else {
            $logger->notice('CREATE ' . $controller_file);

            file_put_contents($controller_file, <<<EOF
            <?php

            namespace {$name}\\Controller;

            use Vendimia\\Controller\\WebController;
            use Vendimia\\Routing\\MethodRoute as Route;
            use Vendimia\\Core\\RequestParameter\\{BodyParam, QueryParam};
            use Vendimia\\Http\\Response;
            use Vendimia\\View\\View;
            use Vendimia\\Session\\SessionManager;

            class DefaultController extends WebController
            {
                /**
                 * Default controller if URL path is empty.
                 *
                 * It will render a 'default' view file.
                 */
                #[Route\Get]
                public function default(): ?array
                {
                }
            }
            EOF);
        }

        // Creamos una vista 'default'
        $view_file = $base_path . '/resources/views/default.php';
        if (file_exists($view_file)) {
            $logger->notice('IGNORE ' . $view_file);
        } else {
            file_put_contents($view_file, <<<EOF
            <h1>View file for controller {$name}\\Controller\\DefaultController::default()</h1>

            <p>Edit the file <code>{$view_file}</code> to create your own view.</p>
            EOF);
        }

        // Ahora lo añadimos al fichero de rutas por defecto
        $route_file = PROJECT_PATH . '/routes/main.php';
        $source = trim(file_get_contents($route_file));

        // Sólo añadimos si la última línea es un '];'
        if (!preg_match('/ *];\n?$/m', $source)) {
            $logger->error("ERROR " . $route_file);
        }

        $lines = preg_split("[\r|\n|\r\n]", $source);

        // Nuevamente, la última línea debe ser un '];'
        $closing_array_line = array_pop($lines);

        if ($closing_array_line !== '];') {
            $logger->error("ERROR " . $route_file);
        }

        // Si ya existe una ruta para este controlador, no hacemos nada
        $route_name = mb_strtolower($name);
        $add_routing_rule = true;
        foreach ($lines as $line) {
            if (str_contains($line, "Rule::path('{$route_name}')")) {
                $logger->notice("IGNORE " . $route_file);
                $add_routing_rule = false;
                break;
            }
        }

        if ($add_routing_rule) {
            // Ahora si, añadimos la nueva ruta
            $lines[] = "";
            $lines[] = "    // Route for DefaultController class of {$name} module";
            $lines[] = "    Rule::path('{$route_name}')->includeFromClass({$name}\Controller\DefaultController::class),";
            $lines[] = "];";

            $logger->notice("UPDATE {$route_file}");
            file_put_contents($route_file, implode("\n", $lines));
        }
    }
}
