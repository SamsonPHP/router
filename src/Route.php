<?php
/**
 * Created by PhpStorm.
 * User: VITALYIEGOROV
 * Date: 23.10.15
 * Time: 08:36
 */
namespace samsonphp\router;
use samsonphp\router\exception\ArrayToObjectConversion;

/**
 * Route
 * @package samsonphp\router
 */
class Route
{
    /** Route method identifiers */
    const METHOD_ANY = 'ANY';
    const METHOD_GET = 'GET';
    const METHOD_POST = 'POST';
    const METHOD_PUT = 'PUT';
    const METHOD_DELETE = 'DELETE';
    const METHOD_UPDATE = 'UPDATE';

    /** @var string Route identifier */
    public $identifier;

    /** @var string HTTP method supported */
    public $method = self::METHOD_ANY;

    /** @var boolean Flag that only synchronous HTTP requests are supported */
    public $async = false;

    /** @var boolean Flag that this route should be cached */
    public $cache = false;

    /** @var string Internal pattern for matching */
    public $pattern;

    /** @var string RegExp compiled from internal pattern for matching */
    public $regexpPattern;

    /** @var array Parameters configuration */
    public $parameters = array();

    /** @var callable Route handler */
    public $callback;

    /**
     * Create route from array
     * @param string $pattern
     * @param array $data
     * @return Route New Route object instance
     * @throws ArrayToObjectConversion
     */
    public static function fromArray($pattern, array $data)
    {
        if (sizeof($data) >= 4) {
            return new self($pattern, $data[0], $data[1], isset($data[4]) ? $data[4] : self::METHOD_ANY, $data[2], $data[3]);
        }

        throw new ArrayToObjectConversion();
    }

    /**
     * @param string $pattern Route matching pattern
     * @param callable $callback Callback for route
     * @param string|null $identifier Route unique identifier, if empty - unique will be generated
     * @param string $method HTTP request method
     * @param bool|false $async Route asynchronous flag
     * @param bool|false $cache Route caching flag
     */
    public function __construct($pattern, $callback, $identifier = null, $method = self::METHOD_ANY, $async = false, $cache = false)
    {
        $this->pattern = $pattern;
        $this->callback = $callback;
        $this->method = $method;
        $this->async = $async;
        $this->cache = $cache;
        // Every route should have an identifier otherwise create unique
        $this->identifier = isset($identifier) ? $identifier : uniqid('route');
        // Compile to regexp
        $this->regexpPattern = $this->internalToRegExp($this->pattern);
    }

    /**
     * Transform internal pattern format to RegExp
     * @param string $input Internal format route pattern
     * @return string RegExp prepared pattern
     */
    public function internalToRegExp($input)
    {
        return '/^'.
            str_ireplace(
                '/', '\/',
                str_ireplace(
                    '/*', '/.*',
                    preg_replace('/@([a-z0-9]_-+)/ui', '(?<$1>[^/]+)', $input)
                )
            ).'/ui';
    }

    /**
     * Try matching route pattern with path
     * @param string $path Path for matching route
     * @return int Matched pattern length
     */
    public function match($path)
    {
        trace($this->regexpPattern.'-'.$path, 1);
        $matches = array();
        if(preg_match($this->pattern, $path, $matches)){
            return strlen($this->pattern);
        } else {
            return false;
        }
    }

    /**
     * Parse route path parameters
     * @param string $path Path for parsing route
     * @return array Key=>value collection of route path parameters
     */
    public function parse($path)
    {
        return $this->parameters;
    }
}
