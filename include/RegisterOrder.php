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
use Dotpay\Curl;
use Dotpay\SellerApi;

/**
 * Toolkit for processing payments by register order method
 */
abstract class RegisterOrder {
    /**
     *
     * @var DotpayController Controller object
     */
    public static $payment;
    
    /**
     *
     * @var string Target url for Register Order
     */
    private static $target = "payment_api/v1/register_order/";
    
    /**
     * Creates register order, if it not exist
     * @param int $channelId Channel identifier
     * @param mixed $orderId Order id
     * @return null|array
     */
    public static function create($orderId, $channelId) {
        $data = str_replace('\\/', '/', json_encode(self::prepareData($orderId, $channelId)));
        if(!self::checkIfCompletedControlExist($orderId, $channelId)) {
            return self::createRequest($data);
        }
        return NULL;
    }
    
    /**
     * Creates request without checking conditions
     * @param array $data
     * @return boolean
     */
    private static function createRequest($data) {
        try {
            $curl = new Curl();
            $curl->addOption(CURLOPT_URL, Dotpay::getInstance()->getSettings()->getPaymentUrl().self::$target)
                 ->addOption(CURLOPT_SSL_VERIFYPEER, TRUE)
                 ->addOption(CURLOPT_SSL_VERIFYHOST, 2)
                 ->addOption(CURLOPT_RETURNTRANSFER, 1)
                 ->addOption(CURLOPT_TIMEOUT, 100)
                 ->addOption(CURLOPT_USERPWD, Dotpay::getInstance()->getSettings()->getApiUsername().':'.Dotpay::getInstance()->getSettings()->getApiPassword())
                 ->addOption(CURLOPT_POST, 1)
                 ->addOption(CURLOPT_POSTFIELDS, $data)
                 ->addOption(CURLOPT_HTTPHEADER, array(
                    'Accept: application/json; indent=4',
                    'content-type: application/json'));
            $resultJson = $curl->exec();
            $resultStatus = $curl->getInfo();
        } catch (Exception $exc) {
            $resultJson = false;
        }
        
        if($curl) {
            $curl->close();
        }
        
        if(false !== $resultJson && $resultStatus['http_code'] == 201) {
            return json_decode($resultJson, true);
        }
        
        return false;
    }
    
    /**
     * Checks, if order id from control field is completed
     * @param int $control Order id from control field
     * @return boolean
     */
    private static function checkIfCompletedControlExist($control, $channel) {
        $api = new SellerApi(Dotpay::getInstance()->getSettings()->getSellerUrl());
        $payments = $api->getPaymentByOrderId(Dotpay::getInstance()->getSettings()->getApiUsername(), Dotpay::getInstance()->getSettings()->getApiPassword(), $control);
        foreach($payments as $payment) {
            $onePayment = $api->getPaymentByNumber(Dotpay::getInstance()->getSettings()->getApiUsername(), Dotpay::getInstance()->getSettings()->getApiPassword(), $payment->number);
            if($onePayment->control == $control && $onePayment->payment_method->channel_id == $channel && $payment->status == 'completed')
                return true;
        }
        return false;
    }

    /**
     * Prepares the data for query.
     * @param int $orderId Order id
     * @param int $channelId Dotpay channel id
     * @return array
     */
    private static function prepareData($orderId, $channelId) {
        $wrapper = entity_metadata_wrapper('commerce_order', $orderId);
        $order = commerce_order_load($orderId);
        $amount = $wrapper->commerce_order_total->amount->value();
        $currency = $wrapper->commerce_order_total->currency_code->value();
        $streetData = Dotpay::getStreetAndStreetN1($wrapper);
        return array (
            'order' => array (
                'amount' => Dotpay::calculateDecimalAmount($amount, $currency),
                'currency' => $currency,
                'description' => t('Order @order_number', array('@order_number' => $order->order_id)),
                'control' => $order->order_id,
            ),

            'seller' => array (
                'account_id' => Dotpay::getInstance()->getSettings()->getId(),
                'url' => Dotpay::getInstance()->getFullUrl('/dotpay/'.$order->order_id.'/back'),
                'urlc' => Dotpay::getInstance()->getFullUrl('/dotpay/confirm'),
            ),

            'payer' => array (
                'first_name' => $wrapper->commerce_customer_billing->commerce_customer_address->first_name->value(),
                'last_name' => $wrapper->commerce_customer_billing->commerce_customer_address->last_name->value(),
                'email' => $order->mail,
                'address' => array(
                    'street' => $streetData['street'],
                    'building_number' => $streetData['street_n1'],
                    'postcode' => Dotpay::getCorrectPostcode($wrapper),
                    'city' => $wrapper->commerce_customer_billing->commerce_customer_address->locality->value(),
                    'country' => $wrapper->commerce_customer_billing->commerce_customer_address->country->value(),
                )
            ),

            'payment_method' => array (
                'channel_id' => $channelId
            ),

            'request_context' => array (
                'ip' => $_SERVER['REMOTE_ADDR']
            )

        );
    }
}
