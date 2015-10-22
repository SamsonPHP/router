<?php
/**
 * Created by PhpStorm.
 * User: egorov
 * Date: 22.10.2015
 * Time: 16:11
 */
namespace samsonphp\router;

/**
 * Gives ability to define routes and their callback for a class
 * @package samsonphp\router
 */
interface RouteInterface
{
    /**
     * @return array Collection of route identifiers, their patterns and callbacks
     */
    public function routes();
}
