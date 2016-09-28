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

/**
 * Credit Card payment channel via Dotpay
 */
class CC extends AbstractChannel {
    const ccChannel = 246;
    const pvChannel = 248;
    const ccName = 'cc';
    const pvName = 'pv';
    
    /**
     * Returns info about payment channel
     * @return array
     */
    public function getInfo() {
        $channels = array();
        $channels['dotpay_cc_channel'] = array(
            'method_id' => 'dotpay',
            'base' => 'dotpay_gateway',
            'title' => t('Credit card channel separately by Dotpay'),
            'display_title' => t('Credit card via Dotpay').$this->is3DSecure(),
            'description' => t('Credit card by Dotpay'),
            'terminal' => FALSE,
            'offsite' => TRUE,
            'callbacks' => array(
                'redirect_form' => 'commerce_dotpay_cc_redirect_form',
            )
        );
        return $channels;
    }
    
    /**
     * Returns payment form with visible detail fields for Drupal API Forms
     * @param array $form Payment form
     * @param array $form_state Form state
     * @param object $order Order object
     * @param array $payment_method Payment method info
     * @return string
     */
    public function getFrontForm($form, &$form_state, $order, $payment_method) {
        Dotpay::addValidateScript();
        $form['#action'] = Dotpay::getInstance()->getFullUrl('dotpay/'.$order->order_id.'/'.self::ccName.'/form');
        $form['#attributes'] = array(
            'class' => 'dotpay-form cc-form-payment'
        );
        $form['phone'] = array(
			'#prefix' => '<div class="dotformcc">'.$this->getUniversalThumb('oneclick.png').' <span class="dotformmethod-chosen">'.t('Pay via credit card').$this->is3DSecure().'</span><br><br><br><h5>'.t('To continue payment, please complete Your data.').'</h5><br>',
            '#type' => 'textfield', 
            '#title' => t('Enter Your phone number:'),
            '#description' => t('This payment method requires additions your data.').'<br>',
            '#default_value' => '',
            '#size' => 16,
			'#maxlength' => 16,
            '#required' => TRUE,
            '#attributes' => array(
                'required' => 'required'
            ),
			'#suffix' => '</div><br>'
        );
        $form = $this->addAgreementToForm($form, $order->order_id);
        $form['submit'] = array(
            '#type' => 'submit',
            '#value' => t('Pay via credit card').$this->is3DSecure()
	);
        $this->createTransaction($order, $payment_method);
        return $form;
    }
    
    /**
     * Returns url to channel thumb image
     * @return string
     */
    public function getThumb() {
        return $this->getUniversalThumb('oneclick.png');
    }
    
    /**
     * Returns all fields for hidden form, with CHK field
     * @param int $orderId Order id
     * @return array
     */
    public function getFieldsWithChk($orderId) {
        if(!Dotpay::getInstance()->getSettings()->isPvActive())
            return parent::getFieldsWithChk($orderId);
        $id = Dotpay::getInstance()->getSettings()->getPvId();
        $pin = Dotpay::getInstance()->getSettings()->getPvPin();
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
        $fields = parent::getPaymentFields($orderId);
        $fields['type'] = 4;
        $fields['ch_lock'] = 1;
        $fields['phone'] = Dotpay::getParam('phone');
        if(Dotpay::getInstance()->getSettings()->isPvActive()) {
            $fields['channel'] = self::pvChannel;
            $fields['id'] = Dotpay::getInstance()->getSettings()->getPvId();
        } else
            $fields['channel'] = self::ccChannel;
        return $fields;
    }
    
    /**
     * Return part of credit card channel name, if selected method supports 3D-Secure
     * @return string
     */
    public function is3DSecure(){
        return (Dotpay::getInstance()->getSettings()->isPvActive())?' (3D-Secure)':'';
    }
}