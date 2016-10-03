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

/**
 * Supports settings of plugin and all Dotpay payment channels
 */
class Settings {
    private $settingsSource = array();
    
    /**
     * Loads configuration
     */
    public function __construct() {
        $conditions = array(
            'event' => 'commerce_payment_methods',
            'plugin' => 'reaction rule',
            'active' => TRUE,
            'owner' => 'rules',
        );
        $commerce_payment_methods = entity_load('rules_config', FALSE, $conditions);
        foreach($commerce_payment_methods as $method) {
            $payment = commerce_payment_method_instance_load('dotpay_standard_channel|'.$method->name);
            $this->update($payment['settings']);
        }
    }
    
    /**
     * Update settings in object based on settings source
     * @param array $settings Source of plugin settings
     * @return \Dotpay\Settings
     */
    public function update($settings) {
        if(is_array($settings)) {
            foreach ($settings as $key => $value)
                $this->settingsSource[$key] = $value;
        }
        return $this;
    }
    
    /**
     * Returns seller ID
     * @return int|null
     */
    public function getId() {
        return isset($this->settingsSource['id'])?$this->settingsSource['id']:null;
    }
    
    /**
     * Sets seller ID
     * @param int $id Seller ID
     * @return \Dotpay\Settings
     */
    public function setId($id) {
        $this->settingsSource['id'] = $id;
        return $this;
    }
    
    /**
     * Returns seller PIN
     * @return string|null
     */
    public function getPin() {
        return isset($this->settingsSource['pin'])?$this->settingsSource['pin']:null;
    }
    
    /**
     * Sets seller PIN
     * @param string $pin
     * @return \Dotpay\Settings
     */
    public function setPin($pin) {
        $this->settingsSource['pin'] = $pin;
        return $this;
    }
    
    /**
     * Returns a flag, if test mode is enabled
     * @return boolean|null
     */
    public function getTestMode() {
        return isset($this->settingsSource['testMode'])?(bool)$this->settingsSource['testMode']:null;
    }
    
    /**
     * Sets flag, if test mode is enabled
     * @param string $testMode Flag of test mode
     * @return \Dotpay\Settings
     */
    public function setTestMode($testMode) {
        $this->settingsSource['testMode'] = $testMode;
        return $this;
    }
    
    /**
     * Returns a flag, if widget on shop page is enabled
     * @return boolean|null
     */
    public function getWidget() {
        return isset($this->settingsSource['widget'])?(bool)$this->settingsSource['widget']:null;
    }
    
    /**
     * Sets a flag, if widget on shop page is enabled
     * @param string $widget Flag of widget on shop page
     * @return \Dotpay\Settings
     */
    public function setWidget($widget) {
        $this->settingsSource['widget'] = $widget;
        return $this;
    }
    
    /**
     * Returns a flag, if PV card channel is enabled
     * @return boolean|null
     */
    public function getPvCardChannel() {
        return isset($this->settingsSource['pvCardChannel'])?(bool)$this->settingsSource['pvCardChannel']:null;
    }
    
    /**
     * Sets a flag, if PV card channel is enabled
     * @param type $pvCardChannel Flag of PV card channel
     * @return \Dotpay\Settings
     */
    public function setPvCardChannel($pvCardChannel) {
        $this->settingsSource['pvCardChannel'] = $pvCardChannel;
        return $this;
    }
    
    /**
     * Returns seller ID for separated card channel for specific currencies
     * @return int
     */
    public function getPvId() {
        return isset($this->settingsSource['pvId'])?$this->settingsSource['pvId']:null;
    }
    
    /**
     * Sets seller ID for separated card channel for specific currencies
     * @param type $pvId Seller ID for PV card channel
     * @return \Dotpay\Settings
     */
    public function setPvId($pvId) {
        $this->settingsSource['pvId'] = $pvId;
        return $this;
    }
    
    /**
     * Returns seller PIN for separated card channel for specific currencies
     * @return string
     */
    public function getPvPin() {
        return isset($this->settingsSource['pvPin'])?$this->settingsSource['pvPin']:null;
    }
    
    /**
     * Sets seller PIN for separated card channel for specific currencies
     * @param string $pvPin Seller PIN for PV card channel
     * @return \Dotpay\Settings
     */
    public function setPvPin($pvPin) {
        $this->settingsSource['pvPin'] = $pvPin;
        return $this;
    }
    
