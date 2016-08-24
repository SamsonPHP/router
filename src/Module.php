<?php
namespace samsonphp\router;

use samson\core\SamsonLocale;
use samsonframework\core\SystemInterface;
use samsonframework\routing\Core;
use samsonframework\routing\generator\Structure;
use samsonframework\routing\Route;
use samsonphp\event\Event;

/**
 * SamsonPHP Routing module implementation.
 *
 * @package samsonphp\router
 */
class Module extends \samson\core\CompressableExternalModule
{
    const EVENT_ROUTE_FOUND = 'router.route.found';

    /** @var string Module identifier */
    public $id = 'router';

    /** @var string Default controller module identifier */
    public $defaultModule = 'main';

    /** @var bool Use automatic locale resolving with browser response headers */
    public $browserLocaleRedirect = false;

    /** @var string Path to routing logic cache file */
    protected $cacheFile;

    /** @var string Current URL path */
    protected $requestURI;

    /**
     * Old generic "main_page" route callback searcher to match old logic.
     *
     * @return Route Default application route "/"
     */
    protected function findGenericDefaultAction()
    {
        $callback = null;
        // Set pointer to module
        $module = &$this->system->getContainer()->getServices('module')[$this->defaultModule];
        // If callback is passed  - function name
        if (is_callable($this->defaultModule)) {
            // Use it as main controller callback
            $callback = $this->defaultModule;
            // Consider as module identifier is passed
        } elseif ($module !== null) {
            // Try to find module universal controller action
            if (method_exists($module, self::CTR_BASE)) {
                $callback = $module->id . '#' . self::CTR_BASE;
            } else if (method_exists($module, self::CTR_CACHE_BASE)) {
                $callback = $module->id . '#' . self::CTR_CACHE_BASE;
            } elseif (method_exists($module, self::CTR_UNI)) {
                $callback = $module->id . '#' . self::CTR_UNI;
            } elseif (method_exists($module, self::CTR_CACHE_UNI)) {
                $callback = $module->id . '#' . self::CTR_CACHE_UNI;
            }
        }

        return new Route('/', $callback, 'main_page');
    }

    /**
     * Module initialization.
     *
     * @param array $params Initialization parameters collection
     * @return bool Initialization result
     */
    public function init(array $params = array())
    {
        //[PHPCOMPRESSOR(remove,start)]
        $modules = $this->system->getContainer()->getServices('module');
        // Create SamsonPHP routing table from loaded modules
        $rg = new GenericRouteGenerator($modules);

        // Generate web-application routes
        $routes = $rg->generate();
        $routes->add($this->findGenericDefaultAction());

        // Create cache marker
        $this->cacheFile = $routes->hash().'.php';
        // If we need to refresh cache
        if ($this->cache_refresh($this->cacheFile)) {
            $generator = new Structure($routes, new \samsonphp\generator\Generator());
            // Generate routing logic function
            $routerLogic = $generator->generate();

            // Store router logic in cache
            file_put_contents($this->cacheFile, '<?php '."\n".$routerLogic);
        }

        require($this->cacheFile);
        //[PHPCOMPRESSOR(remove,end)]

        // Set locale resolver mode
        SamsonLocale::$leaveDefaultLocale = $this->browserLocaleRedirect;

        // This should be change to receive path as a parameter on initialization
        $pathParts = array_values(array_filter(explode(Route::DELIMITER, $_SERVER['REQUEST_URI']), function($v){
            return ($v !== '' && null !== $v);
        }));

         // Parse URL and store locale found bug
        $localeFound = SamsonLocale::parseURL($pathParts, $this->browserLocaleRedirect);
        // Gather URL path parts with removed locale placeholder
        $this->requestURI = implode(Route::DELIMITER, $pathParts);

        // Get localization data
        $current = SamsonLocale::current();
        $default = SamsonLocale::$defaultLocale;

        // Browser agent language detection logic
        if ($this->browserLocaleRedirect && !$localeFound) {
            // Redirect to browser language
            $lang = substr($_SERVER['HTTP_ACCEPT_LANGUAGE'], 0, 2);
            // Is browser language supported by application
            $langSupport = in_array($lang, SamsonLocale::get(), true);

            /**
             * If browser language header is supported by our web-application and we are already not on that locale
             * and current locale is not default.
             */
            if ($current === $default && $current !== $lang && $langSupport) {
                header('Location: http://'.$_SERVER['HTTP_HOST'].'/'.$lang.'/'.$this->requestURI);exit;
            } elseif (!$langSupport || $lang === $current) {
                SamsonLocale::$leaveDefaultLocale = false;
            }
        }

        // Subscribe to samsonphp\core routing event
        \samsonphp\event\Event::subscribe('core.routing', array($this, 'router'));

        // Continue initialization
        return parent::init($params);
    }

