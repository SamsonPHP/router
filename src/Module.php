<?php
namespace samsonphp\router;

use samsonframework\routing\Generator;
use samsonframework\routing\Route;

/**
 * SamsonPHP Routing module implementation.
 *
 * @package samsonphp\router
 */
class Module extends \samson\core\CompressableExternalModule
{
    /** @var string Module identifier */
    public $id = 'router';

    /** @var string Default controller module identifier */
    public $defaultModule = 'main';

    /** @var Core Routing core */
    protected $core;

    /**
     * Old generic "main_page" route callback searcher to match old logic.
     *
     * @return Route Default application route "/"
     */
    protected function findGenericDefaultAction()
    {
        $callback = null;
        // If callback is passed  - function name
        if (is_callable($this->defaultModule)) {
            // Use it as main controller callback
            $callback = $this->defaultModule;
            // Consider as module identifier is passed
        } elseif (isset($this->system->module_stack[$this->defaultModule])) {
            // Try to find module universal controller action
            $callback = array($this->system->module_stack[$this->defaultModule], self::CTR_UNI);
        }

        return new Route('/', $callback,'main_page');
    }

    /**
     * Module initialization
     * @param array $params Initialization parameters collection
     */
    public function init(array $params = array())
    {
        // Create SamsonPHP routing table from loaded modules
        $rg = new GenericRouteGenerator($this->system->module_stack, $this->findGenericDefaultAction());

        // Generate web-application routes
        $routes = $rg->generate();

        // Create router core component
        $this->core = new Core($routes);

        // Subscribe to samsonphp\core routing event
        \samsonphp\event\Event::subscribe('core.routing', array($this->core, 'router'));

        // Create cache marker
        $cacheFile = $routes->hash().'.php';

        // If we need to refresh cache
        if ($this->cache_refresh($cacheFile)) {
            $generator = new Generator();
            // Generate routing logic function
            $routerLogic = $generator->generate($routes);

            // Store router logic in cache
            file_put_contents($cacheFile, '<?php '.$routerLogic);
        }

        require($cacheFile);

        return parent::init($params);
    }
}
