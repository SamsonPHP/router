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
        // Iterate all loaded classes
        foreach (get_declared_classes() as $className) {
            // Check if class implements RouteInterface
            if (in_array('samsonphp\route\RouteInterface', class_implements($className, false))) {
                // Call class interface static method to retrieve class routes and merge into one collection, passing prefix
                $routes = array_merge(call_user_func($className.'::routes', $prefix), $this->routes);
            }
        }

        return $routes;
    }

    public function __construct()
    {
        $this->routes = $this->loadRoutes();
    }
}
