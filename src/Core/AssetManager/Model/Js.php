<?php
namespace Vendimia\Core\AssetManager\Model;

use Vendimia\Core\ProjectInfo;
use Vendimia\Interface\Path\ResourceLocatorInterface;
use Vendimia\Exception\ResourceNotFoundException;

class Js
{
    private $sources = [];
    
    public function __construct (
        private ProjectInfo $project_info,
        private ResourceLocatorInterface $resource_locator,
    )
    {
        
    }

    public function setSources($sources, $module = null)
    {
        $this->sources = $sources;
        $this->resource_locator->setDefaultModule($module ?? $this->project_info->module);

    }

    /** 
     * Render the JS group
     */
    public function render(): string
    {
        $return = '';

        foreach ($this->sources as $source) {
            $file_path = $this->resource_locator->find(
                $source,
                type: 'js',
                ext: 'js',
            );

            if (is_null($file_path)) {
                throw new ResourceNotFoundException(
                    "JavaScript source '{$source}' not found",
                    [
                        'Paths' => $this->resource_locator->getLastSearchedPaths(),
                    ],
                );
            }
            
            // TODO: Crear (o usar) un parser de JavaScript para 
            // minimizarlo
            $return .= file_get_contents($file_path);

            // Para separar sources, usamos el punto y coma
            $return .= ';';
        }

        return $return;
    }
}