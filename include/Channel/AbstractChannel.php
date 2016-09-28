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

namespace Dotpay\Channel;

use Dotpay\Dotpay;
use Dotpay\Curl;

/**
 * Abstract Dotpay payment channel
 */
abstract class AbstractChannel {
    const name = '';
    
    const cashGroup = 'cash';
    const transferGroup = 'transfers';
    
    /**
     * Returns info about payment channel
     */
    abstract public function getInfo();
    
    /**
     * Returns payment form with visible detail fields for Drupal API Forms
     * @param array $form Payment form
     * @param array $form_state Form state
     * @param object $order Order object
     * @param array $payment_method Payment method info
     */
    abstract public function getFrontForm($form, &$form_state, $order, $payment_method);
    
    /**
     * Returns url to channel thumb image
     */
    abstract public function getThumb();
    
    /**
     * Returns all fields for hidden form, with CHK field
     * @param int $orderId Order id
     * @return array
     */
    public function getFieldsWithChk($orderId) {
        $id = Dotpay::getInstance()->getSettings()->getId();
        $pin = Dotpay::getInstance()->getSettings()->getPin();
        $fields = $this->getPaymentFields($orderId);
        $fields['chk'] = $this->generateCHK($id, $pin, $fields);
        return $fields;
    }
    
    /**
     * Returns fields for hidden form with standard values but without CHK field
     * @param int $orderId Order id
     * @return array
     */
    protected function getPaymentFields($orderId) {
        $wrapper = entity_metadata_wrapper('commerce_order', $orderId);
        $order = commerce_order_load($orderId);
        $amount = $wrapper->commerce_order_total->amount->value();
        $currency = $wrapper->commerce_order_total->currency_code->value();
        $data = array(
            'id' => Dotpay::getInstance()->getSettings()->getId(),
            'amount' => Dotpay::calculateDecimalAmount($amount, $currency),
            'currency' => $currency,
            'control' => $order->order_id,
            'description'  => t('Order @order_number', array('@order_number' => $order->order_number)),
            'lang' =>  Dotpay::getCorrectLanguage(),
            'URL' => Dotpay::getInstance()->getFullUrl('/dotpay/'.$order->order_id.'/back'),
            'URLC' => Dotpay::getInstance()->getFullUrl('/dotpay/confirm'),
            'type' => 0,
            'ch_lock' => 0,
            'api_version' => 'dev',
            'email'	=> $order->mail,
            'bylaw' => 1,
            'personal_data' => 1,
            'p_info' =>  variable_get('site_name', url('<front>', array('absolute' => TRUE)))
	);
        if(isset($wrapper->commerce_customer_billing)
            && isset($wrapper->commerce_customer_billing->commerce_customer_address)) {
            $streetData = Dotpay::getStreetAndStreetN1($wrapper);
            $data += array(
                'firstname' => $wrapper->commerce_customer_billing->commerce_customer_address->first_name->value(),
                'lastname' => $wrapper->commerce_customer_billing->commerce_customer_address->last_name->value(),
                'street' => $streetData['street'],
                'street_n1' => $streetData['street_n1'],
                'city'	=> $wrapper->commerce_customer_billing->commerce_customer_address->locality->value(),
                'postcode' => Dotpay::getCorrectPostcode($wrapper),
                'country' => $wrapper->commerce_customer_billing->commerce_customer_address->country->value(),
            );
        }
        return $data;
    }
    
    /**
     * Returns url to thumb image for a given filename
     * @param type $filename
     * @return 
     */
    protected function getUniversalThumb($filename) {
        $iconParameters = array(
		'path' => drupal_get_path('module', 'commerce_dotpay') . '/web/images/icons/'.$filename,
		'title' => 'dotpay icon',
		'alt' => 'dotpay icon',
		'attributes' => array(
                    'class' => 'dotpay-channel-icon'
		),
	);
	return theme('image', $iconParameters);
    }
    
    /**
     * Returns visible payment form with added agreement fields
     * @param array $form Original form
     * @param int $orderId Order id
     * @return array
     */
    protected function addAgreementToForm($form, $orderId) {
        $form['bylaw'] = array(
            '#type' => 'checkbox',
            '#title' => Dotpay::getDotpayAgreement($orderId, 'bylaw'),
            '#required' => 1,
            '#default_value' => 1,
            '#attributes' => array(
                'checked' => 'checked',
                'required' => 'required'
            )
        );
        $form['personal_data'] = array(
            '#type' => 'checkbox',
            '#title' => Dotpay::getDotpayAgreement($orderId, 'personal_data'),
            '#required' => 1,
            '#default_value' => 1,
            '#attributes' => array(
                'checked' => 'checked',
                'required' => 'required'
            )
        );
        return $form;
    }
    
