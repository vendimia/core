<?php
namespace Vendimia\Core\AssetManager\Model;

use Vendimia\Core\ProjectInfo;
use Vendimia\Interface\Path\ResourceLocatorInterface;
use Vendimia\MadSS\MadSS;

class Css
{
    public function __construct (
        private ProjectInfo $project,
        private ResourceLocatorInterface $resource_locator,
        private MadSS $madss,
        string $module,
        array $sources,
    ) 
    {
        // Añadimos las posibles extensiones al ResourceLocator
        $resource_locator->setDefaultType('css');
        $resource_locator->setDefaultExt('css', 'scss');
        $resource_locator->setDefaultModule($module);

        $madss->addSourceFiles(...$sources);
    }

    /** 
     * Render the CSS
     */
    public function render(): string
    {
        return $this->madss->process();
    }

    /** 
     * Return the Resource Locator used for MadSS
     */
    public function getResourceLocator(): ResourceLocatorInterface
    {
        return $this->resource_locator;
    }
}