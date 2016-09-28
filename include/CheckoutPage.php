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
 * Description of CheckoutPage
 *
 * @author tomasz
 */
class CheckoutPage {
    public function getOCIcon() {
        return $this->getUniversalIcon('oneclick.png');
    }
    public function getPVIcon() {
        return $this->getUniversalIcon('oneclick.png');
    }
    public function getBlikIcon() {
        return $this->getUniversalIcon('blik.png');
    }
    public function getDotpayIcon() {
        return $this->getUniversalIcon('dotpay.png');
    }
    public function getMPIcon() {
        return $this->getUniversalIcon('MasterPass.png');
    }
    private function getUniversalIcon($filename) {
        $iconParameters = array(
		'path' => drupal_get_path('module', 'commerce_dotpay') . '/images/icons/'.$filename,
		'title' => 'dotpay icon',
		'alt' => 'dotpay icon',
		'attributes' => array(
		),
	);
	return theme('image', $iconParameters);
    }
}
