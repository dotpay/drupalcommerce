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
use Dotpay\Form;
use Dotpay\Back;
use Dotpay\Card;
use Dotpay\Confirm;
use Dotpay\Instruction;
use Dotpay\Channel\AbstractChannel;

require_once(__DIR__.'/include/bootstrap.php');
require_once(__DIR__.'/commerce_dotpay_channels.inc');

define('DOTPAY_GATEWAY_VERSION', '2.0.2');

/**
 * Implemenation of hook_commerce_payment_method_info()
 * Returns data of all Dotpay payments gateway supplied by plugin
 * @return array
 */
function commerce_dotpay_commerce_payment_method_info() {
    drupal_add_css(drupal_get_path('module', 'commerce_dotpay').'/web/css/main.css');
    return Dotpay::getInstance()->getChannelList();
}

/**
 * Implementation of hook_settings_form()
 * Returns data for Drupal API Forms with form of plugin settings
 * @param array|null $settings
 * @return array
 */
function dotpay_gateway_settings_form($settings = NULL) {
    return Dotpay::getInstance()
           ->getSettings()
           ->setDefault()
           ->update($settings)
           ->getForm();
}

/**
 * Implementation of hook_form_FORM_ID_alter()
 * Adds channel thumb images to all Dotpay gateways
 * @param array $form Form structure
 * @param array $form_state Form state
 */
function commerce_dotpay_form_commerce_checkout_form_alter(&$form, &$form_state) {
    if (!empty($form['commerce_payment']['payment_method']['#options'])) {
        foreach ($form['commerce_payment']['payment_method']['#options'] as $key => &$value) {
            list($method_id, $rule_name) = explode('|', $key);
            if ($method_id == 'dotpay_standard_channel') {
                $value = Dotpay::getInstance()->getStandard()->getThumb().$value;
            }
            else if ($method_id == 'dotpay_blik_channel') {
                $value = Dotpay::getInstance()->getBlik()->getThumb().$value;
            }
            else if ($method_id == 'dotpay_mp_channel') {
                $value = Dotpay::getInstance()->getMasterPass()->getThumb().$value;
            }
            else if ($method_id == 'dotpay_cc_channel') {
                $value = Dotpay::getInstance()->getCC()->getThumb().$value;
            }
            else if ($method_id == 'dotpay_oc_channel') {
                $value = Dotpay::getInstance()->getOneClick()->getThumb().$value;
            }
        }
    }
}

/**
 * Implementation of hook_menu().
 * Returns pages added by this plugin to site
 * @return array
 */
function commerce_dotpay_menu() {
    $items = array();

    $items['dotpay/%/%/form'] = array(
        'title' => t('Payment'),
        'page callback' => 'commerce_dotpay_router_form',
        'page arguments' => array(1,2),
        'type' => MENU_CALLBACK,
        'access callback' => true,
    );
    $items['dotpay/%/back'] = array(
        'title' => t('Waiting'),
        'page callback' => 'commerce_dotpay_back',
        'page arguments' => array(1),
        'access callback' => true,
        'access arguments' => array('access content'),
    );
    $items['dotpay/confirm'] = array(
        'title' => t('Waiting'),
        'page callback' => 'commerce_dotpay_confirm',
        'type' => MENU_CALLBACK,
        'access callback' => true,
    );
    $items['user/%user/ocmanage'] = array(
        'title' => t('My saved cards'),
        'page callback' => 'commerce_dotpay_ocmanage',
        'page arguments' => array(1),
        'type' => MENU_LOCAL_TASK,
        'access callback' => true,
        'weight' => 20,
    );
    $items['dotpay/oc/remove'] = array(
        'page callback' => 'commerce_dotpay_ocremove',
        'type' => MENU_CALLBACK,
        'access callback' => true,
    );
    $items['dotpay/%/%/instruction'] = array(
        'title' => t('Waiting'),
        'page callback' => 'commerce_dotpay_instruction',
        'type' => MENU_CALLBACK,
        'access callback' => true,
        'page arguments' => array(1,2),
    );
    $items['dotpay/%/status'] = array(
        'page callback' => 'commerce_dotpay_check_status',
        'type' => MENU_CALLBACK,
        'access callback' => true,
        'page arguments' => array(1),
    );
    return $items;
}

