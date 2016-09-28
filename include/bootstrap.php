<?php

/**
*
*
* NOTICE OF LICENSE
*
* This source file is subject to the Academic Free License (AFL 3.0)
* that is bundled with this package in the file LICENSE.txt.
* It is also available through the world-wide-web at this URL:
* http://opensource.org/licenses/afl-3.0.php
* If you did not receive a copy of the license and are unable to
* obtain it through the world-wide-web, please send an email
* to tech@dotpay.pl so we can send you a copy immediately.
*
* DISCLAIMER 
*
* Do not edit or add to this file if you wish to upgrade Drupal Commerce to newer
* versions in the future. If you wish to customize Drupal Commerce for your
* needs please refer to http://www.dotpay.pl for more information.
*
*  @author    Dotpay Team <tech@dotpay.pl>
*  @copyright Dotpay
*  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
*
*/

/**
 * Fix for PHP older than 7.0
 * @param string $dir
 * @param int $levels
 * @return string
 */
function mydirname($dir, $levels) {
    while(--$levels)
        $dir = dirname($dir);
    return $dir;
}

/**
 * Checks, if class with the specified name can be loaded and loads it
 * @param string $className name of loaded class
 * @return boolean
 */
function dotpay_autoloader($className) {
    if(strpos($className, 'Dotpay') !== 0)
        return false;
    $path = __DIR__.str_replace('\\', '/', preg_replace('/Dotpay/', '', $className, 1)).'.php';
    if(!file_exists($path))
        return false;
    require_once($path);
}

/**
 * Checks, if session is already started
 * @return boolean
 */
function is_session_started()
{
    if ( php_sapi_name() !== 'cli' ) {
        if ( version_compare(phpversion(), '5.4.0', '>=') ) {
            return session_status() === PHP_SESSION_ACTIVE ? TRUE : FALSE;
        } else {
            return session_id() === '' ? FALSE : TRUE;
        }
    }
    return FALSE;
}

if(!is_session_started())
    session_start();

spl_autoload_register('dotpay_autoloader');