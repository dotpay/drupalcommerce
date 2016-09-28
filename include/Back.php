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

namespace Dotpay;

use Dotpay\Dotpay;

/**
 * Provides functionality to support back page in payment process
 */
class Back {
    private $template;
    
    private $order;
    
    private $message = NULL;
    
    /**
     * Prepares object and sets potential error
     * @param object $order Drupal Commerce order object
     */
    public function __construct($order) {
        $this->order = $order;
        $this->template = dirname(__DIR__).'/templates/back.phtml';
        $this->order = commerce_order_load($order);
        
        if(Dotpay::getParam('error_code')!==false) {
            switch(Dotpay::getParam('error_code'))
            {
                case 'PAYMENT_EXPIRED':
                    $this->message = t('Exceeded expiration date of the generated payment link.');
                    break;
                case 'UNKNOWN_CHANNEL':
                    $this->message = t('Selected payment channel is unknown.');
                    break;
                case 'DISABLED_CHANNEL':
                    $this->message = t('Selected channel payment is desabled.');
                    break;
                case 'BLOCKED_ACCOUNT':
                    $this->message = t('Account is disabled.');
                    break;
                case 'INACTIVE_SELLER':
                    $this->message = t('Seller account is inactive.');
                    break;
                case 'AMOUNT_TOO_LOW':
                    $this->message = t('Amount is too low.');
                    break;
                case 'AMOUNT_TOO_HIGH':
                    $this->message = t('Amount is too high.');
                    break;
                case 'BAD_DATA_FORMAT':
                    $this->message = t('Data format is bad.');
                    break;
                case 'HASH_NOT_EQUAL_CHK':
                    $this->message = t('Request has been modified during transmission.');
                    break;
                case 'REQUIRED_PARAMS_NOT_FOUND':
                    $this->message = t('There were not given all request parameters.');
                    break;
                default:
                    $this->message = t('There was an unidentified error. Please contact to your seller and give him the order number.');
            }
        } else if(Dotpay::getParam('status')=='FAIL') {
            $this->message = t('Payment has failed. Please repeat it. If an error will be again, please contact to dealer.');
        }
    }
    
    /**
     * Returns rendered template with HTML code of back page
     * @return string
     */
    public function render() {
        ob_start();
        if(file_exists($this->template))
            include($this->template);
        return ob_get_clean();
    }
    
    /**
     * Returns url to page with checking the status of payment
     * @return string
     */
    private function getStatusUrl() {
        return Dotpay::getInstance()->getFullUrl('/dotpay/'.$this->order->order_id.'/status');
    }
    
    /**
     * Returns url to page with summary of order
     * @return string
     */
    private function getOrderSummaryUrl() {
        return url('checkout/' . $this->order->order_id . '/payment/return/' . $this->order->data['payment_redirect_key'], array('absolute' => TRUE));
    }
}
