<?php
/**
 * Created by PhpStorm.
 * User: VITALYIEGOROV
 * Date: 23.10.15
 * Time: 11:15
 */
namespace samsonphp\router;

/**
 * Generic routing interface
 * @package samsonphp\router
 */
interface GenericInterface
{
    /** Default controller name */
    const CTR_BASE = '__base';
    const CTR_CACHE_BASE = '__cache_base';

    /** Universal controller name */
    const CTR_UNI = '__handler';
    const CTR_CACHE_UNI = '__cache_handler';

    /** Post controller name */
    const CTR_POST = '__post';
    const CTR_CACHE_POST = '__cache_post';

    /** Put controller name */
    const CTR_PUT = '__put';
    const CTR_CACHE_PUT = '__cache_put';

    /** Delete controller name */
    const CTR_DELETE = '__delete';
    const CTR_CACHE_DELETE = '__cache_delete';

    /** Delete controller name */
    const CTR_UPDATE = '__update';
    const CTR_CACHE_UPDATE = '__cache_update';

    /** Controllers naming conventions */

    /** Procedural controller prefix */
    const PROC_PREFIX = '_';
    /** OOP controller prefix */
    const OBJ_PREFIX = '__';
    /** AJAX controller prefix */
    const ASYNC_PREFIX = 'async_';
    /** CACHE controller prefix */
    const CACHE_PREFIX = 'cache_';
}
