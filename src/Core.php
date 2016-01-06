<?php
/**
 * Created by PhpStorm.
 * User: egorov
 * Date: 22.10.2015
 * Time: 16:20
 */
namespace samsonphp\router;

use samsonframework\core\SystemInterface;
use samsonframework\routing\Route;

/**
 * Main routing logic core.
 *
 * @see samsonframework\routing\Core;
 * @package samsonphp\router
 */
class Core extends \samsonframework\routing\Core
{
    /**
     * Define if HTTP request is asynchronous
     * @return bool True if request is asynchronous
     */
    protected function isAsynchronousRequest()
    {
        return $_SERVER['HTTP_ACCEPT'] == '*/*'
        || isset($_SERVER['HTTP_SJSASYNC'])
        || isset($_POST['SJSASYNC']);
    }

    /**
     * Parse route parameters received from router logic function.
     *
     * @param Route $route Route instance
     * @param array $receivedParameters Collection of parsed parameters
     * @return array Collection of route callback needed parameters
     */
    protected function parseParameters(Route $route, array $receivedParameters)
    {
        // Gather parsed route parameters in correct order
        $parameters = array();
        foreach ($route->parameters as $name) {
            // Add to parameters collection
            $parameters[] = &$receivedParameters[$name];
        }
        return $parameters;
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
        $path = $_SERVER['REQUEST_URI'];
        // Get HTTP request method
        $method = $_SERVER['REQUEST_METHOD'];
        // Prepend HTTP request type, true - asynchronous
        $method = ($async ? GenericRouteGenerator::ASYNC_PREFIX : '').$method;

        $result = false;

        /** @var mixed $routeMetadata Dispatching result route metadata */
        if (is_array($routeMetadata = $this->dispatch($path, $method))) {
            /** @var Route $route Found route object */
            $route = $this->routes[$routeMetadata[0]];

            // Routing result
            $result = call_user_func_array($route->callback, $this->parseParameters($route, $routeMetadata[1]));

            // Get object from callback and set it as current active core module
            $core->active($route->callback[0]);

            // If this route is asynchronous
            if ($async) {
                // Set async response
                $core->async(true);

                // If controller action has failed
                if (!isset($result['status']) || !$result['status']) {
                    $result['message'] = "\n" . 'Event failed: ' . $route->identifier;
                    $result['status'] = 0;
                }

                // Encode event result as json object
                echo json_encode($result, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP);

                // Mark as successful
                $result = true;
            }

            // If no result is passed - consider success
            $result = $result !== false ? true : $result;
        }

        //elapsed('Finished routing');
        // Return true or false depending on $result
        return $result;
    }
}