/**
 * Displays hidden form depending on the given order id and Dotpay channel name; implementation of page callback
 * @param int $order Order id
 * @param string $method Dotpay channel name
 */
function commerce_dotpay_router_form($order, $method) {
    $channelDriver = null;
    switch($method) {
        case 'oc':
            $channelDriver = Dotpay::getInstance()->getOneClick();
            break;
        case 'cc':
            $channelDriver = Dotpay::getInstance()->getCC();
            break;
        case 'blik':
            $channelDriver = Dotpay::getInstance()->getBlik();
            break;
        case 'mp':
            $channelDriver = Dotpay::getInstance()->getMasterPass();
            break;
        case 'standard':
            $channelDriver = Dotpay::getInstance()->getStandard();
            break;
    }
    $channel = Dotpay::getParam('channel');
    if(!empty($channel) && Dotpay::isChannelInGroup($order, $channel, array(AbstractChannel::cashGroup, AbstractChannel::transferGroup)))
        header('Location: '.url('dotpay/'.$order.'/'.Dotpay::getParam('channel').'/instruction'));
    if($channelDriver === NULL)
        die(t('Channel payment is incorrect. Please try to place your order again.'));
    $form = new Form($channelDriver);
    echo $form->render($order);
    die();
}

/**
 * Returns page data, which would be shown on payment back page; implementation of page callback
 * @param int $orderId Order id
 * @return array
 */
function commerce_dotpay_back($orderId) {
    $backPage = new Back($orderId);
    $page =  array(
        'content' => array(
            '#type' => 'markup',
            '#markup' => $backPage->render(),
        )
    );
    return $page;
}

/**
 * Processes request with payment confirmation from Dotpay; implementation of page callback
 */
function commerce_dotpay_confirm() {
    $confirm = new Confirm();
    echo $confirm->make();
    die();
}

/**
 * Displays page with list of saved cards for One Click payment method; implementation of page callback
 * @global object $user Drupal user object
 * @param type $account User uid
 * @return type
 */
function commerce_dotpay_ocmanage($account) {
    global $user;
    drupal_set_title(t('Manage your Credit Cards (for One Click payments)'));
    drupal_set_page_content(Dotpay::render(__DIR__.'/templates/ocmanage.phtml', array('cards'=>Card::getUsefulCardsForCustomer($user->uid))));
    return array();
}

/**
 * Removes saved credit card based on request parameters; implementation of page callback
 * @global object $user Drupal user object
 */
function commerce_dotpay_ocremove() {
    global $user;
    $card = Card::getCardById($_POST['cardId']);
    if($card !== false && $user->uid==$card->customer_id) {
        Card::removeCard($_POST['cardId']);
        die('1');
    }
    die('0');
}

/**
 * Displays page with instruction of payment by transfer or cash channels; implementation of page callback
 * @param int $orderId Order id
 * @param type $channelId Channel id
 * @return type
 */
function commerce_dotpay_instruction($orderId, $channelId) {
    drupal_add_css(drupal_get_path('module', 'commerce_dotpay').'/web/css/microBootstrap.css');
    $instruction = Dotpay::getInstance()->getStandard()->prepareTransferPayment($orderId, $channelId);
    drupal_set_title(t('Instruction of your payment'));
    drupal_set_page_content(Dotpay::render(__DIR__.'/templates/instruction.phtml', array('instruction' => $instruction)));
    return array();
}

/**
 * Checks payment status of order; implementation of page callback
 * @param type $orderId order id
 */
function commerce_dotpay_check_status($orderId) {
    $transactions = commerce_payment_transaction_load_multiple(array(), array('order_id' => $orderId));
    $transaction = array_pop($transactions);
    switch($transaction->status) {
        case COMMERCE_PAYMENT_STATUS_SUCCESS:
            die('1');
        case COMMERCE_PAYMENT_STATUS_FAILURE:
            die('-1');
        default:
            die('0');
    }
}

