<?php
/**
 * Created by PhpStorm.
 * User: VITALYIEGOROV
 * Date: 26.10.15
 * Time: 10:50
 */
namespace samsonphp\router;

use \samsonframework\routing\RouteCollection;
use \samsonframework\routing\Route;
use \samsonphp\event\Event;

/**
 * This class is needed to generate routes for old SamsonPHP modules
 * @package samsonphp\router
 */
class GenericRouteGenerator
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
    //[PHPCOMPRESSOR(remove,start)]

    /** @var RouteCollection Generated routes collection */
    protected $routes;

    /** @var Object[] Collection of SamsonPHP modules */
    protected $modules;

    /**
     * @return RouteCollection Generated routes collection
     */
    public function &routes()
    {
        return $this->routes;
    }

    /**
     * GenericRouteGenerator constructor.
     * @param Module[] $modules
     */
    public function __construct(array & $modules)
    {
        $this->routes = new RouteCollection();
        $this->modules = &$modules;
    }

    /**
     * Load all SamsonPHP web-application routes.
     *
     * @return RouteCollection Collection of web-application routes
     */
    public function &generate()
    {
        foreach ($this->modules as &$module) {
            // Generate generic routes
            $moduleRoutes = $this->createGenericRoutes($module);

            // Try to get module routes using interface method
            $moduleRoutes = method_exists($module, 'routes')
                ? $module->routes($moduleRoutes) : $moduleRoutes;

            $this->routes = $this->routes->merge($moduleRoutes);
        }

        return $this->routes;
    }

    /**
     * Class method signature parameters.
     *
     * @param mixed $object Object
     * @param string $method Method name
     * @return \ReflectionParameter[] Method parameters
     */
    protected function getMethodParameters($object, $method)
    {
        // Analyze callback arguments
        $reflectionMethod = new \ReflectionMethod($object, $method);

        return $reflectionMethod->getParameters();
    }

    /**
     * Convert class method signature into route pattern with parameters.
     *
     * @param mixed $object Object
     * @param string $method Method name
     * @return string Pattern string with parameters placeholders
     */
    protected function buildMethodParameters($object, $method)
    {
        $pattern = array();

        // Analyze callback arguments
        foreach ($this->getMethodParameters($object, $method) as $parameter) {
            // Build pattern markers
            $pattern[] = '{' . $parameter->getName() . '}';
            //trace($parameter->getDefaultValue(),1);
        }

        return implode('/', $pattern);
    }

    /**
     * @param $module
     * @param $prefix
     * @param $method
     * @param string $action
     * @param string $async
     * @param string $cache
     * @return RouteCollection
     * @throws \samsonframework\routing\exception\IdentifierDuplication
     */
    protected function getParametrizedRoutes($module, $prefix, $method, $action = '', $async = '', $cache = '')
    {
        $routes = new RouteCollection();

        // Iterate method parameters list to find NOT optional parameters
        $parameters = array();
        $optionalParameters = array();
        foreach ($this->getMethodParameters($module, $method) as $parameter) {
            if (!$parameter->isOptional()) {
                // Append parameter to collection
                $parameters[] = '{' . $parameter->getName() . '}';
            } else {
                $optionalParameters[] = $parameter->getName();
            }
        }

        // Build controller action pattern
        $pattern = $prefix . '/';
        // Add controller action if passed
        $pattern = isset($action{0}) ? $pattern . $action . '/' : $pattern;
        // Add needed parameters
        $pattern .= implode('/', $parameters);

        $optionalPattern = $pattern.'/';

        // Iterate all optional parameters
        foreach ($optionalParameters as $parameter) {
            // Add optional parameter as now we consider it needed
            $optionalPattern .= '{' . $parameter . '}/';

            // Add SamsonPHP specific async method
            foreach (array(Route::METHOD_GET, Route::METHOD_POST) as $httpMethod) {
                // Add route for this controller action
                $routes->add(
                    new Route(
                        $optionalPattern,
                        $module->id.'#'.$method, // Route callback
                        $module->id . '_' . $httpMethod . '_' . $method.'_'.$parameter, // Route identifier
                        $async . $httpMethod // Prepend async prefix to method if found
                    )
                );
            }
        }

        // Add SamsonPHP without optional parameters
        foreach (array(Route::METHOD_GET, Route::METHOD_POST) as $httpMethod) {
            // Add route for this controller action
            $routes->add(
                new Route(
                    $pattern,
                    $module->id.'#'.$method, // Route callback
                    $module->id . '_' . $httpMethod . '_' . $method, // Route identifier
                    $async . $httpMethod // Prepend async prefix to method if found
                )
            );
        }

        return $routes;
    }

    /**
     * Generate old-fashioned routes collection.
     *
     * @param Object $module
     * @return RouteCollection
     */
    protected function createGenericRoutes(&$module)
    {
        /** @var RouteCollection $routes */
        $routes = new RouteCollection();
        /** @var callable $universalCallback */
        $universalCallback = null;
        $universalRoutes = new RouteCollection();
        /** @var Route $baseRoute */
        $baseRoute = null;

        $prefix = '/' . $module->id;

        Event::fire('samsonphp.router.create.module.routes', array($module, & $prefix));

        //trace('!!!!!!!!!!!!!!!! - '.$prefix);

        // Iterate class methods
        foreach (get_class_methods($module) as $method) {
            // Try to find standard controllers
            switch (strtolower($method)) {
                case self::CTR_UNI: // Add generic controller action
                case self::CTR_CACHE_UNI:
                    $universalCallback = $module->id.'#'.$method;
                    $universalRoutes->merge($this->getParametrizedRoutes($module, $prefix, $method));
                    break;
                case self::CTR_BASE: // Add base controller action
                    $baseRoute = new Route($prefix . '/', $module->id.'#'.$method, $module->id . self::CTR_BASE);
                    break;
                case self::CTR_CACHE_BASE:
                    $baseRoute = new Route($prefix . '/', $module->id.'#'.$method, $module->id . self::CTR_CACHE_BASE);
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
                        $routes->merge(
                            $this->getParametrizedRoutes(
                                $module,
                                $prefix,
                                $method,
                                $matches['action'],
                                $matches[self::ASYNC_PREFIX],
                                $matches['cache_']
                            )
                        );
                    }
            }
        }

        // Add universal route
        $routes->merge($universalRoutes);

        // Add base controller action
        if (isset($baseRoute)) {
            $routes->add($baseRoute);
            // If we have not found base controller action but we have universal action
        } elseif (isset($universalCallback)) {
            // Bind its pattern to universal controller callback
            $routes->add(new Route($prefix . '/', $universalCallback, $module->id . self::CTR_BASE));
        }

        return $routes;
    }
    //[PHPCOMPRESSOR(remove,end)]
}
