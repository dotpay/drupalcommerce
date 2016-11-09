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
use Dotpay\Channel\CC;
use Dotpay\SellerApi;
use Dotpay\Card;
use Dotpay\CardBrand;

/**
 * Provides functionality to support confirming payment by URLC request from Dotpay
 */
class Confirm {
    const DOTPAY_IP = '195.150.9.37';
    const OFFICE_IP = '77.79.195.34';
    
    private $message = 'OK';
    
    private $order = null;
    
    private $wrapper = null;
    
    /**
     * Checks different conditions and change payment status to success. Returns message with error or string "OK" to Dotpay server
     * @return string
     */
    public function make() {
        $this->checkIfMessageToOffice();
        ($this->checkIp() &&
         $this->checkMethod() &&
         $this->checkAmountAndCurrency() &&
         $this->checkId() &&
         $this->checkSignature() &&
         $this->changeStatus() && 
         $this->registerOcCard());
        return $this->message;
    }
    
    /**
     * Checks, if request come from Dotpay office and displays basic informations about plugin and seller account
     */
    private function checkIfMessageToOffice() {
        if($_SERVER['REMOTE_ADDR'] == self::OFFICE_IP && $_SERVER['REQUEST_METHOD'] == 'GET') {
            $commerceInfo = system_get_info('module', 'commerce');
            die("Drupal Commerce - M.Ver: ".DOTPAY_GATEWAY_VERSION.
                ", DrupalCommerce.Ver: ". $commerceInfo['version'] .
                ", Drupal.Ver: ". VERSION .
                ", ID: ".Dotpay::getInstance()->getSettings()->getId().
                ", Test: ".(bool)Dotpay::getInstance()->getSettings()->getTestMode()
            );
        }
    }
    
    /**
     * Checks, if request IP belongs to Dotpay
     * @return boolean
     */
    private function checkIp() {
        //if($_SERVER['REMOTE_ADDR'] == self::DOTPAY_IP)
            return true;
        $this->message = 'DrupalCommerce - ERROR (REMOTE ADDRESS: '.$_SERVER['REMOTE_ADDR'].')';
        return false;
    }
    
    /**
     * Checks, if request method is correct
     * @return boolean
     */
    private function checkMethod() {
        if($_SERVER['REQUEST_METHOD'] == 'POST')
            return true;
        $this->message = 'DrupalCommerce - ERROR (METHOD <> POST)';
        return false;
    }
    
    /**
     * Checks, if amount and currency from request are the same as these values in order
     * @return boolean
     */
    private function checkAmountAndCurrency() {
        $currency = $this->getOrderWrapper()->commerce_order_total->currency_code->value();
        $amount = Dotpay::calculateDecimalAmount($this->getOrderWrapper()->commerce_order_total->amount->value(), $currency);
        if($currency != Dotpay::getParam('operation_original_currency')) {
            $this->message = 'DrupalCommerce - NO MATCH OR WRONG CURRENCY - '.Dotpay::getParam('operation_original_currency').' <> '.$currency;
            return false;
        }
        if($amount == Dotpay::getParam('operation_original_amount'))
            return true;
        $this->message = 'DrupalCommerce - NO MATCH OR WRONG AMOUNT - '.Dotpay::getParam('operation_original_amount').' <> '.$amount;
        return false;
    }
    
    /**
     * Checks, if seller ID from request is the same as in plugin settings
     * @return boolean
     */
    private function checkId() {
        if($this->isSelectedPvChannel())
            $id = Dotpay::getInstance()->getSettings()->getPvId();
        else
            $id = Dotpay::getInstance()->getSettings()->getId();
        if(Dotpay::getParam('id')==$id)
            return true;
        $this->message = 'DrupalCommerce - ERROR ID';
        return false;
    }
    
