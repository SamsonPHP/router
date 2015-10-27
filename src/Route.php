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

    /** @var array Collection of all supported HTTP methods */
    public static $METHODS = array(
        self::METHOD_GET,
        self::METHOD_POST,
        self::METHOD_PUT,
        self::METHOD_DELETE,
        self::METHOD_UPDATE
    );

    /** @var string Route identifier */
    public $identifier;

    /** @var string HTTP method supported */
    public $method = self::METHOD_ANY;

    /** @var string Internal pattern for matching */
    public $pattern;

    /** @var string RegExp compiled from internal pattern for matching */
    public $regexpPattern;

    /** @var array Parameters configuration */
    public $parameters = array();

    /** @var callable Route handler */
    public $callback;

    /**
     * @param string $pattern Route matching pattern
     * @param callable $callback Callback for route
     * @param string|null $identifier Route unique identifier, if empty - unique will be generated
     * @param string $method HTTP request method
     * @param bool|false $async Route asynchronous flag
     * @param bool|false $cache Route caching flag
     */
    public function __construct($pattern, $callback, $identifier = null, $method = self::METHOD_ANY)
    {
        $this->pattern = $pattern;
        $this->callback = $callback;
        $this->method = $method;
        // Every route should have an identifier otherwise create unique
        $this->identifier = isset($identifier) ? $identifier : uniqid('route');
        // Compile to regexp
        $this->regexpPattern = $this->internalToRegExp($this->pattern);

        // Parse callback signature and get parameters list
        if (is_callable($callback)) {
            $reflectionMethod = is_array($callback) ? new \ReflectionMethod($callback[0], $callback[1]) : new \ReflectionFunction($callback);
            foreach ($reflectionMethod->getParameters() as $parameter) {
                $this->parameters[] = $parameter->getName();
            }
        }
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
        if(preg_match($this->regexpPattern, $path, $matches)){
            //trace('Match: '.$this->regexpPattern.'('.strlen($this->pattern).')',1);
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
