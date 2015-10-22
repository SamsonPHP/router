<?php
/**
 * Created by PhpStorm.
 * User: egorov
 * Date: 22.10.2015
 * Time: 16:20
 */
namespace samsonphp\router;

/**
 * Main routing logic
 * @package samsonphp\router
 */
class Core
{
    /** @var array Collection of all application routes */
    protected $routes = array();

    /**
     * Load all web-application routes
     * @param string $prefix URL path prefix for loaded routes
     * @return array Collection of web-application routes
     */
    protected function loadRoutes($prefix = '')
    {
        $routes = array();
//        // Iterate all loaded classes
//        foreach (get_declared_classes() as $className) {
//            // Check if class implements RouteInterface
//            if (in_array('samsonphp\route\RouteInterface', class_implements($className, false))) {
//                // Call class interface static method to retrieve class routes and merge into one collection, passing prefix
//                $routes = array_merge(call_user_func($className.'::routes', $prefix), $this->routes);
//            }
//        }



        return $routes;
    }

    protected function matchRoute($pattern)
    {
        
    }

    /**
     * SamsonPHP core.routing event handler
     *
     * @param \samson\core\Core $core       Pointer to core object
     * @param mixed             $result     Return value as routing result
     * @param string            $default    Default route path
     */
    public function router(\samson\core\Core & $core, & $result, $default = 'main')
    {
        $routes = array();
        foreach ($core->module_stack as $moduleID => $module) {
            //if (in_array('samsonphp\router\RouteInterface', class_implements($module, false))) {
            if(is_subclass_of($module, 'samsonphp\router\RouteInterface')) {
                // Call class interface method to retrieve object routes and merge into one collection, passing prefix
                $routes = array_merge($module->routes(), $routes);
            }
        }

        // Match route to get callback
        if (false !== ($callback = $this->matchRoute(''))) {

        }
    }

    public function __construct()
    {
        // Load routes
        $this->routes = $this->loadRoutes();

    }
}
