<?php
namespace Vendimia\Core;

use Vendimia\Interface\Path\ResourceLocatorInterface;
use Vendimia\Core\ProjectInfo;

use const Vendimia\PROJECT_PATH;

/**
 * Vendimia implementation of ResourceLocatorInterface
 */
class ResourceLocator implements ResourceLocatorInterface
{
    /** Path relative to a module where to find certain resource types */
    private const TYPE_PATH = [
        'route' => '',
        'css' => 'resources/css',
        'js' => 'resources/js',
        'view' => 'resources/views',
        'layout' => 'resources/views/layouts',
    ];

    private $last_searched_paths = [];

    public function __construct(
        private ProjectInfo $project,
        private ?string $default_type = null,
        private string|array|null $default_ext = null,
        private ?string $default_module = null,
    )
    {
    }

    public function setDefaultType($type)
    {
        $this->default_type = $type;
    }

    public function setDefaultExt(...$ext)
    {
        $this->default_ext = $ext;
    }

    public function setDefaultModule($module)
    {
        $this->default_module = $module;
    }

    /**
     * Search for a resource file in several paths.
     *
     * `$filename` can have 3 forms:
     *
     * * Just `filename`, will search in the active module and base
     *   paths,
     * * `module:filename`, will search in `module` path.
     * * `::filename`, will only search in `base` path
     */
    public function find(
        $filename,
        ?string $type = null,
        string|array|null $ext = null,
        bool $return_relative_resource_name = false,
    ): ?string
    {
        if (is_null($type)) {
            $type = $this->default_type;
        }
        if (is_null($ext)) {
            $ext = $this->default_ext;
        }

        $active_module = $this->default_module ?? $this->project->module;

        // Buscar solo en base
        $only_base = false;

        // No buscar en base
        $no_base = false;

        // Removemos puntos y slashes
        $filename = ltrim($filename, '.\\/');

        if (str_starts_with($filename, '::')) {
            // Solo nos quedamos con base
            $filename = substr($filename, 2);
            $only_base = true;
        } else {
            // Si tiene un ':', entonces cambiamos el módulo donde debemos
            // buscar
            $colon_pos = strpos($filename, ':');
            if ($colon_pos !== false) {
                $active_module = substr($filename, 0, $colon_pos);
                $filename = substr($filename, ++$colon_pos);
                $no_base = true;
            }
        }

        // Si hay una extensión, y es un string, la añadimos como array
        if ($ext && is_string($ext)) {
            $ext = [$ext];
        }

        $search_paths = [];

        // Empezamos sólo con 'base'
        if (!$no_base) {
            $search_paths = [
                [
                    [$this->project->controller, $filename], // Relative file path
                    PROJECT_PATH,
                    'base',
                    self::TYPE_PATH[$type],
                    $this->project->controller,
                    $filename
                ],
                [
                    [$filename],    // Relative file path
                    PROJECT_PATH,
                    'base',
                    self::TYPE_PATH[$type],
                    $filename
                ],
            ];
        }

        // Si no solo buscamos en la base, preponemos los paths del módulo
        if (!$only_base) {
            $search_paths = [
                [
                    [$this->project->controller, $filename], // Relative file path
                    PROJECT_PATH,
                    'modules',
                    $active_module,
                    self::TYPE_PATH[$type],
                    $this->project->controller,
                    $filename
                ],
                [
                    [$filename],    // Relative file path
                    PROJECT_PATH,
                    'modules',
                    $active_module,
                    self::TYPE_PATH[$type],
                    $filename
                ],
                ...$search_paths
            ];
        }

        // Empezamos de cero las rutas buscadas
        $this->last_searched_paths = [];

        foreach ($search_paths as $path) {
            // Sacamos la ruta relativa
            $relative_path = array_shift($path);

            foreach ($ext as $e) {

                $target = join(DIRECTORY_SEPARATOR, array_filter($path));
                $target .= '.' . $e;
                $this->last_searched_paths[] = $target;

                if (file_exists($target)) {
                    if ($return_relative_resource_name) {
                        return join(DIRECTORY_SEPARATOR, $relative_path);
                    }
                    return $target;
                }
            }
        }

        return null;
    }

    /**
     * Return where we search for the resource. Useful for debugging
     */
    public function getLastSearchedPaths(): array
    {
        return $this->last_searched_paths;
    }
}