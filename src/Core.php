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
        $routes = new RouteCollection();
        foreach ($modules as $moduleID => $module) {
            //if (in_array('samsonphp\router\RouteInterface', class_implements($module, false))) {
            if(is_subclass_of($module, 'samsonphp\router\RouteInterface')) {
                // Try to get module routes using interface method
                $moduleRoutes = $module->routes();
                // There are no routes defined
                if (!sizeof($moduleRoutes)) {
                    // Generate generic routes
                    $moduleRoutes = $this->createGenericRoutes($module);
                }

                $routes = $routes->merge($moduleRoutes);
            }
        }

        return $routes;
    }

    /**
     *
     * @param $module
     * @return array
     */
    protected function createGenericRoutes($module)
    {
        $prefix = '/'.$module->id;

        $routes = new RouteCollection();

        // Iterate class methods
        foreach (get_class_methods($module) as $method) {
            // Try to find standard controllers
            switch (strtolower($method)) {
                case GenericInterface::CTR_UNI: // Add generic controller action
                    $routes->add(new Route($prefix . '/*', array($module, $method), $module->id . GenericInterface::CTR_UNI));
                    break;
                case GenericInterface::CTR_BASE: // Add base controller action
                    $routes->add(new Route($prefix . '/?$', array($module, $method), $module->id . GenericInterface::CTR_BASE));
                    break;
                case GenericInterface::CTR_POST:// not implemented
                case GenericInterface::CTR_PUT:// not implemented
                case GenericInterface::CTR_DELETE:// not implemented
                    break;

                // Ignore magic methods
                case '__call':
                case '__wakeup':
                case '__sleep':
                case '__construct':
                case '__destruct':
                case '__set':
                case '__get':
                    break;

                // This is not special controller action
                default:
                    // Match controller action OOP pattern
                    if (preg_match('/^' . GenericInterface::OBJ_PREFIX . '(?<async_>async_)?(?<cache_>cache_)?(?<action>.+)/i', $method, $matches)) {
                        // Add route for this controller action
                        $routes->add(
                            new Route($prefix . '/' . $matches['action'],
                                array($module, $method), // Route callback
                                $module->id . '_' . $method, // Route identifier
                                Route::METHOD_ANY,
                                $matches[GenericInterface::ASYNC_PREFIX] == GenericInterface::ASYNC_PREFIX ? true : false,
                                $matches[GenericInterface::CACHE_PREFIX] == GenericInterface::CACHE_PREFIX ? true : false
                            )
                        );
                    }
            }
        }

        return $routes;
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
        /** @var Route $route Match route in routes collection to get callback & parameters */
        if (false !== ($route = $this->loadRoutes($core->module_stack)->match($path))) {
            trace($route, 1);
            // Get object from callback & set it as current active core module
//            $core->active($handlerData[0][0]);
//
//            $parameters = array();
//
//            // Perform controller action
//            $result = is_callable($handlerData[0]) ? call_user_func_array($handlerData[0], $parameters) : A_FAILED;
//
//            // Stop candidate search
//            $result = !isset($result) ? A_SUCCESS : $result;
        }
    }
}
