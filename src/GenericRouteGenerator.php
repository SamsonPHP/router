<?php
/**
 * Created by PhpStorm.
 * User: VITALYIEGOROV
 * Date: 26.10.15
 * Time: 10:50
 */
namespace samsonphp\router;

/**
 * This class is needed to generate routes for old SamsonPHP modules
 * @package samsonphp\router
 */
class GenericRouteGenerator implements RouteGeneratorInterface
{
    /** Default controller name */
    const CTR_BASE = '__base';
    const CTR_CACHE_BASE = '__cache_base';

    /** Universal controller name */
    const CTR_UNI = '__handler';
    const CTR_CACHE_UNI = '__cache_handler';

    /** Post controller name */
    const CTR_POST = '__post';
    const CTR_CACHE_POST = '__cache_post';

    /** Put controller name */
    const CTR_PUT = '__put';
    const CTR_CACHE_PUT = '__cache_put';

    /** Delete controller name */
    const CTR_DELETE = '__delete';
    const CTR_CACHE_DELETE = '__cache_delete';

    /** Delete controller name */
    const CTR_UPDATE = '__update';
    const CTR_CACHE_UPDATE = '__cache_update';

    /** Controllers naming conventions */

    /** Procedural controller prefix */
    const PROC_PREFIX = '_';
    /** OOP controller prefix */
    const OBJ_PREFIX = '__';
    /** AJAX controller prefix */
    const ASYNC_PREFIX = 'async_';
    /** CACHE controller prefix */
    const CACHE_PREFIX = 'cache_';
    
    /** @var RouteCollection Generated routes collection */
    protected $routes;

    /** @var samsonos\core\Module[] Collection of SamsonPHP modules */
    protected $modules;

    /**
     * @return RouteCollection Generated routes collection
     */
    public function & routes()
    {
        return $this->routes;
    }

    public function __construct(array & $modules, $default)
    {
        $this->routes = new RouteCollection();

        // Create default '/' route
        $this->routes->add(new Route('/', $this->findGenericDefaultAction($modules, $default), 'main_page'));
        $this->modules = & $modules;
    }

    /**
     * Load all SamsonPHP web-application routes
     * @param string $prefix URL path prefix for loaded routes
     * @return array Collection of web-application routes
     */
    public function & generate()
    {
        foreach ($this->modules as $moduleID => & $module) {
            if(is_subclass_of($module, __NAMESPACE__.'\RouteInterface')) {
                // Try to get module routes using interface method
                $moduleRoutes = $module->routes();
                // There are no routes defined
                if (!sizeof($moduleRoutes)) {
                    // Generate generic routes
                    $moduleRoutes = $this->createGenericRoutes($module);
                }

                $this->routes = $this->routes->merge($moduleRoutes);
            }
        }

        return $this->routes;
    }

    /**
     * Convert class method signature into route pattern with parameters
     * @param object $object Object
     * @param string $method Method name
     * @return string Pattern string with parameters placeholders
     */
    protected function buildMethodParameters($object, $method)
    {
        $pattern = array();

        // Analyze callback arguments
        $reflectionMethod = new \ReflectionMethod($object, $method);
        foreach ($reflectionMethod->getParameters() as $parameter) {
            // Build pattern markers
            $pattern[] = '{'.$parameter->getName().'}';
            //trace($parameter->getDefaultValue(),1);
        }

        return implode('/', $pattern);
    }

    /**
     * Old generic "main_page" route callback searcher to match old logic
     * @param \samson\core\Core $core
     * @param callable|string $callback
     * @return array
     *
     */
    public function findGenericDefaultAction($modules, $callback)
    {
        if (is_callable($callback)) {
            return $callback;
        } else if (isset($modules[$callback])) {
            return array($modules[$callback], self::CTR_UNI);
        }
    }

    /**
     *
     * @param $module
     * @return array
     */
    protected function createGenericRoutes(& $module)
    {
        /** @var RouteCollection $routes */
        $routes = new RouteCollection();
        /** @var Route $universalRoute */
        $universalRoute = null;
        /** @var Route $baseRoute */
        $baseRoute = null;

        // Iterate class methods
        foreach (get_class_methods($module) as $method) {
            $prefix = '/' . $module->id;
            // Try to find standard controllers
            switch (strtolower($method)) {
                case self::CTR_UNI: // Add generic controller action
                    $universalRoute = new Route($prefix . '/{parameters:.*}', array($module, $method), $module->id . self::CTR_UNI);
                    //trace($this->buildMethodParameters($module, $method), 1);
                    break;
                case self::CTR_BASE: // Add base controller action
                    $baseRoute = new Route($prefix . '/{parameters:.*}', array($module, $method), $module->id . self::CTR_BASE);
                    break;
                case self::CTR_POST:// not implemented
                case self::CTR_PUT:// not implemented
                case self::CTR_DELETE:// not implemented
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
                    if (preg_match('/^' . self::OBJ_PREFIX . '(?<async_>async_)?(?<cache_>cache_)?(?<action>.+)/i', $method, $matches)) {
                        // Build controller action pattern
                        $pattern = $prefix . '/' . $matches['action'] . '/' . $this->buildMethodParameters($module, $method);

                        // Add SamsonPHP specific async method
                        foreach (Route::$METHODS as $httpMethod) {
                            // Add route for this controller action
                            $routes->add(
                                new Route(
                                    $pattern,
                                    array($module, $method), // Route callback
                                    $module->id . $httpMethod .'_'.$method, // Route identifier
                                    $matches[self::ASYNC_PREFIX].$httpMethod // Prepend async prefix to method
                                )
                            );
                        }
                    }
            }
        }

        // Add universal controller action
        if (isset($universalRoute)) {
            $routes->add($universalRoute);
        }

        // Add base controller action
        if (isset($baseRoute)) {
            $routes->add($baseRoute);
            // If we have not found base controller action but we have universal action
        } else if (isset($universalRoute)){
            // Bind its pattern to universal controller callback
            $routes->add(
                new Route(
                    $prefix . '/{parameters:.*}',
                    $universalRoute->callback,
                    $module->id . self::CTR_BASE
                )
            );
        }

        return $routes;
    }
}
