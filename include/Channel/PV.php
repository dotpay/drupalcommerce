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

use \Dotpay\Dotpay;

/**
 * Description of Blik
 *
 * @author tomasz
 */
class PV extends AbstractChannel {
    const channel = 248;
    const name = 'pv';
    public function getInfo() {
        $channels = array();
        $channels['dotpay_cc_channel'] = array(
            'method_id' => 'dotpay',
            'base' => 'dotpay_gateway',
            'title' => t('Card channel for Your currency'),
            'display_title' => t('Card channel').' (3D Secure)',
            'description' => t('Card channel by Dotpay'),
            'terminal' => FALSE,
            'offsite' => TRUE,
            'callbacks' => array(
                'redirect_form' => 'commerce_dotpay_pv_redirect_form',
            )
        );
        return $channels;
    }
    public function getFrontForm($form, &$form_state, $order, $payment_method) {
        drupal_add_js(drupal_get_path('module', 'commerce_dotpay') . '/web/js/validate.js');
        $form['#action'] = Dotpay::getInstance()->getFullUrl('dotpay/'.$order->order_id.'/'.self::name.'/form');
        $form['#attributes'] = array(
            'class' => 'dotpay-form cc-form-payment'
        );
        $form = $this->addAgreementToForm($form);
        $form['submit'] = array(
            '#type' => 'submit',
            '#value' => t('Pay via credit card').' (3D-Secure)'
	);
        $this->createTransaction($order, $payment_method);
        return $form;
    }
    protected function getPaymentFields($orderId) {
        $fields = parent::getPaymentFields($orderId);
        $fields['id'] = Dotpay::getInstance()->getSettings()->getPvId();
        $fields['type'] = 4;
        $fields['ch_lock'] = 1;
        $fields['channel'] = self::channel;
        return $fields;
    }
    public function getFieldsWithChk($orderId) {
        $id = Dotpay::getInstance()->getSettings()->getPvId();
        $pin = Dotpay::getInstance()->getSettings()->getPvPin();
        $fields = $this->getPaymentFields($orderId);
        $fields['chk'] = $this->generateCHK($id, $pin, $fields);
        return $fields;
    }
    public function getThumb() {
        return $this->getUniversalThumb('oneclick.png');
    }
}
