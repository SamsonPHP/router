<?php
/**
 * Created by PhpStorm.
 * User: egorov
 * Date: 22.10.2015
 * Time: 16:20
 */
namespace samsonphp\router;
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
     * SamsonPHP core.routing event handler
     *
     * @param \samson\core\Core $core Pointer to core object
     * @param mixed $result Return value as routing result
     * @param string $default Default route path
     */
    public function router(\samson\core\Core & $core, & $result, & $path, $default, $async = false)
    {
        //elapsed('Start routing');
        // Create SamsonPHP routing table from loaded modules
        $rg = new RouteGenerator($core->module_stack, $default);

        //elapsed('Created routes');

        $generator = new Generator();
        $routerLogic = $generator->generate($rg->routes());
        file_put_contents(s()->path() . 'www/cache/routing.cache.php', '<?php ' . $routerLogic);
        eval($routerLogic);
        //elapsed('Created routing logic');

        // Get HTTP request method
        $method = $_SERVER['REQUEST_METHOD'];
        // Get HTTP request type, true - asynchronous
        $type = $_SERVER['HTTP_ACCEPT'] == '*/*' || isset($_SERVER['HTTP_SJSASYNC']) || isset($_POST['SJSASYNC']) ? Route::TYPE_ASYNC : Route::TYPE_SYNC;

        //trace($type, 1);
        // Perform routing logic
        if (is_array($routeData = __router($path, $rg->routes(), $type, $method))) {
            //elapsed('Found route');
            $route = $routeData[0];
            // Route parameters
            $parameters = array();

            // Gather parameters in correct order
            foreach ($route->parameters as $index => $name) {
                $parameters[] = &$routeData[1][$name];
            }

            // Perform controller action
            $result = is_callable($route->callback) ? call_user_func_array($route->callback, $parameters) : A_FAILED;

            //trace($route, 1);
            //trace($parameters, 1);

            // Get object from callback & set it as current active core module
            $core->active($route->callback[0]);

            // If this route needs caching
            if ($route->cache) {
                $core->cached();
            }

            // If this route is asynchronous
            if ($route->async) {
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
