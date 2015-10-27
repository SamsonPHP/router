<?php
/**
 * Created by PhpStorm.
 * User: egorov
 * Date: 22.10.2015
 * Time: 16:20
 */
namespace samsonphp\router;
use samsonphp\router\exception\FailedLogicCreation;
use samsonphp\router\exception\NoMatchFound;

/**
 * Main routing logic
 * @package samsonphp\router
 */
class Core
{
    /** @var array Collection of all application routes */
    protected $routes = array();

    /**
     * Define if HTTP request is asynchronous
     * @return bool True if request is asynchronous
     */
    protected function isAsynchronousRequest()
    {
        return $_SERVER['HTTP_ACCEPT'] == '*/*' || isset($_SERVER['HTTP_SJSASYNC']) || isset($_POST['SJSASYNC']);
    }

    /**
     * Dispatch HTTP request
     * @param $path
     * @param $routes
     * @param $type
     * @param $method
     * @param null $route
     * @return bool|mixed
     * @throws FailedLogicCreation
     */
    protected function dispatch($path, $method, &$route = null)
    {
        //elapsed('Started dispatching routes');

        // Create routing logic generator
        $generator = new Generator();

        // Generate routing logic from routes
        $routerLogic = $generator->generate($this->routes);

        //file_put_contents(s()->path() . 'www/cache/routing.cache.php', '<?php ' . $routerLogic);
        //elapsed('Created routing logic');

        // Evaluate routing logic function
        eval($routerLogic);
        if (function_exists('__router')) {
            // Perform routing logic
            if (is_array($routeData = __router($path, $this->routes, $method))) {
                //elapsed('Found route');
                /** @var Route $route Retrieve found Route object */
                $route = $routeData[0];

                // Gather parsed route parameters in correct order
                $parameters = array();
                foreach ($route->parameters as $index => $name) {
                    $parameters[] = &$routeData[1][$name];
                }

                // Perform route callback action
                $result = is_callable($route->callback) ? call_user_func_array($route->callback, $parameters) : false;

                return isset($result) ? $result : true;
            }
        }

        throw new FailedLogicCreation();
    }

    /**
     * SamsonPHP core.routing event handler
     *
     * @param \samson\core\Core $core Pointer to core object
     * @param mixed $result Return value as routing result
     * @param string $default Default route path
     */
    public function router(\samson\core\Core & $core, & $result, & $path, $default, $async = false)
    {
        //elapsed('Start routing');
        $async = $this->isAsynchronousRequest();

        // Get HTTP request method
        $method = $_SERVER['REQUEST_METHOD'];
        // Prepend HTTP request type, true - asynchronous
        $method = ($async ? GenericInterface::ASYNC_PREFIX : '').$method;

        // Create SamsonPHP routing table from loaded modules
        $rg = new RouteGenerator($core->module_stack, $default);
        $this->routes = $rg->routes();

        //elapsed('Created routes');

        /** @var Route $route Found route object */
        $route = null;
        if ($this->dispatch($path, $method, $route)) {
            // Get object from callback & set it as current active core module
            $core->active($route->callback[0]);

            // If this route is asynchronous
            if ($async) {
                // Set async response
                $core->async(true);

                // If controller action has failed
                if (!isset($result['status']) || !$result['status']) {
                    // Handle event chain fail
                    $result['message'] = "\n" . 'Event failed: ' . $route->identifier;
                }

                // Encode event result as json object
                echo json_encode($result, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP);

                // Successfully stop routing execution
                return true;
            }

            // Stop candidate search
            $result = !isset($result) ? true : $result;
        }
        //elapsed('Finished routing');
    }
}