    /**
     * Sets the variable with specified name and value
     * @param mixed $name Name of variable
     * @param mixed $value value of variable
     */
    protected function setValue($name, $value) {
        $_SESSION['commerce_dotpay_'.$name] = $value;
    }
    protected function getValue($name, $remove = true) {
        if(!isset($_SESSION['commerce_dotpay_'.$name]))
            return NULL;
        $value = $_SESSION['commerce_dotpay_'.$name];
        if($remove)
            unset($_SESSION['commerce_dotpay_'.$name]);
        return $value;
    }
    
    /**
     * Returns currency codes accepted by Dotpay
     * @return array
     */
    private function getAllowedCurrencies() {
        return array(
            "EUR", "USD", "GBP", "JPY","CZK", "SEK", "PLN"
        );
    }
    
    /**
     * Creates a transaction for order and sets status pending
     * @param int $order Order id
     * @param array $payment_method Payment method info
     * @return type
     */
    public function createTransaction($order,$payment_method) {
        $wrapper = entity_metadata_wrapper('commerce_order', $order);
	$amount = $wrapper->commerce_order_total->amount->value();
	$currency = $wrapper->commerce_order_total->currency_code->value();
        
        $transactions = commerce_payment_transaction_load_multiple(array(), array('instance_id' => $payment_method['instance_id'], 'status' => COMMERCE_PAYMENT_STATUS_PENDING, 'order_id' => $order->order_id));
	$transaction = array_pop($transactions);
	if(!$transaction){
            $transaction = commerce_payment_transaction_new($payment_method['method_id'], $order->order_id);
            $transaction->instance_id = $payment_method['instance_id'];
            $transaction->status = COMMERCE_PAYMENT_STATUS_PENDING;
            $transaction->amount = $amount;
            $transaction->currency_code = $currency;
            commerce_payment_transaction_save($transaction);
	}

	return $transaction->transaction_id;
    }

