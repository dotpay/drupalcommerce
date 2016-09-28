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
use Dotpay\Card;

/**
 * One Click cards payment channel via Dotpay
 */
class OneClick extends AbstractChannel {
    const channel = 248;
    const name = 'oc';
    
    /**
     * Returns info about payment channel
     * @return array
     */
    public function getInfo() {
        $channels = array();
        $channels['dotpay_oc_channel'] = array(
            'method_id' => 'dotpay',
            'base' => 'dotpay_gateway',
            'title' => t('One Click card channel via Dotpay'),
            'display_title' => t('Credit card - One Click by Dotpay'),
            'description' => t('One Click by Dotpay'),
            'terminal' => FALSE,
            'offsite' => TRUE,
            'callbacks' => array(
                'redirect_form' => 'commerce_dotpay_oc_redirect_form',
            )
        );
        return $channels;
    }
    
    /**
     * Returns payment form with visible detail fields for Drupal API Forms
     * @global object $user Drupal user object
     * @param array $form Payment form
     * @param array $form_state Form state
     * @param object $order Order object
     * @param array $payment_method Payment method info
     * @return string
     */
    public function getFrontForm($form, &$form_state, $order, $payment_method) {
        global $user;
        Dotpay::addValidateScript();
        drupal_add_js(drupal_get_path('module', 'commerce_dotpay') . '/web/js/oneclick.js');
        drupal_add_js(drupal_get_path('module', 'commerce_dotpay') . '/web/js/jquery.transit.min.js');
        drupal_add_css(drupal_get_path('module', 'commerce_dotpay') . '/web/css/oneclick.css');
        $form['#action'] = Dotpay::getInstance()->getFullUrl('dotpay/'.$order->order_id.'/'.self::name.'/form');
        $form['#attributes'] = array(
            'class' => 'dotpay-form oc-form-payment'
        );
        $cardData = Card::getUsefulCardsForCustomer($user->uid);
        $cards = $this->getOcCards($cardData);
        $img = '';
        if(count($cards)) {
            $img = '</div><div id="oc-right-box"><img class="dotpay-card-logo" ';
            foreach($cardData as $card) {
                $img .= 'data-card-'.$card->cc_id.'="'.$card->image.'" ';
            }
            $img .= '/></div></div>';
            
			$form['chosen_payment_method_info'] = array(
				  '#prefix' => '<div class="dotformcc">'.$this->getUniversalThumb('oneclick.png').' <span class="dotformmethod-chosen">'.t('Pay via credit card by One Click').'</span><br><br><br>',	
				  '#type' => 'item',
				);
			
			$form['choose'] = array(
                '#type' => 'radio',
                '#title' => t('Choose from saved cards').'&nbsp;(<a href="'.url('user/'.$user->uid.'/ocmanage').'" target="_blank">'.t('Manage your saved cards').'</a>)',
                '#return_value' => 'choose',
                '#attributes' => array(
                    'name' => 'oc_type'
                ),
                '#prefix' => '<div id="oc-row"><div id="oc-left-box">'
            );
            $form['saved_cards'] = array(
                '#type' => 'select',
                '#title' => t('Saved cards'),
                '#options' => $cards,
                '#default_value' => null,
                '#attributes' => array(
                    'class' => 'oc_list'
                )
            );
        }
        $form['register'] = array(
            '#type' => 'radio',
            '#title' => t('Register a new card'),
            '#return_value' => 'register',
            '#attributes' => array(
                'name' => 'oc_type'
            ),
            '#suffix' => $img
        );
        $form['oc_agreements'] = array(
            '#type' => 'checkbox',
            '#title' => t('I agree to repeated loading bill my credit card for the payment One-Click by way of purchase of goods or services offered by the store.'),
            '#required' => 1,
            '#default_value' => 1,
            '#attributes' => array(
                'checked' => 'checked',
                'required' => 'required'
            ),
			'#suffix' => '</div><br>'
        );
        $form = $this->addAgreementToForm($form, $order->order_id);
        $form['submit'] = array(
            '#type' => 'submit',
            '#value' => t('Pay faster via Credit Card').' (One Click)'
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
     * Returns fields for hidden form with standard values but without CHK field
     * @param int $orderId Order id
     * @return array
     */
    protected function getPaymentFields($orderId) {
        $fields = parent::getPaymentFields($orderId);
        $fields['type'] = 4;
        $fields['ch_lock'] = 1;
        $fields['channel'] = self::channel;
        if(Dotpay::getParam('oc_type') == 'register') {
            $fields['credit_card_store'] = 1;
            $fields['credit_card_customer_id'] = $this->registerCard($orderId);
        } else {
            $card = Card::getCardById(Dotpay::getParam('saved_cards'));
            $fields['credit_card_customer_id'] = $card->hash;
            $fields['credit_card_id'] = $card->card_id;
        }
        return $fields;
    }
    
    /**
     * Returns list of One Click cards for current user, adapted to Drupal API Form (select field)
     * @global object $user Drupal user object
     * @param array $cards List of card data
     * @return string
     */
    private function getOcCards($cards) {
        $options = array();
        foreach($cards as $card)
            $options[$card->cc_id] = $card->mask.' ('.$card->brand.')';
        return $options;
    }
    
    /**
     * Register new card for One Click method with given order
     * @global object $user Drupal user object
     * @param int $orderId Order id
     * @return string
     */
    private function registerCard($orderId){
        global $user;
        $hash = Card::addCard($user->uid, $orderId);
        return $hash;
    }
}