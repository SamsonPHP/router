<?php
namespace samsonphp\router;

use samson\core\SamsonLocale;
use samsonframework\core\SystemInterface;
use samsonframework\routing\Core;
use samsonframework\routing\generator\Structure;
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
        // If callback is passed  - function name
        if (is_callable($this->defaultModule)) {
            // Use it as main controller callback
            $callback = $this->defaultModule;
            // Consider as module identifier is passed
        } elseif (isset($this->system->module_stack[$this->defaultModule])) {
            // Try to find module universal controller action
            $callback = $this->system->module_stack[$this->defaultModule]->id.'#'.self::CTR_UNI;
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
        // Create SamsonPHP routing table from loaded modules
        $rg = new GenericRouteGenerator($this->system->module_stack);

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

        // This should be change to receive path as a parameter on initialization
        $pathParts = explode(Route::DELIMITER, $_SERVER['REQUEST_URI']);
        SamsonLocale::parseURL($pathParts);
        $this->requestURI = implode(Route::DELIMITER, $pathParts);

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
        return $_SERVER['HTTP_ACCEPT'] == '*/*'
        || isset($_SERVER['HTTP_SJSASYNC'])
        || isset($_POST['SJSASYNC']);
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
        $path = rtrim(strtok(ltrim($path, '/'), '?'), '/');

        /** @var mixed $routeMetadata Dispatching result route metadata */
        if (is_array($routeMetadata = call_user_func(Core::ROUTING_LOGIC_FUNCTION, $path, $method))) {
            // Get callback info
            list($module, $method) = explode("#", $routeMetadata[2]);
            // Get module
            $module = $core->module($module);
            // Create callback
            $callback = array($module, $method);

            // Check if we have valid callback
            if (is_callable($callback)) {
                // Routing result
                $result = call_user_func_array(
                    $callback,
                    $this->parseParameters($callback, $routeMetadata[1])
                );

                // Get object from callback and set it as current active core module
                $core->active($module);

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

        //elapsed('Finished routing');
        // Return true or false depending on $result
        return $result;
    }
}
