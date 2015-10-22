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
    protected function loadRoutes($modules, $prefix = '')
    {
        $routes = array();
        foreach ($modules as $moduleID => $module) {
            //if (in_array('samsonphp\router\RouteInterface', class_implements($module, false))) {
            if(is_subclass_of($module, 'samsonphp\router\RouteInterface')) {
                // Call class interface method to retrieve object routes and merge into one collection, passing prefix
                foreach ($module->routes() as $path => $data) {
                    // Enclose special char for RegExp
                    $routes[$path] = $data;
                }
            }
        }

        return $routes;
    }

    protected function normalize($input)
    {
        return preg_replace('/@([a-z]+)/ui', '(?<$1>[^/]+)',
            str_ireplace(
                '/*', '/.*',
                str_ireplace('/', '\/', $input)
            )
        );
    }

    protected function matchRoute($path, $routes)
    {
        //trace(array_keys($routes), 1);
        //trace('/^'.$this->normalize($path).'/', 1);

        $candidates = array();
        $candidate = false;

        // Iterate all routes
        foreach ($routes as $routePath => $routeDate) {
            //trace($this->normalize($routePath), 1);
            // Match route pattern with path
            if(preg_match('/^'.$this->normalize($routePath).'/', $path, $matches)){
                trace($matches, 1);
                // Store only longest matched route
                if (strlen($routePath) > strlen($candidate)) {
                    $candidates[$routePath] = $routeDate;
                    $candidate = $routePath;
                }
            }
        }

        trace($candidates[$candidate], 1);
        //trace($candidates, 1);

        return $candidates[$candidate];
    }

    /**
     * SamsonPHP core.routing event handler
     *
     * @param \samson\core\Core $core       Pointer to core object
     * @param mixed             $result     Return value as routing result
     * @param string            $default    Default route path
     */
    public function router(\samson\core\Core & $core, & $result, & $path)
    {
        $routes = $this->loadRoutes($core->module_stack);

        // Match route to get callback
        if (false !== ($callback = $this->matchRoute($path, $routes))) {

        }
    }
}
