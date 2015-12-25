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

        // Routing result
        $result = false;

        //elapsed('Created routes');

        /** @var Route $route Found route object */
        $route = null;

        /** @var mixed $result Dispatching result, usually route callback result */
        if ($result = $this->dispatch($path, $method, $route)) {
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
            $result = !isset($result) ? true : $result;
        }

        //elapsed('Finished routing');
        return $result;
    }
}
