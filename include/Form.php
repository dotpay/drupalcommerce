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

/**
 * Provides functionality to render hidden form with payment data, sending to Dotpay
 */
class Form {
    private $template = '';
    private $fields = array();
    private $target = '';
    private $driver = null;
    private $channel;
    
    /**
     * Prepare object before using
     * @param \Dotpay\Channel\AbstractChannel $channelDriver Dotpay channel used to current payment
     */
    public function __construct(Channel\AbstractChannel $channelDriver) {
        $this->driver = $channelDriver;
        $this->template = dirname(__DIR__).'/templates/form.phtml';
    }

    /**
     * Returns rendered template with HTML code of hidden form
     * @param type $orderId Order id
     * @return string
     */
    public function render($orderId) {
        $this->fields = $this->driver->getFieldsWithChk($orderId);
        $this->target = Dotpay::getInstance()->getSettings()->getPaymentUrl();
        ob_start();
        if(file_exists($this->template))
            include($this->template);
        return ob_get_clean();
    }
}
