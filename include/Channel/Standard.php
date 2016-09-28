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
use Dotpay\Instruction;
use Dotpay\RegisterOrder;

/**
 * Standard payment channel via Dotpay
 */
class Standard extends AbstractChannel {
    const name = 'standard';
    
    /**
     * Returns info about payment channel
     * @return array
     */
    public function getInfo() {
        $channels = array();
        $channels['dotpay_standard_channel'] = array(
            'method_id' => 'dotpay',
            'base' => 'dotpay_gateway',
            'title' => t('Dotpay main channel'),
            'display_title' => t('Dotpay - fast and secure payment'),
            'description' => t('Dotpay payment channels'),
            'terminal' => FALSE,
            'offsite' => TRUE,
            'callbacks' => array(
                'redirect_form' => 'commerce_dotpay_standard_redirect_form',
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
        $this->addWidgetConfigScript($order->order_id);
        $target = Dotpay::getInstance()->getFullUrl('dotpay/'.$order->order_id.'/'.self::name.'/form');
        if(!Dotpay::getInstance()->getSettings()->getWidget())
            header('Location: '.$target);
        drupal_add_css($this->getWidgetCssUrl(), array('type'=>'external'));
        Dotpay::addValidateScript();
        drupal_add_js(drupal_get_path('module', 'commerce_dotpay') . '/web/js/payment_widget.js');
        $form['#action'] = $target;
        $form['#attributes'] = array(
            'class' => 'dotpay-form widget-form-payment'
        );
		$form['chosen_payment_method_info'] = array(
			  '#type' => 'item',
			  '#markup' => '<div class="dotformcc"><br>'.$this->getUniversalThumb('dotpay.png').' <span class="dotformmethod-chosen">'.t(' Dotpay - fast and secure payment').'</span><br><br>'.t('Select one of the available below payment methods').' &dArr;</div><br>',
		  
		);
		
        $form['widget'] = array(
            '#type' => 'hidden',
            '#prefix' => '<p class="my-form-widget-container"></p>'
        );
        $form = $this->addAgreementToForm($form, $order->order_id);
        $form['submit'] = array(
            '#type' => 'submit',
            '#value' => t('Pay via Dotpay'),
	);
        $this->createTransaction($order, $payment_method);
        return $form;
    }
    
    /**
     * Returns url to channel thumb image
     * @return string
     */
    public function getThumb() {
        return $this->getUniversalThumb('dotpay.png');
    }
    
    /**
     * Prepare transfer payment: register new payment by RegisterOrder and create instruction of payment
     * @param type $orderId Order id
     * @param type $channelId Dotpay channel id
     * @return Instruction
     */
    public function prepareTransferPayment($orderId, $channelId) {
        if(empty($orderId))
            return NULL;
        $payment = RegisterOrder::create($orderId, $channelId);
        if($payment === NULL) {
            $instruction = Instruction::getByOrderId($orderId);
        } else {
            if(Dotpay::isChannelInGroup($orderId, $payment['operation']['payment_method']['channel_id'], array(self::cashGroup))) {
                $isCash = true;
            } else {
                $isCash = false;
            }
            $instruction = new Instruction();
            $instruction->setAmount($payment['instruction']['amount']);
            $instruction->setCurrency($payment['instruction']['currency']);
            $instruction->setNumber($payment['operation']['number']);
            $instruction->setCash($isCash);
            $instruction->setHash(Instruction::gethashFromPayment($payment));
            $instruction->setOrderId($orderId);
            $instruction->setChannel($payment['operation']['payment_method']['channel_id']);
            if(isset($payment['instruction']['recipient'])) {
                $instruction->setBankAccount($payment['instruction']['recipient']['bank_account_number']);
            }
            $instruction->save();
        }
        return $instruction;
    }
    
    /**
     * Returns fields for hidden form with standard values but without CHK field
     * @param int $orderId Order id
     * @return array
     */
    protected function getPaymentFields($orderId) {
        $fields = parent::getPaymentFields($orderId);
        if(Dotpay::getInstance()->getSettings()->getWidget()) {
            $fields['type'] = 4;
            $fields['ch_lock'] = 1;
            $fields['channel'] = Dotpay::getParam('channel');
        }
        $this->setValue('channel', Dotpay::getParam('channel'));
        return $fields;
    }
    
    /**
     * Returns url to Dotpay widget css file
     * @return string
     */
    private function getWidgetCssUrl() {
        return 'https://ssl.dotpay.pl/test_payment/widget/payment_widget.css';
    }
    
    /**
     * Adds script with widget config JSON
     * @param int $orderId Order id
     */
    private function addWidgetConfigScript($orderId) {
        $wrapper = entity_metadata_wrapper('commerce_order', $orderId);
        $amount = $wrapper->commerce_order_total->amount->value();
        $currency = $wrapper->commerce_order_total->currency_code->value();
        $script = 'window.dotpayWidgetConfig = {
                        "sellerAccountId": "'.Dotpay::getInstance()->getSettings()->getId().'",
                        "amount": "'.Dotpay::calculateDecimalAmount($amount, $currency).'",
                        "currency": "'.$currency.'",
                        "lang": "'.Dotpay::getCorrectLanguage().'",
                        "widgetFormContainerClass": "my-form-widget-container",
                        "offlineChannel": "mark",
                        "offlineChannelTooltip": true
                    };';    
        $element = array(
            '#type' => 'markup',
            '#markup' => '<script>'.$script.'</script>',
        );
        drupal_add_html_head($element, 'dotpay-config');
    }
}
