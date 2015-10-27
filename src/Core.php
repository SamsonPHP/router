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
     * @param \samson\core\Core $core       Pointer to core object
     * @param mixed             $result     Return value as routing result
     * @param string            $default    Default route path
     */
    public function router(\samson\core\Core & $core, & $result, & $path, $default, $async = false)
    {
        //elapsed('Start routing');
        // Create SamsonPHP routing table from loaded modules
        $rg = new RouteGenerator($core->module_stack, $default);

        //elapsed('Created routes');

        $generator = new Generator();
        $routerLogic = $generator->generate($rg->routes());
        file_put_contents(s()->path().'www/cache/routing.cache.php', '<?php '.$routerLogic);
        eval($routerLogic);
        //elapsed('Created routing logic');


        if (is_array($routeData = __router($path, $rg->routes()))) {
            //elapsed('Found route');
            $route = $routeData[0];
            // Route parameters
            $parameters = array();

            // Gather parameters in correct order
            foreach ($route->parameters as $index => $name) {
                $parameters[] = & $routeData[1][$name];
            }

            // Perform controller action
            $result = is_callable($route->callback) ? call_user_func_array($route->callback, $parameters) : A_FAILED;

            trace($route,1);
            trace($parameters, 1);

            // Get object from callback & set it as current active core module
            $core->active($route->callback[0]);

            //trace($route->pattern, 1);

            // Check if request has special asynchronous markers
            if ($_SERVER['HTTP_ACCEPT'] == '*/*' || isset($_SERVER['HTTP_SJSASYNC']) || isset($_POST['SJSASYNC'])) {
                // If this route is asynchronous
                if ($route->async) {
                    // Anyway convert event result to array
                    if (!is_array($result)) $result = array($result);

                    // If event successfully completed
                    if (!isset($result['status']) || !$result['status']) {
                        // Handle event chain fail
                        $result['message'] = "\n" . 'Event failed: ' . $route->identifier;

                        // Add event result array to results collection
                        //$result = array_merge($event_result, $_event_result);

                        // Stop event-chain execution
                        //break;
                    } // Add event result array to results collection
                    //else $result = array_merge($result, $_event_result);

                    // If at least one event has been executed
                    if (sizeof($result)) {
                        // Set async response
                        $core->async(true);

                        // Send success status
                        header("HTTP/1.0 200 Ok");

                        // Encode event result as json object
                        echo json_encode($result, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP);

                        return A_SUCCESS;
                    }
                }

            }

            // If this route needs caching
            if ($route->cache) {
                $core->cached();
            }

            // Stop candidate search
            $result = !isset($result) ? A_SUCCESS : $result;
        }

        //elapsed('Finished routing');
    }

}
