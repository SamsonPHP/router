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
    /** @var string Route identifier */
    public $identifier;

    /** @var string HTTP method supported */
    public $method = 'ANY';

    /** @var boolean Flag that only synchronous HTTP requests are supported */
    public $async = false;

    /** @var boolean Flag that this route should be cached */
    public $cache = false;

    /** @var boolean Flag that only synchronous HTTP requests are supported */
    public $pattern;

    /** @var array Parameters configuration */
    public $parameters = array();
}
