<?php
/**
 * Created by PhpStorm.
 * User: VITALYIEGOROV
 * Date: 23.10.15
 * Time: 08:36
 */
namespace samsonphp\router;

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

    public function __construct($pattern, $identifier = null, $method = self::METHOD_ANY, $async = false, $cache = false)
    {
        $this->pattern = $pattern;
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