    /**
     * Returns currency codes for separated card channel for specific currencies
     * @return string
     */
    public function getPvCurrency() {
        return isset($this->settingsSource['pvCurrency'])?$this->settingsSource['pvCurrency']:null;
    }
    
    /**
     * Sets currency codes for separated card channel for specific currencies
     * @param string $pvCurrency List of currency codes for PV card channel
     * @return \Dotpay\Settings
     */
    public function setPvCurrency($pvCurrency) {
        $this->settingsSource['pvCurrency'] = $pvCurrency;
        return $this;
    }
    
    /**
     * Returns a flag, if PV channel is current active
     * @return boolean
     */
    public function isPvActive() {
        $currency = $this->getCurrencyForPv();
        return (Dotpay::isSelectedCurrency($this->getPvCurrency(), $currency) && (bool)$this->getPvCardChannel());
    }
    
    /**
     * Returns a username for seller panel
     * @return string
     */
    public function getApiUsername() {
        return isset($this->settingsSource['apiUsername'])?$this->settingsSource['apiUsername']:null;
    }
    
    /**
     * Sets a username for seller panel
     * @param string $apiUsername Username for seller panel
     * @return \Dotpay\Settings
     */
    public function setApiUsername($apiUsername) {
        $this->settingsSource['apiUsername'] = $apiUsername;
        return $this;
    }
    
    /**
     * Returns password for seller panel
     * @return string
     */
    public function getApiPassword() {
        return isset($this->settingsSource['apiPassword'])?$this->settingsSource['apiPassword']:null;
    }
    
    /**
     * Sets a password for seller panel
     * @param type $apiPassword Password for seller panel
     * @return \Dotpay\Settings
     */
    public function setApiPassword($apiPassword) {
        $this->settingsSource['apiPassword'] = $apiPassword;
        return $this;
    }
    