    /**
     * Returns CHK param generated for a given data
     * @param int $DotpayId Dotpay seller ID
     * @param string $DotpayPin Dotpay seller PIN
     * @param array $ParametersArray parameters of order
     * @return string
     */
    protected function generateCHK($DotpayId, $DotpayPin, $ParametersArray) {
        $ParametersArray['id'] = $DotpayId;
        $ChkParametersChain =
        $DotpayPin.
        (isset($ParametersArray['api_version']) ?
        $ParametersArray['api_version'] : null).
        (isset($ParametersArray['charset']) ?
        $ParametersArray['charset'] : null).
        (isset($ParametersArray['lang']) ?
        $ParametersArray['lang'] : null).
        (isset($ParametersArray['id']) ?
        $ParametersArray['id'] : null).
        (isset($ParametersArray['amount']) ?
        $ParametersArray['amount'] : null).
        (isset($ParametersArray['currency']) ?
        $ParametersArray['currency'] : null).
        (isset($ParametersArray['description']) ?
        $ParametersArray['description'] : null).
        (isset($ParametersArray['control']) ?
        $ParametersArray['control'] : null).
        (isset($ParametersArray['channel']) ?
        $ParametersArray['channel'] : null).
        (isset($ParametersArray['credit_card_brand']) ?
        $ParametersArray['credit_card_brand'] : null).
        (isset($ParametersArray['ch_lock']) ?
        $ParametersArray['ch_lock'] : null).
        (isset($ParametersArray['channel_groups']) ?
        $ParametersArray['channel_groups'] : null).
        (isset($ParametersArray['onlinetransfer']) ?
        $ParametersArray['onlinetransfer'] : null).
        (isset($ParametersArray['URL']) ?
        $ParametersArray['URL'] : null).
        (isset($ParametersArray['type']) ?
        $ParametersArray['type'] : null).
        (isset($ParametersArray['buttontext']) ?
        $ParametersArray['buttontext'] : null).
        (isset($ParametersArray['URLC']) ?
        $ParametersArray['URLC'] : null).
        (isset($ParametersArray['firstname']) ?
        $ParametersArray['firstname'] : null).
        (isset($ParametersArray['lastname']) ?
        $ParametersArray['lastname'] : null).
        (isset($ParametersArray['email']) ?
        $ParametersArray['email'] : null).
        (isset($ParametersArray['street']) ?
        $ParametersArray['street'] : null).
        (isset($ParametersArray['street_n1']) ?
        $ParametersArray['street_n1'] : null).
        (isset($ParametersArray['street_n2']) ?
        $ParametersArray['street_n2'] : null).
        (isset($ParametersArray['state']) ?
        $ParametersArray['state'] : null).
        (isset($ParametersArray['addr3']) ?
        $ParametersArray['addr3'] : null).
        (isset($ParametersArray['city']) ?
        $ParametersArray['city'] : null).
        (isset($ParametersArray['postcode']) ?
        $ParametersArray['postcode'] : null).
        (isset($ParametersArray['phone']) ?
        $ParametersArray['phone'] : null).
        (isset($ParametersArray['country']) ?
        $ParametersArray['country'] : null).
        (isset($ParametersArray['code']) ?
        $ParametersArray['code'] : null).
        (isset($ParametersArray['p_info']) ?
        $ParametersArray['p_info'] : null).
        (isset($ParametersArray['p_email']) ?
        $ParametersArray['p_email'] : null).
        (isset($ParametersArray['n_email']) ?
        $ParametersArray['n_email'] : null).
        (isset($ParametersArray['expiration_date']) ?
        $ParametersArray['expiration_date'] : null).
        (isset($ParametersArray['recipient_account_number']) ?
        $ParametersArray['recipient_account_number'] : null).
        (isset($ParametersArray['recipient_company']) ?
        $ParametersArray['recipient_company'] : null).
        (isset($ParametersArray['recipient_first_name']) ?
        $ParametersArray['recipient_first_name'] : null).
        (isset($ParametersArray['recipient_last_name']) ?
        $ParametersArray['recipient_last_name'] : null).
        (isset($ParametersArray['recipient_address_street']) ?
        $ParametersArray['recipient_address_street'] : null).
        (isset($ParametersArray['recipient_address_building']) ?
        $ParametersArray['recipient_address_building'] : null).
        (isset($ParametersArray['recipient_address_apartment']) ?
        $ParametersArray['recipient_address_apartment'] : null).
        (isset($ParametersArray['recipient_address_postcode']) ?
        $ParametersArray['recipient_address_postcode'] : null).
        (isset($ParametersArray['recipient_address_city']) ?
        $ParametersArray['recipient_address_city'] : null).
        (isset($ParametersArray['warranty']) ?
        $ParametersArray['warranty'] : null).
        (isset($ParametersArray['bylaw']) ?
        $ParametersArray['bylaw'] : null).
        (isset($ParametersArray['personal_data']) ?
        $ParametersArray['personal_data'] : null).
        (isset($ParametersArray['credit_card_number']) ?
        $ParametersArray['credit_card_number'] : null).
        (isset($ParametersArray['credit_card_expiration_date_year']) ?
        $ParametersArray['credit_card_expiration_date_year'] : null).
        (isset($ParametersArray['credit_card_expiration_date_month']) ?
        $ParametersArray['credit_card_expiration_date_month'] : null).
        (isset($ParametersArray['credit_card_security_code']) ?
        $ParametersArray['credit_card_security_code'] : null).
        (isset($ParametersArray['credit_card_store']) ?
        $ParametersArray['credit_card_store'] : null).
        (isset($ParametersArray['credit_card_store_security_code']) ?
        $ParametersArray['credit_card_store_security_code'] : null).
        (isset($ParametersArray['credit_card_customer_id']) ?
        $ParametersArray['credit_card_customer_id'] : null).
        (isset($ParametersArray['credit_card_id']) ?
        $ParametersArray['credit_card_id'] : null).
        (isset($ParametersArray['blik_code']) ?
        $ParametersArray['blik_code'] : null).
        (isset($ParametersArray['credit_card_registration']) ?
        $ParametersArray['credit_card_registration'] : null).
        (isset($ParametersArray['recurring_frequency']) ?
        $ParametersArray['recurring_frequency'] : null).
        (isset($ParametersArray['recurring_interval']) ?
        $ParametersArray['recurring_interval'] : null).
        (isset($ParametersArray['recurring_start']) ?
        $ParametersArray['recurring_start'] : null).
        (isset($ParametersArray['recurring_count']) ?
        $ParametersArray['recurring_count'] : null);
        return hash('sha256',$ChkParametersChain);
    }
}
