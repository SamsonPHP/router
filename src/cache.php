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

// This is generated route parsing tree
if (substr($path, 0, 9) === '/material/') {
    if (substr($path, 9, 5) === 'form/') {
        if (preg_match('/(?<id>\d+)/', substr($path, 14), $matches)) {
            return call_user_func_array($routes['material_form']->callback, array($matches['id']));
        }
    } else if (substr($path, 9, 5) === 'list/') {
        if (preg_match('/(?<structure>\d+)\/(?<page>\d+)?\/(?<filter>asc|dsc)?/', substr($path, 14), $matches)) {
            return call_user_func_array($routes['material_list']->callback, array($matches['structure'], $matches['page'], $matches['filter']));
        }
    }
} else if (substr($path, 0, 9) === '/user/') {
    //..
} else { // Route not found
    return '404';
}