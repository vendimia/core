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
    private static function validatePHPLabel($label)
    {
        if (preg_match('/^[a-zA-Z_\x80-\xff][a-zA-Z0-9_\x80-\xff]*$/', $label)){
            return true;
        }
        return false;
    }

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
        if (!self::validatePHPLabel($name)){
            throw new InvalidArgumentException('Module name must be a valid PHP class name');
        }

        $base_path = PROJECT_PATH . '/modules/' . $name;

        $directory_tree = [
            $base_path => [
                'Controller',
                'Model',
                'Form',
                'Database' => [
                    'migrations'
                ],
                'resources' => [
                    'css',
                    'js',
                    'views' => [
                        'layouts',
                    ]
                ],
                'routes',
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

            use {$name}\\{Database, Model, Form};

            class DefaultController extends WebController
            {
                /**
                 * Default controller if URL path is empty.
                 *
                 * It will render a 'default' view file.
                 */
                #[Route\Get]
                public function default()
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
        $route_file = PROJECT_PATH . '/routes/web.php';
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
            $lines[] = "    Rule::path('{$route_name}')->include('{$name}:routes/web'),";
            $lines[] = "];";

            $logger->notice("UPDATE {$route_file}");
            file_put_contents($route_file, implode("\n", $lines));
        }

        // Creamos un fichero de rutas
        $module_route_file = $base_path . '/routes/web.php';
        if (file_exists($module_route_file)) {
            $logger->notice("IGNORE " . $module_route_file);
        } else {
            $logger->notice("WRITE " . $module_route_file);
            file_put_contents($module_route_file, <<<EOF
            <?php

            use Vendimia\Routing\Rule;

            use {$name}\Controller;

            return [
                Rule::any()->includeFromClass(Controller\DefaultController::class),
            ];
            EOF);
        }
    }

    /*
     * Adds a new database to a module
     */
    public static function database(string $module, string $name)
    {
        $logger = ObjectManager::retrieve()->get(Logger::class);

        // El nombre del módulo debe empezar en mayúscula
        $module = mb_convert_case(trim($module), MB_CASE_TITLE);

        $module_path = PROJECT_PATH . '/modules/' . $module;

        // Si el módulo no existe, fallamos.
        if (!is_dir($module_path)) {
            throw new InvalidArgumentException("Module {$module} does not exist");
        }

        // El nombre de la base de datos debe empezar en mayúscula
        $name = mb_convert_case(trim($name), MB_CASE_TITLE);

        // El nombre debe ser una etiqueta PHP válida
        if (!self::validatePHPLabel($name)){
            throw new InvalidArgumentException('Database name must be a valid PHP class name');
        }

        $content = <<<EOF
        <?php

        namespace {$module}\Database;

        use Vendimia\Database\{Entity, Field};

        /**
         * Definition of {$name} database entity
         */
        class {$name} extends Entity
        {

        }
        EOF;

        $target_file = $module_path . "/Database/{$name}.php";

        if (file_exists($target_file)) {
            $logger->notice('IGNORE ' . $target_file);
        } else {
            $logger->info('WRITE ' . $target_file);
            file_put_contents($target_file, $content);
        }
    }
}
