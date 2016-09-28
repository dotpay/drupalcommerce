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
 * MasterPass payment channel via Dotpay
 */
class MasterPass extends AbstractChannel {
    const channel = 71;
    const name = 'mp';
    
    /**
     * Returns info about payment channel
     * @return array
     */
    public function getInfo() {
        $channels = array();
        $channels['dotpay_mp_channel'] = array(
            'method_id' => 'dotpay_mp',
            'base' => 'dotpay_gateway',
            'title' => t('MasterPass channel separately via Dotpay'),
            'display_title' => t('MasterPass via Dotpay'),
            'description' => t('MasterPass by Dotpay'),
            'terminal' => FALSE,
            'offsite' => TRUE,
            'callbacks' => array(
                'redirect_form' => 'commerce_dotpay_mp_redirect_form',
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
            'class' => 'dotpay-form mp-form-payment'
        );
		
		$form['chosen_payment_method_info'] = array(
			  '#type' => 'item',
			  '#markup' => '<div class="dotformcc"><br>'.$this->getUniversalThumb('MasterPass.png').' <span class="dotformmethod-chosen">'.t(' MasterPass via Dotpay').'</span><br></div><br>',
		);
		
        $form = $this->addAgreementToForm($form, $order->order_id);
        $form['submit'] = array(
            '#type' => 'submit',
            '#value' => t('Pay via MasterPass')
	);
        $this->createTransaction($order, $payment_method);
        return $form;
    }
    
    /**
     * Returns url to channel thumb image
     * @return string
     */
    public function getThumb() {
        return $this->getUniversalThumb('MasterPass.png');
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
        $fields['channel'] = self::channel;
        return $fields;
    }
}