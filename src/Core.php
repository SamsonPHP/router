<?php
/**
 * Created by PhpStorm.
 * User: egorov
 * Date: 22.10.2015
 * Time: 16:20
 */
namespace samsonphp\router;

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
        return $_SERVER['HTTP_ACCEPT'] == '*/*' || isset($_SERVER['HTTP_SJSASYNC']) || isset($_POST['SJSASYNC']);
    }

    /**
     * SamsonPHP core.routing event handler
     *
     * @param \samson\core\Core $core Pointer to core object
     * @param mixed $result Return value as routing result
     * @param string $default Default route path
     * @return bool Routing result
     */
    public function router(\samson\core\Core & $core, & $result, $default)
    {
        $path = $_SERVER['REQUEST_URI'];

        //elapsed('Start routing');
        $async = $this->isAsynchronousRequest();

        // Get HTTP request method
        $method = $_SERVER['REQUEST_METHOD'];
        // Prepend HTTP request type, true - asynchronous
        $method = ($async ? GenericRouteGenerator::ASYNC_PREFIX : '').$method;

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

        // We could not dispatch route
        return false;
        //elapsed('Finished routing');
    }
}
