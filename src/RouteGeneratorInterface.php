<?php
/**
 * Created by PhpStorm.
 * User: VITALYIEGOROV
 * Date: 27.10.15
 * Time: 18:46
 */
namespace samsonphp\router;

/**
 * Route generation Interface
 * @package samsonphp\router
 */
interface RouteGeneratorInterface
{
    /**
     * @return RouteCollection Collection of generated routes
     */
    public function & generate();
}
