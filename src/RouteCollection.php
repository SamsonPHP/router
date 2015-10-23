<?php
/**
 * Created by PhpStorm.
 * User: VITALYIEGOROV
 * Date: 23.10.15
 * Time: 08:35
 */
namespace samsonphp\router;

use samsonphp\router\exception\IdentifierDuplication;
use samsonphp\router\exception\NoMatchFound;

/**
 * Routes collection
 * @package samsonphp\router
 */
class RouteCollection implements \ArrayAccess
{
    /** @var Route[]  */
    protected $routes = array();

    /**
     * Find matching route by path
     * @param string $path Path for matching route patterns
     * @throws NoMatchFound
     */
    public function match($path)
    {
        $matchingRoute = null;
        foreach ($this->routes as $route) {
            if ($route->)
        }

        if(true) {

        } else {
            throw new NoMatchFound();
        }
    }

    /**
     * Add route
     * @param Route $route
     * @throws IdentifierDuplication
     */
    public function add(Route $route)
    {
        if (!isset($this->routes[$route->identifier])) {
            $this->routes[$route->identifier] = $route;
        } else {
            throw new IdentifierDuplication();
        }
    }

    /**
     * Whether a offset exists
     * @link http://php.net/manual/en/arrayaccess.offsetexists.php
     * @param mixed $offset <p>
     * An offset to check for.
     * </p>
     * @return boolean true on success or false on failure.
     * </p>
     * <p>
     * The return value will be casted to boolean if non-boolean was returned.
     * @since 5.0.0
     */
    public function offsetExists($offset)
    {
        return isset($this->routes[$offset]);
    }

    /**
     * Offset to retrieve
     * @link http://php.net/manual/en/arrayaccess.offsetget.php
     * @param mixed $offset <p>
     * The offset to retrieve.
     * </p>
     * @return mixed Can return all value types.
     * @since 5.0.0
     */
    public function offsetGet($offset)
    {
        return $this->routes[$offset];
    }

    /**
     * Offset to set
     * @link http://php.net/manual/en/arrayaccess.offsetset.php
     * @param mixed $offset <p>
     * The offset to assign the value to.
     * </p>
     * @param mixed $value <p>
     * The value to set.
     * </p>
     * @return void
     * @since 5.0.0
     */
    public function offsetSet($offset, $value)
    {
        $this->routes[$offset] = $value;
    }

    /**
     * Offset to unset
     * @link http://php.net/manual/en/arrayaccess.offsetunset.php
     * @param mixed $offset <p>
     * The offset to unset.
     * </p>
     * @return void
     * @since 5.0.0
     */
    public function offsetUnset($offset)
    {
        unset($this->routes[$offset]);
    }
}
