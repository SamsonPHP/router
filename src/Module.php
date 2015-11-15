<?php
namespace samsonphp\router;

/**
 * Created by PhpStorm.
 * User: VITALYIEGOROV
 * Date: 14.11.15
 * Time: 09:53
 */
class Module extends \samson\core\CompressableExternalModule
{
    /** @var string Module identifier */
    public $id = 'router';

    /** @var Core Routing core */
    protected $core;

    public function prepare()
    {

    }

    /**
     * Module initialization
     * @param array $params Initialization parameters collection
     */
    public function init(array $params = array())
    {
        // Create router core component
        $this->core = new Core();

        // Subscribe to samsonphp\core routing event
        \samsonphp\event\Event::subscribe('core.routing', array($this->core, 'router'));

        // Create SamsonPHP routing table from loaded modules
        $rg = new GenericRouteGenerator(s()->module_stack, $default);

        // Generate web-application routes
        $routes = $rg->generate();

        // Create cache marker
        $cacheFile = $routes->hash().'.php';
        $cacheFilePath = $this->cache_path . $cacheFile;

        // If we need to refresh cache
        if ($this->cache_refresh($cacheFile)) {
            // Generate routing logic function
            $routerLogic = $this->core->generate($routes);

            // Store router logic in cache
            file_put_contents($cacheFilePath, '<?php '.$routerLogic);
        }

        require($cacheFilePath);
    }
}
