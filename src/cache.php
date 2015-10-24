<?php
/**
 * Created by PhpStorm.
 * User: VITALYIEGOROV
 * Date: 24.10.15
 * Time: 12:33
 */
$path = '/material/form/102'; $path = '/material/list/11';
$matches = array();
// TODO: HTTP Method type filtering

/**
 * This is generated route parsing tree
 * Idea is to create nested ifs matching route structure
 * to create the most effective router ever
 */
if (substr($path, 0, 9) === '/material/') {
    if (substr($path, 9, 5) === 'form/') {
        // /material/form/{id:\d+}
        if (preg_match('/(?<id>\d+)/', substr($path, 14), $matches)) {
            /**
             * This is generic implementation as this module should not be related to any framework
             * so external handler should be called to generate correct callback execution
             */
            //return call_user_func_array($callback, array($matches['id']));
            // SamsonPHP implementation, as we know what to write upon this file generation
            m('material')->__form($matches['id']);
        }
    } else if (substr($path, 9, 5) === 'list/') {
        // /material/list/{structure:\d+}[/{page:\d+}[/{filter:asc|dsc}]]
        if (preg_match('/(?<structure>\d+)\/(?<page>\d+)?\/(?<filter>asc|dsc)?/', substr($path, 14), $matches)) {
            return call_user_func_array($callback, array($matches['structure'], $matches['page'], $matches['filter']));
        }
    }
} else if (substr($path, 0, 9) === '/user/') {
    //..
} else { // Route not found
    return '404';
}