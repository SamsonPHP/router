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
        // Create SamsonPHP routing table from loaded modules
        $rg = new RouteGenerator($core->module_stack, $default);

        if (($route = $rg->routes()->match($path)) !== false) {

            // Get object from callback & set it as current active core module
            $core->active($route->callback[0]);

            //trace($route->pattern, 1);

            // Route parameters
            $parameters = array();

            // Check if request has special asynchronous markers
            if ($_SERVER['HTTP_ACCEPT'] == '*/*' || isset($_SERVER['HTTP_SJSASYNC']) || isset($_POST['SJSASYNC'])) {
                // If this route is asynchronous
                if ($route->async) {

                    // Perform controller action
                    $result = is_callable($route->callback) ? call_user_func_array($route->callback, $parameters) : A_FAILED;

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

            } else { // Synchronous controller
                // Perform controller action
                $result = is_callable($route->callback) ? call_user_func_array($route->callback, $parameters) : A_FAILED;

            }

            // If this route needs caching
            if ($route->cache) {
                $core->cached();
            }

            // Stop candidate search
            $result = !isset($result) ? A_SUCCESS : $result;
        }
    }
}
