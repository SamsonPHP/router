<?php
/**
 * Created by PhpStorm.
 * User: VITALYIEGOROV
 * Date: 25.10.15
 * Time: 20:55
 */
namespace samsonphp\router;

/**
 * Class generates routing logic function
 * @package samsonphp\router
 */
class Generator
{
    /**
     * Generate routing logic function
     * @param RouteCollection $routesCollection Routes collection for generating routing logic function
     * @return string PHP code for routing logic
     */
    public function generate(RouteCollection $routesCollection)
    {
        // Build multi-dimensional route array-tree
        $routeTree = array();
        foreach ($routesCollection as $route) {
            $map = array();
            // Split pattern
            foreach (explode('/', $route->pattern) as $routePart) {
                // Remove empty parts
                if (isset($routePart{0})) {
                    $map[] = '["' . $routePart . '"]';
                }
            }

            // Convert to string route type to save in array
            $type = $route->async ? Route::TYPE_ASYNC : Route::TYPE_SYNC;

            // Build array tree parameters from route pattern for building array structure
            $treeArray = sizeof($map) ? implode('', $map) : '["' . $route->pattern . '"]';

            //elapsed($type.'-'.$route->pattern.' -> $routeTree' . $treeArray . '= $route->identifier;',1);

            if ($route->method !== Route::METHOD_ANY) {// Build dynamic array-tree structure for specific method
                eval('$routeTree["' . $type . '"]["' . $route->method . '"]' . $treeArray . '= $route->identifier;');
            } else {// Build dynamic array-tree structure for all methods
                eval('$routeTree["' . $type . '"]["' . Route::METHOD_GET . '"]' . $treeArray . '= $route->identifier;');
                eval('$routeTree["' . $type . '"]["' . Route::METHOD_POST . '"]' . $treeArray . '= $route->identifier;');
                eval('$routeTree["' . $type . '"]["' . Route::METHOD_UPDATE . '"]' . $treeArray . '= $route->identifier;');
                eval('$routeTree["' . $type . '"]["' . Route::METHOD_DELETE . '"]' . $treeArray . '= $route->identifier;');
                eval('$routeTree["' . $type . '"]["' . Route::METHOD_PUT . '"]' . $treeArray . '= $route->identifier;');
            }
        }

        /**
         * Iterate found route types and create appropriate router logic function
         * for each route type/method key using specific $routeTree branch
         */
        $routerCallerCode = 'function __router($path, & $routes, $type, $method){' . "\n";
        $routerCallerCode .= '$matches = array();' . "\n";
        foreach ($routeTree as $routerType => $routerMethods) {
            $routerCallerCode .= 'if ($type === "' . $routerType . '") {' . "\n";
            foreach ($routerMethods as $routeMethod => $routes) {
                $routerCallerCode .= 'if ($method === "' . $routeMethod . '") {' . "\n";
                $routerCallerCode .= $this->recursiveGenerate($routeTree[$routerType][$routeMethod], '') . "\n";
                $routerCallerCode .= '}' . "\n";
            }
            $routerCallerCode .= '}' . "\n";
        }

        return $routerCallerCode . '}';
    }

    /**
     * Create router logic function.
     * This method is recursive
     * @param array|string $dataPointer Collection of routes or route identifier
     * @param string $path Current route tree path
     * @param string $code Final result
     * @param int $level Recursion level
     * @return string Router logic function
     */
    protected function recursiveGenerate(&$dataPointer, $path, &$code = '', $level = 1)
    {
        /** @var bool $conditionStarted Flag for creating conditions */
        $conditionStarted = false;

        // Count left spacing to make code looks better
        $tabs = implode('', array_fill(0, $level, ' '));
        foreach ($dataPointer as $placeholder => $data) {
            // Concatenate path
            $newPath = $path . '/' . $placeholder;

            // Add route description as a comment
            $code .= $tabs . '// ' . $newPath . "\n";

            // Count indexes
            $stLength = strlen($path);
            $stIndex = $stLength + 1;
            $length = strlen($placeholder);

            // Check if placeholder is a route variable
            if (preg_match('/{(?<name>[^}:]+)(\t*:\t*(?<filter>[^}]+))?}/i', $placeholder, $matches)) {
                // Define parameter filter or use generic
                $filter = isset($matches['filter']) ? $matches['filter'] : '[0-9a-z_]+';

                // Generate parameter route parsing, logic is that parameter can have any length so we
                // limit it either by closest brace(}) to the right or to the end of the string
                $code .= $tabs . 'if (preg_match("/(?<' . $matches['name'] . '>' . $filter . ')/i", substr($path, ' . $stIndex . ',  strpos($path, "/", ' . $stLength . ') ? strlen($path) - strpos($path, "/", ' . $stLength . ') : 0), $matches)) {' . "\n";

                //$code .= $tabs . 'trace("I am at ' . $path . '", 1);';
                // When we have route parameter we do not split logic tree as different parameters can match
                $conditionStarted = false;
            } else { // Generate route placeholder comparison
                $code .= $tabs . ($conditionStarted ? 'else ' : '') . 'if (substr($path, ' . $stIndex . ', ' . $length . ') === "' . $placeholder . '" ) {' . "\n";
                //$code .= $tabs . 'trace("I am at ' . $path . '", 1);';
                // Flag that condition group has been started
                $conditionStarted = true;
            }

            // This is route end - call handler
            if (is_string($data)) {
                $code .= $tabs . '     return array($routes["' . $data . '"], $matches);' . "\n";
            } else { // Go deeper in recursion
                $this->recursiveGenerate($data, $newPath, $code, $level + 5);
            }

            // Close current route condition group
            $code .= $tabs . '}' . "\n";
        }

        return $code;
    }
}