    /**
     * Checks, if coming request is unchanged and not forget
     * @return boolean
     */
    private function checkSignature() {
        if($this->isSelectedPvChannel()) {
            $id = Dotpay::getInstance()->getSettings()->getPvId();
            $pin = Dotpay::getInstance()->getSettings()->getPvPin();
        } else {
            $id = Dotpay::getInstance()->getSettings()->getId();
            $pin = Dotpay::getInstance()->getSettings()->getPin();
        }
        $signature = $pin.$id.
        Dotpay::getParam('operation_number').
        Dotpay::getParam('operation_type').
        Dotpay::getParam('operation_status').
        Dotpay::getParam('operation_amount').
        Dotpay::getParam('operation_currency').
        Dotpay::getParam('operation_withdrawal_amount').
        Dotpay::getParam('operation_commission_amount').
        Dotpay::getParam('operation_original_amount').
        Dotpay::getParam('operation_original_currency').
        Dotpay::getParam('operation_datetime').
        Dotpay::getParam('operation_related_number').
        Dotpay::getParam('control').
        Dotpay::getParam('description').
        Dotpay::getParam('email').
        Dotpay::getParam('p_info').
        Dotpay::getParam('p_email').
        Dotpay::getParam('channel').
        Dotpay::getParam('channel_country').
        Dotpay::getParam('geoip_country');	

        if(Dotpay::getParam('signature') === hash('sha256', $signature))
            return true;
        $this->message = 'DrupalCommerce - ERROR SIGN';
        return false;
    }
    
    /**
     * Checks based on the request parameters, if payment channel is PV card channel
     * @return boolean
     */
    private function isSelectedPvChannel() {
        return (Dotpay::isSelectedCurrency(Dotpay::getInstance()->getSettings()->getPvCurrency(), Dotpay::getParam('operation_currency')) 
           && Dotpay::getParam('channel')==CC::pvChannel
           && Dotpay::getInstance()->getSettings()->getPvCardChannel()
           && Dotpay::getInstance()->getSettings()->getPvId()==Dotpay::getParam('id'));
    }
    
    /**
     * Returns Drupal Commerce order object for order id taken from request field: control
     * @return object
     */
    private function getOrder() {
        if($this->order === null)
            $this->order = commerce_order_load(Dotpay::getParam('control'));
        return $this->order;
    }
    
    /**
     * Returns Drupal Commerce order wrapper object for order id taken from request field: control
     * @return object
     */
    private function getOrderWrapper() {
        if($this->wrapper === null)
            $this->wrapper = entity_metadata_wrapper('commerce_order', Dotpay::getParam('control'));
        return $this->wrapper;
    }
    
    /**
     * Changes transaction status in depend on the sending parameters in confirm request
     * @return boolean
     */
    private function changeStatus() {
        $transactions = commerce_payment_transaction_load_multiple(array(), array('order_id' => Dotpay::getParam('control')));
        $transaction = array_pop($transactions);

        if(!$transaction){
                echo 'Transaction not found';
                return false;
        }
        
        $transaction->remote_id = Dotpay::getParam('operation_number');
        $transaction->remote_status = Dotpay::getParam('operation_status');
        $transaction->currency_code = Dotpay::getParam('operation_currency');
        
        switch(Dotpay::getParam('operation_status')){
            case 'rejected':
                $transaction->status = COMMERCE_PAYMENT_STATUS_FAILURE;
                $transaction->message = t("Rejected payment");
                break;
            case 'completed':
                $transaction->status = COMMERCE_PAYMENT_STATUS_SUCCESS;
                $transaction->message = t("Success payment");
                break;
            default:
                $transaction->status = COMMERCE_PAYMENT_STATUS_PENDING;
                $transaction->message = t("New payment");
                break;
        }
        commerce_payment_transaction_save($transaction);
        
        return true;
    }

    /**
     * Saves missing data to One Click card based on data taken from Dotpay server
     * @return boolean
     */
    private function registerOcCard() {
        $cc = Card::getCardFromOrder(Dotpay::getParam('control'));
        if($cc->cc_id !== NULL && $cc->card_id == NULL) {
            $sellerApi = new SellerApi(Dotpay::getInstance()->getSettings()->getSellerUrl());
            $ccInfo = $sellerApi->getCreditCardInfo(
                Dotpay::getInstance()->getSettings()->getApiUsername(),
                Dotpay::getInstance()->getSettings()->getApiPassword(),
                Dotpay::getParam('operation_number')
            );
            Card::updateCard($cc->cc_id, $ccInfo->id, $ccInfo->masked_number, $ccInfo->brand->name);
            CardBrand::updateBrand($ccInfo->brand->name, $ccInfo->brand->logo);
            return true;
        }
        return false;
    }
}
