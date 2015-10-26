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
    public function generate(RouteCollection & $routesCollection)
    {
        // Build multi-dimentional route array-tree
        $routeTree = array();
        foreach ($routesCollection as $route) {
            $map = array();
            foreach (explode('/', $route->pattern) as $routePart) {
                // Remove empty parts
                if (isset($routePart{0})) {
                    $map[] = '["' . $routePart . '"]';
                }
            }
            eval('$routeTree'.implode('', $map).'= $route->identifier;');
        }

        // Wrap routing logic into function to support returns
        $routerCode = 'function __router($path, array & $routes) {'."\n";
        $routerCode.= $this->recursiveGenerate($routeTree, '')."\n".'}';

        return $routerCode;
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
            $stIndex = strlen($path) + 1;
            $length = strlen($placeholder);

            // Check if placeholder is a route variable
            if (preg_match('/{(?<name>[^}:]+)(\t*:\t*(?<filter>[^}]+))?}/i', $placeholder, $matches)) {
                // Define parameter filter or use generic
                $filter = isset($matches['filter']) ? $matches['filter'] : '[0-9a-z_]+';

                // Generate parameter route parsing, logic is that parameter can have any length so we
                // limit it either by closest brace(}) to the right or to the end of the string
                $code .= $tabs . 'if (preg_match("/(?<' . $matches['name'] . '>'.$filter.'+)/i", substr($path, ' . $stIndex . ',  strpos($path, "/", ' . $stIndex . ') ? strlen($path) - strpos($path, "/", ' . $stIndex . ') : 0), $matches)) {' . "\n";

                // When we have route parameter we do not split logic tree as different parameters can match
                $conditionStarted = false;
            } else { // Generate route placeholder comparison
                $code .= $tabs . ($conditionStarted ? 'else ' : '') . 'if (substr($path, ' . $stIndex . ', ' . $length . ') === "' . $placeholder . '" ) {' . "\n";

                // Flag that condition group has been started
                $conditionStarted = true;
            }

            // This is route end - call handler
            if (is_string($data)) {
                $code .= $tabs . '     return $routes["' . $data . '"]->callback;' . "\n";
            } else { // Go deeper in recursion
                $this->recursiveGenerate($data, $newPath, $code, $level + 5);
            }

            // Close current route condition group
            $code .= $tabs . '}' . "\n";
        }

        return $code;
    }
}