    /** @see \samson\core\CompressableExternalModule::afterCompress() */
    public function afterCompress(&$obj = null, array &$code = null)
    {
        // Compress generated php code
        $obj->compress_php($this->cacheFile, $this, $code, '');
    }

    /**
     * Define if HTTP request is asynchronous.
     *
     * @return bool True if request is asynchronous
     */
    public function isAsynchronousRequest()
    {
        return isset($_SERVER['HTTP_SJSASYNC']) || isset($_POST['SJSASYNC']);
    }

    /**
     * Parse route parameters received from router logic function.
     *
     * @param callable $callback Route instance
     * @param array $receivedParameters Collection of parsed parameters
     * @return array Collection of route callback needed parameters
     */
    protected function parseParameters($callback, array $receivedParameters)
    {
        $parameters = array();
        // Parse callback signature and get parameters list
        if (is_callable($callback)) {
            $reflectionMethod = is_array($callback)
                ? new \ReflectionMethod($callback[0], $callback[1])
                : new \ReflectionFunction($callback);
            foreach ($reflectionMethod->getParameters() as $parameter) {
                $parameters[] = $parameter->getName();
            }
        }

        // Gather parsed route parameters in correct order
        $foundParameters = array();
        foreach ($parameters as $name) {
            // Add to parameters collection
            $parameterValue = &$receivedParameters[$name];
            if (isset($parameterValue) && isset($parameterValue{0})) {
                $foundParameters[] = $parameterValue;
            }
        }
        return $foundParameters;
    }

    /**
     * SamsonPHP core.routing event handler
     *
     * @param SystemInterface $core Pointer to core object
     * @param mixed $result Return value as routing result
     * @return bool Routing result
     */
    public function router(SystemInterface &$core, &$result)
    {
        //elapsed('Start routing');
        // Flag for matching SamsonPHP asynchronous requests
        $async = $this->isAsynchronousRequest();
        // Get HTTP request path
        $path = $this->requestURI;//$_SERVER['REQUEST_URI'];
        // Get HTTP request method
        $method = $_SERVER['REQUEST_METHOD'];
        // Prepend HTTP request type, true - asynchronous
        $method = ($async ? GenericRouteGenerator::ASYNC_PREFIX : '').$method;

        $result = false;

        // Remove first slash if present, add method to path, remove GET params, remove trailing slash
        $path = rtrim(ltrim(strtok($path, '?'), '/'), '/');

        /** @var mixed $routeMetadata Dispatching result route metadata */
        if (is_array($routeMetadata = call_user_func(Core::ROUTING_LOGIC_FUNCTION, $path, $method))) {
            // Check found route
            if (count($routeMetadata) === 3) {
                // Get callback info
                list($module, $method) = explode("#", $routeMetadata[2]);
                // Get module
                $module = $core->module($module);
                // Create callback
                $callback = array($module, $method);
    
                // Trigger found route event
                Event::fire(self::EVENT_ROUTE_FOUND, array(&$module, $callback));
    
                // Check if we have vaild callback
                if (is_callable($callback)) {
                    // Get object from callback and set it as current active core module
                    $core->active($module);
                    // Routing result
                    $result = call_user_func_array(
                        $callback,
                        $this->parseParameters($callback, $routeMetadata[1])
                    );
    
                    // If this is cached method
                    if (stripos($method, self::CACHE_PREFIX) !== false) {
                        // perform caching
                        $core->cached();
                    }
    
                    // If this route is asynchronous
                    if ($async) {
                        // Set async response
                        $core->async(true);
    
                        // If controller action has failed
                        if (!isset($result['status']) || !$result['status']) {
                            $result['message'] = "\n" . 'Event failed: ' . $routeMetadata[0];
                            $result['status'] = 0;
                        }
    
                        // Encode event result as json object
                        echo json_encode($result, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP);
    
                        // Mark as successful
                        $result = true;
                    }
                }

                // If no result is passed - consider success
                $result = $result !== false ? true : $result;
            }
        }

        //elapsed('Finished routing');
        // Return true or false depending on $result
        return $result;
    }
}
