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
 * Description of Dotpay
 *
 * @author tomasz
 */
class Dotpay {
    /**
     *
     * @var Dotpay 
     */
    private static $instance = null;
    
    /**
     *
     * @var Settings 
     */
    private $settings = null;
    
    /**
     *
     * @var Checkoutpage 
     */
    private $channels = array();
    
    /**
     * Returns Dotpay object
     * @return Dotpay
     */
    public static function getInstance() {
        if(self::$instance == NULL) {
            self::$instance = new Dotpay();
        }
        return self::$instance;
    }
    
    public function getChannelList() {
        $standard = new Channel\Standard();
        $blik = new Channel\Blik();
        $mp = new Channel\MasterPass();
        $oc = new Channel\OneClick();
        $cc = $this->getCC();
        return array_merge($oc->getInfo(), $cc->getInfo(), $blik->getInfo(), $mp->getInfo(), $standard->getInfo());
    }
    
    /**
     * Returns Settings object
     * @return Settings
     */
    public function getSettings() {
        if($this->settings === null) {
            $this->settings = new Settings();
        }
        return $this->settings;
    }
    
    /**
     * Returns OneClick channel object
     * @return OneClick
     */
    public function getOneClick() {
        if(empty($this->channels['oneclick']))
            $this->channels['oneclick'] = new Channel\OneClick();
        return $this->channels['oneclick'];
    }
    
    /**
     * Returns CC channel object
     * @return CC
     */
    public function getCC() {
        if(empty($this->channels['cc']))
            $this->channels['cc'] = new Channel\CC();
        return $this->channels['cc'];
    }
    
    /**
     * Returns Blik channel object
     * @return Blik
     */
    public function getBlik() {
        if(empty($this->channels['blik']))
            $this->channels['blik'] = new Channel\Blik();
        return $this->channels['blik'];
    }
    
    /**
     * Returns MasterPass channel object
     * @return MasterPass
     */
    public function getMasterPass() {
        if(empty($this->channels['mp']))
            $this->channels['mp'] = new Channel\MasterPass();
        return $this->channels['mp'];
    }
    
    /**
     * Returns Standard channel object
     * @return Standard
     */
    public function getStandard() {
        if(empty($this->channels['standard']))
            $this->channels['standard'] = new Channel\Standard();
        return $this->channels['standard'];
    }
    
    /**
     * Returns full url address for the given params
     * @param string $params Url params
     * @return string
     */
    public function getFullUrl($params) {
        $url = 'http';
        if(!empty($_SERVER['HTTPS']))
            $url.='s';
        $url.='://'.$_SERVER["SERVER_NAME"];
        if($_SERVER["SERVER_PORT"] != 80)
            $url.=':'.$_SERVER["SERVER_PORT"];
        return $url.url($params);
    }
    
    /**
     * Return param, which was sending to page by GET or POST method
     * @param string $name name of param
     * @param mixed $default default value
     * @return boolean
     */
    public static function getParam($name, $default = false) {
        if (!isset($name) || empty($name) || !is_string($name)) {
            return false;
        }
        $ret = (isset($_POST[$name]) ? $_POST[$name] : (isset($_GET[$name]) ? $_GET[$name] : $default));
        if (is_string($ret)) {
            return addslashes($ret);
        }
        return $ret;
    }
    
    /**
     * Returns rendered template file, which name is given as a parameter, with given variables
     * @param string $filename Filename of template
     * @param array $vars Variables forwarded to template
     * @return string
     */
    public static function render($filename, $vars = array()){
        ob_start();
        if(file_exists($filename))
            include($filename);
        return ob_get_clean();
    }
    
    /**
     * Returns correct data for street and street_n1 fields, if street name and street number are in one field
     * @param object $wrapper Drupal Commerce order wrapper object
     * @return array
     */
    public static function getStreetAndStreetN1($wrapper) {
        $street = $wrapper->commerce_customer_billing->commerce_customer_address->thoroughfare->value();
        $street_n1 = $wrapper->commerce_customer_billing->commerce_customer_address->premise->value();
        
        if(empty($street_n1))
        {
            preg_match("/\s[\w\d\/_\-]{0,30}$/", $street, $matches);
            if(count($matches)>0)
            {
                $street_n1 = trim($matches[0]);
                $street = str_replace($matches[0], '', $street);
            }
        }
        
        return array(
            'street' => $street,
            'street_n1' => $street_n1
        );
    }
    
    /**
     * Returns correct postcode, if it not contain '-' char for polish postcode
     * @param object $wrapper Drupal Commerce order wrapper object
     * @return string
     */
    public static function getCorrectPostcode($wrapper) {
        $country = $wrapper->commerce_customer_billing->commerce_customer_address->country->value();
        $postcode = $wrapper->commerce_customer_billing->commerce_customer_address->postal_code->value();
        if(strpos('-', $postcode) === false && $country == 'pl') {
            $part1 = substr($postcode, 0, 2);
            $part2 = substr($postcode, 2, 3);
            $postcode = $part1.'-'.$part2;
        }
        return $postcode;
    }
    
    /**
     * Checks, if the given currency code is in the given list of currency codes
     * @param string $allowCurrencyForm List of currency literal codes
     * @param string $paymentCurrency Literal code of payment currency
     * @return boolean
     */
    public static function isSelectedCurrency($allowCurrencyForm, $paymentCurrency) {
        $result = false;
        
        $allowCurrency = str_replace(';', ',', $allowCurrencyForm);
        $allowCurrency = strtoupper(str_replace(' ', '', $allowCurrency));
        $allowCurrencyArray =  explode(",",trim($allowCurrency));
        
        if(in_array(strtoupper($paymentCurrency), $allowCurrencyArray)) {
            $result = true;
        }
        
        return $result;
    }
    