    /**
     * Returns a data for Drupal API Forms with settings of Dotpay plugin
     * @return array
     */
    public function getForm() {
        return array(
            'id' => array(
                '#prefix' => '<div class="dotformmain"><h4>'.t('Main settings (required)').'</h4><p>&nbsp;</p>',
                '#type' => 'textfield',
                '#title' => t('ID'),
                '#description' => t('Your seller 6 digits ID (The same as in Dotpay user panel)').'<br>'.t('Please contact the Customer Care to get new account if your ID has less than 6 digits').':&nbsp;<a href="'.t('https://ssl.dotpay.pl/en/customer_care').'" target="_blank"><b>'.t('Contact').'</b></a></span><br>',
                '#default_value' => $this->getId(),
                '#size' => 6,
                '#maxlength' => 6,
                '#required' => TRUE,
            ),
            'pin' => array(
                '#type' => 'textfield',
                '#title' => t('PIN'),
                '#description' => t('Your seller PIN (The same as in Dotpay user panel)').'<br>',
                '#default_value' => $this->getPin(),
                '#size' => 32,
                '#maxlength' => 32,
                '#required' => TRUE,
            ),
            'testMode' => array(
                '#type' => 'select',
                '#title' => t('Test Mode'),
                '#description' => t('I\'m using Dotpay test account (test ID) to payment simulations').'<br>'.t('Required Dotpay test account:').' <a href="https://ssl.dotpay.pl/test_seller/test/registration/?affilate_id=drupalcommerce" target="_blank" title="'.t('Dotpay test account registration').'"><b>'.t('registration').'</b></a><br><br><hr>',
                '#options' => array(
                    1 => t('Enable'),
                    0 => t('Disable')
                ),
                '#default_value' => (int)$this->getTestMode(),
                '#required' => TRUE,
            ),
            'widget' => array(
                '#suffix' => '</div><br>', 
                '#type' => 'select',
                '#title' => t('Dotpay widget enabled'),
                '#description' => t('Display payment channels in a shop'),
                '#options' => array(
                    1 => t('Enable'),
                    0 => t('Disable')
                ),
                '#default_value' => (int)$this->getWidget(),
            ),

            'apiUsername' => array(
                '#prefix' => '<h4>'.t('Additional settings:').'</h4><div class="dotformoptional"><h4>Dotpay API</h4><p>'.t('Required for proper operation One Click and display instructions for Transfer channels (wire transfer data are not passed to the bank and a payer needs to copy or write the data manually)').'</p>',
                '#type' => 'textfield',
                '#title' => t('Dotpay API username'),
                '#description' => t('Your username for Dotpay seller panel'),
                '#default_value' => $this->getApiUsername(),
                '#size' => 32,
                '#required' => FALSE,
            ),
            'apiPassword' => array(
                '#suffix' => '</div><br>', 
                '#type' => 'password',
                '#title' => t('Dotpay API password'),
                '#description' => t('Your password for Dotpay seller panel'),
                '#size' => 32,
                '#required' => FALSE,
                '#attributes' => array(
                    'value' => $this->getApiPassword()
                )
            ),
			
            'pvCardChannel' => array(
                '#prefix' => '<div class="dotformoptional"><h4>'.t('Separate ID for foreign currencies').'</h4><p>&nbsp;</p>',
                '#type' => 'select',
                '#title' => t('I have separate ID for foreign currencies'),
                '#description' => t('You can enable separate ID for foreign currencies'),
                '#options' => array(
                    1 => t('Enable'),
                    0 => t('Disable')
                ),
                '#default_value' => (int)$this->getPvCardChannel(),
            ),
            'pvId' => array(
                '#type' => 'textfield',
                '#title' => t('ID for foreign currencies account'),
                '#description' => t('Seller ID for separated card channel (copy from Dotpay user panel)'),
                '#default_value' => $this->getPvId(),
                '#size' => 6,
                '#maxlength' => 6,
                '#required' => FALSE,
            ),
            'pvPin' => array(
                '#type' => 'textfield',
                '#title' => t('PIN for foreign currencies account'),
                '#description' => t('Seller PIN for separated card channel (copy from Dotpay user panel)'),
                '#default_value' => $this->getPvPin(),
                '#size' => 32,
                '#maxlength' => 32,
                '#required' => FALSE,
            ),
            'pvCurrency' => array(
                '#suffix' => '</div><br>', 
                '#type' => 'textfield',
                '#title' => t('Currencies for separate ID'),
                '#description' => t('Please enter currency codes separated by commas, for example: EUR,USD'),
                '#default_value' => $this->getPvCurrency(),
                '#size' => 16,
                '#maxlength' => 32,
                '#required' => FALSE,
            ),
			
			
        );
    }
    
    /**
     * Sets default values on configuration stored in this object
     * @return \Dotpay\Settings
     */
    public function setDefault() {
        $this->setId('')
             ->setPin('')
             ->setTestMode(0)
             ->setWidget(1)
             ->setPvCardChannel(0)
             ->setPvId('')
             ->setPvPin('')
             ->setPvCurrency('')
             ->setApiUsername('')
             ->setApiPassword('');
        return $this;
    }
    
    /**
     * Returns url to payment place on Dotpay server
     * @return string
     */
    public function getPaymentUrl() {
        $host = 'https://ssl.dotpay.pl/';
        if($this->getTestMode()=='1') {
            return $host.'test_payment/';
        } else {
            return $host.'t2/';
        }
    }
    
    /**
     * Returns url to seller api on Dotpay server
     * @return string
     */
    public function getSellerUrl() {
        $host = 'https://ssl.dotpay.pl/';
        if($this->getTestMode()=='1') {
            return $host.'test_seller/';
        } else {
            return $host.'s2/login/';
        }
    }
    
    /**
     * Returns current currency loaded from storage in time, when this currency isn't available in standard way
     * @global object $user Drupal user object
     * @return boolean
     */
    private function getCurrencyForPv() {
        global $user;
        $order = commerce_cart_order_load($user->uid);
        if($order==false) {
            if(isset($_SESSION['dotpay_commerce_order_currency_pv']))
                return $_SESSION['dotpay_commerce_order_currency_pv'];
            else
                return false;
        }
        $wrapper = entity_metadata_wrapper('commerce_order', $order);
        $currency = $wrapper->commerce_order_total->currency_code->value();
        $_SESSION['dotpay_commerce_order_currency_pv'] = $currency;
        return $currency;
    }
}
