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

use Dotpay\Dotpay;

/**
 * Mapping specified Dotpay payment gateways hooks to methods of Dotpay channel class used to generating visible payment forms
 */

/**
 * BLIK
 */
function commerce_dotpay_blik_redirect_form($form, &$form_state, $order, $payment_method) {
    return Dotpay::getInstance()->getBlik()->getFrontForm($form, $form_state, $order, $payment_method);
}

/**
 * Standard
 */
function commerce_dotpay_standard_redirect_form($form, &$form_state, $order, $payment_method) {
    return Dotpay::getInstance()->getStandard()->getFrontForm($form, $form_state, $order, $payment_method);
}

/**
 * MasterPass
 */
function commerce_dotpay_mp_redirect_form($form, &$form_state, $order, $payment_method) {
    return Dotpay::getInstance()->getMasterPass()->getFrontForm($form, $form_state, $order, $payment_method);
}

/**
 * CC
 */
function commerce_dotpay_cc_redirect_form($form, &$form_state, $order, $payment_method) {
    return Dotpay::getInstance()->getCC()->getFrontForm($form, $form_state, $order, $payment_method);
}

/**
 * CC
 */
function commerce_dotpay_oc_redirect_form($form, &$form_state, $order, $payment_method) {
    return Dotpay::getInstance()->getOneClick()->getFrontForm($form, $form_state, $order, $payment_method);
}