    /**
     * Returns correct amount, calculated based on given amount and currency code
     * @param float $amount Amount
     * @param string $currency_code Currency code
     * @return float
     */
    public static function calculateDecimalAmount($amount, $currency_code) {
        $rounded_amount = commerce_currency_round($amount, commerce_currency_load($currency_code));
        return number_format(commerce_currency_amount_to_decimal($rounded_amount, $currency_code), 2, '.', '');
    }
    
    /**
     * Check, if channel is in channels groups
     * @param int $channelId channel id
     * @param array $group names of channel groups
     * @return boolean
     */
    public static function isChannelInGroup($orderId, $channelId, array $groups) {
        $resultJson = self::getDotpayChannels($orderId);
        if(false !== $resultJson) {
            $result = json_decode($resultJson, true);
            if (isset($result['channels']) && is_array($result['channels'])) {
                foreach ($result['channels'] as $channel) {
                    if (isset($channel['group']) && $channel['id']==$channelId && in_array($channel['group'], $groups)) {
                        return true;
                    }
                }
            }
        }
        return false;
    }
    
    /**
     * Return Dotpay channels, which are availaible for the given amount as a parameter
     * @param int $orderId order id
     * @return string|boolean
     */
    public static function getDotpayChannels($orderId) {
        $wrapper = entity_metadata_wrapper('commerce_order', $orderId);
        $currency = $wrapper->commerce_order_total->currency_code->value();
        $amount = Dotpay::calculateDecimalAmount($wrapper->commerce_order_total->amount->value(), $currency);
        
        $dotpay_url = Dotpay::getInstance()->getSettings()->getPaymentUrl();
        $dotpay_id = Dotpay::getInstance()->getSettings()->getId();
        $dotpay_lang = self::getCorrectLanguage();
        
        $curlUrl = "{$dotpay_url}payment_api/channels/";
        $curlUrl .= "?currency={$currency}";
        $curlUrl .= "&id={$dotpay_id}";
        $curlUrl .= "&amount={$amount}";
        $curlUrl .= "&lang={$dotpay_lang}";
        /**
        * curl
        */
        try {
            $curl = new Curl();
            $curl->addOption(CURLOPT_SSL_VERIFYPEER, false)
                 ->addOption(CURLOPT_HEADER, false)
                 ->addOption(CURLOPT_URL, $curlUrl)
                 ->addOption(CURLOPT_REFERER, $curlUrl)
                 ->addOption(CURLOPT_RETURNTRANSFER, true);
            $resultJson = $curl->exec();
        } catch (Exception $exc) {
             $resultJson = false;
        }
        
        if($curl) {
            $curl->close();
        }
        
        return $resultJson;
    }
    
    /**
     * Return Dotpay agreement for the given amount and type
     * @param int $orderId order id
     * @param string $what type of agreements
     * @return string
     */
    public static function getDotpayAgreement($orderId, $what) {
        $resultStr = '';
        
        $resultJson = self::getDotpayChannels($orderId);
        
        if(false !== $resultJson) {
            $result = json_decode($resultJson, true);

            if (isset($result['forms']) && is_array($result['forms'])) {
                foreach ($result['forms'] as $forms) {
                    if (isset($forms['fields']) && is_array($forms['fields'])) {
                        foreach ($forms['fields'] as $forms1) {
                            if ($forms1['name'] == $what) {
                                $resultStr = $forms1['description_html'];
                            }
                        }
                    }
                }
            }
        }

        return $resultStr;
    }
    
    /**
     * 
     * @global object $language Drupal language object
     * @return string
     */
    public static function getCorrectLanguage() {
        global $language;

	if (!in_array($language->language, self::getAllowedLanguages())){
            return 'en';
	}
	return $language->language;
    }
    
    /**
     * Returns list of languages, accepted by Dotpay
     * @return array
     */
    private static function getAllowedLanguages() {
        return array(
            'pl','en','de','it','fr','es','cz','ru','bg'
        );
    }
    
    /**
     * Returns channel data, if payment channel is active for order data
     * @param type $orderId order id
     * @param type $channelId channel id
     * @return array|false
     */
    public static function getChannelData($orderId, $channelId) {
        $resultJson = self::getDotpayChannels($orderId);
        if(false !== $resultJson) {
            $result = json_decode($resultJson, true);

            if (isset($result['channels']) && is_array($result['channels'])) {
                foreach ($result['channels'] as $channel) {
                    if (isset($channel['id']) && $channel['id']==$channelId) {
                        return $channel;
                    }
                }
            }
        }
        return false;
    }
    
    /**
     * Adds script with validate tools for visible form to page
     */
    public static function addValidateScript() {
        $script = 'window.validateMessages = {
                        "requiredFields": "'.t('You didn\'t fill all required fields.').'",
                        "badBlikCode": "'.t('Your BLIK code is incorrect.').'",
                        "emptyChannel": "'.t('Select a payment channel.').'",
                        "badPhoneNumber": "'.t('Your phone number is incorrect.').'"
                    };';    
        $element = array(
            '#type' => 'markup',
            '#markup' => '<script>'.$script.'</script>',
        );
        drupal_add_html_head($element, 'jquery-tmpl');
        drupal_add_js(drupal_get_path('module', 'commerce_dotpay') . '/web/js/validate.js');
    }

    /**
     * Disabled creating objects of this class
     */
    private function __construct(){
        $this->getSettings();
    }
    
    /**
     * Disabled cloning objects of this class
     */
    private function __clone(){}
}
