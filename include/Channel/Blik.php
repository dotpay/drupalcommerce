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
 * BLIK payment channel via Dotpay
 */
class Blik extends AbstractChannel {
    const channel = 73;
    const name = 'blik';
    /**
     * Returns info about payment channel
     * @return array
     */
    public function getInfo() {
        $channels = array();
        $channels['dotpay_blik_channel'] = array(
            'method_id' => 'dotpay',
            'base' => 'dotpay_gateway',
            'title' => t('Blik channel separately by Dotpay'),
            'display_title' => t('Blik via Dotpay'),
            'description' => t('Blik by Dotpay'),
            'terminal' => FALSE,
            'offsite' => TRUE,
            'callbacks' => array(
                'redirect_form' => 'commerce_dotpay_blik_redirect_form',
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
        $form['#action'] = Dotpay::getInstance()->getFullUrl('dotpay/'.$order->order_id.'/'.self::name.'/form');
        $form['#attributes'] = array(
            'class' => 'dotpay-form blik-form-payment'
        );
        $form['blik_code'] = array(
			'#prefix' => '<div class="dotformmethod">'.$this->getUniversalThumb('blik.png').' <span class="dotformmethod-chosen">'.t('Pay via BLIK (by Dotpay)').'</span><br><br><h5>'.t('To continue payment, please enter the BLIK code.').'</h5>',
            '#type' => 'textfield', 
            '#title' => t('BLIK code'), 
			'#description' => '<br>'.t('The BLIK code is a 6-digit authorisation number, which is displayed after you log on to the Mobile Application from Your Bank.').'<br>',
            '#default_value' => '', 
            '#size' => 6, 
            '#maxlength' => 6, 
            '#required' => TRUE,
            '#attributes' => array(
                'required' => 'required'
            ),
			'#suffix' => '</div><br>'
        );
        $form = $this->addAgreementToForm($form, $order->order_id);
        $form['submit'] = array(
            '#type' => 'submit',
            '#value' => t('Pay via BLIK'),
	);
        $this->createTransaction($order, $payment_method);
        return $form;
    }
    
    /**
     * Returns url to channel thumb image
     * @return string
     */
    public function getThumb() {
        return $this->getUniversalThumb('blik.png');
    }
    
    /**
     * Returns fields for hidden form with standard values but without CHK field
     * @param int $orderId Order id
     * @return array
     */
    protected function getPaymentFields($orderId) {
        $fields = parent::getPaymentFields($orderId);
        $blik = $this->getValue('blik_code');
        $fields['type'] = 4;
        $fields['ch_lock'] = 1;
        $fields['channel'] = self::channel;
        if(Dotpay::getInstance()->getSettings()->getTestMode()!=1)
            $fields['blik_code'] = $blik;
        return $fields;
    }
}