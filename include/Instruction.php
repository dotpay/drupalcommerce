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

require_once(mydirname(__DIR__,2).'/vendor/simple_html_dom.php');

/**
 * Payment instruction model for Dotpay payment gateway
 */
class Instruction {
    const DOTPAY_NAME = 'DOTPAY SA';
    const DOTPAY_STREET = 'Wielicka 72';
    const DOTPAY_CITY = '30-552 Kraków';
    
    const tableName = 'dotpay_payment_instructions';
    
    private $instructionId;
    private $orderId;
    private $number;
    private $hash;
    private $isCash;
    private $bankAccount;
    private $amount;
    private $currency;
    private $channel;

    /**
     * Returns instruction id
     * @return int
     */
    public function getInstructionId() {
        return $this->instructionId;
    }
    
    /**
     * Returns order id
     * @return int
     */
    public function getOrderId() {
        return $this->orderId;
    }
    
    /**
     * Returns mask number
     * @return string
     */
    public function getNumber() {
        return $this->number;
    }
    
    /**
     * Returns instruction hash
     * @return string
     */
    public function getHash() {
        return $this->hash;
    }
    
    /**
     * Returns flag, if instruction applies to cash method
     * @return boolean
     */
    public function isCash() {
        return $this->isCash;
    }
    
    /**
     * Returns bank account, if instruction applies to transfer method
     * @return string|null
     */
    public function getBankAccount() {
        return $this->bankAccount;
    }
    
    /**
     * Returns amount
     * @return float
     */
    public function getAmount() {
        return $this->amount;
    }
    
    /**
     * Returns currency
     * @return string
     */
    public function getCurrency() {
        return $this->currency;
    }
    
    /**
     * Returns payment channel id
     * @return int
     */
    public function getChannel() {
        return $this->channel;
    }
    
    /**
     * Sets instruction id
     * @param int $instructionId instruction id
     * @return \Dotpay_Instruction
     */
    public function setInstructionId($instructionId) {
        $this->instructionId = $instructionId;
        return $this;
    }
    
    /**
     * Sets order id
     * @param int $orderId order id
     * @return \Dotpay_Instruction
     */
    public function setOrderId($orderId) {
        $this->orderId = $orderId;
        return $this;
    }
    
    /**
     * Sets instruction number
     * @param string $number instruction number
     * @return \Dotpay_Instruction
     */
    public function setNumber($number) {
        $this->number = $number;
        return $this;
    }
    
    /**
     * Sets instruction hash
     * @param string $hash instruction hash
     * @return \Dotpay_Instruction
     */
    public function setHash($hash) {
        $this->hash = $hash;
        return $this;
    }
    
    /**
     * Sets true, if payment channel belongs to cash group
     * @param bool $cash cash flag
     * @return \Dotpay_Instruction
     */
    public function setCash($cash) {
        $this->isCash = (int)$cash;
        return $this;
    }
    
    /**
     * Sets bank account number
     * @param string $bankAccount bank account number
     * @return \Dotpay_Instruction
     */
    public function setBankAccount($bankAccount) {
        $this->bankAccount = $bankAccount;
        return $this;
    }
    
    /**
     * Sets amount
     * @param float $amount amount
     * @return \Dotpay_Instruction
     */
    public function setAmount($amount) {
        $this->amount = $amount;
        return $this;
    }
    
    /**
     * Sets currency
     * @param string $currency currency
     * @return \Dotpay_Instruction
     */
    public function setCurrency($currency) {
        $this->currency = $currency;
        return $this;
    }

    /**
     * Sets channel id
     * @param int $channel channel id
     * @return \Dotpay_Instruction
     */
    public function setChannel($channel) {
        $this->channel = $channel;
        return $this;
    }
    
    /**
     * Returns recipient name
     * @return string
     */
    public function getRecipient() {
        return self::DOTPAY_NAME;
    }
    
    /**
     * Returns street of recipient
     * @return string
     */
    public function getStreet() {
        return self::DOTPAY_STREET;
    }
    
    /**
     * Returns city of recipient
     * @return string
     */
    public function getCity() {
        return self::DOTPAY_CITY;
    }
    
    /**
     * Returns translated command for instruction button
     * @return string
     */
    public function getCommand() {
        if($this->isCash)
            return t('Download blankiet');
        else
            return t('Make a money transfer');
    }

    /**
     * Creates table for this model
     */
    public static function install() {
        $sql = 'CREATE TABLE IF NOT EXISTS `'.self::tableName.'` (
                    `instruction_id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
                    `order_id` INT UNSIGNED NOT NULL,
                    `number` varchar(64) NOT NULL,
                    `hash` varchar(128) NOT NULL,
                    `is_cash` TINYINT NOT NULL,
                    `bank_account` VARCHAR(64),
                    `amount` decimal(10,2) NOT NULL,
                    `currency` varchar(3) NOT NULL,
                    `channel` INT UNSIGNED NOT NULL,
                    PRIMARY KEY (`instruction_id`)
                ) DEFAULT CHARSET=utf8;';
        db_query($sql);
    }
    
    /**
     * Clears database structure after this model
     */
    public static function uninstall() {
        $sql = 'DROP TABLE IF EXISTS `'.self::tableName.'`;';
        db_query($sql);
    }
    
    /**
     * Returns payment instruction by order id
     * @param int $orderId order id
     * @return \Dotpay_Instruction
     */
    public static function getByOrderId($orderId) {
        $result = db_query('
            SELECT instruction_id as id
            FROM `'.self::tableName.'` 
            WHERE order_id = '.(int)$orderId
        )->fetchAll(\PDO::FETCH_OBJ);
        if(!is_array($result))
            return NULL;
        return new Instruction($result[count($result)-1]->id);
    }
    
    /**
     * Returns instruction hash from payment
     * @param array $payment payment
     * @return string
     */
    public static function gethashFromPayment($payment) {
        $parts = explode('/',$payment['instruction']['instruction_url']);
        return $parts[count($parts)-2];
    }
    
    /**
     * Returns url to bank site
     * @param string $baseUrl base url for Dotpay server
     * @return string
     */
    public function getBankPage($baseUrl) {
        $url = $this->buildInstructionUrl($baseUrl);
        $html = file_get_html($url);
        if($html==false)
            return null;
        return $html->getElementById('channel_container_')->firstChild()->getAttribute('href');
    }
    
    /**
     * Returns url to pdf payment instruction
     * @return string
     */
    public function getPdfUrl($baseUrl) {
        return $baseUrl.'instruction/pdf/'.$this->number.'/'.$this->hash.'/';
    }
    
    /**
     * Returns url to the payment instruction on Dotpay server
     * @param string $baseUrl base url for Dotpay server
     * @return string
     */
    protected function buildInstructionUrl($baseUrl) {
        return $baseUrl.'instruction/'.$this->number.'/'.$this->hash.'/';
    }
    
    /**
     * Saves changes in model to the database
     * @return boolean
     */
    public function save() {
        $existedCard = db_query('SELECT * FROM '.self::tableName.' WHERE instruction_id = '.(int)$this->instructionId)->fetch(\PDO::FETCH_OBJ);;
        if(empty($existedCard)) {
            db_insert(self::tableName)
            ->fields(array(
                'order_id' => $this->orderId,
                'number' => $this->number,
                'hash' => $this->hash,
                'is_cash' => $this->isCash,
                'bank_account' => $this->bankAccount,
                'amount' => $this->amount,
                'currency' => $this->currency,
                'channel' => $this->channel
            ))
            ->execute();
        } else {
            db_update(self::tableName)
            ->fields(array(
                'order_id' => $this->orderId, 
                'number' => $this->number,
                'hash' => $this->hash,
                'is_cash' => $this->isCash,
                'bank_account' => $this->bankAccount,
                'amount' => $this->amount,
                'currency' => $this->currency,
                'channel' => $this->channel
            ))
            ->condition('instruction_id', $this->instructionId, '=')
            ->execute();
        }
        $instruction = db_query( 'SELECT * FROM '.self::tableName.' WHERE order_id = '.(int)$this->orderId.' AND hash = \''.$this->hash.'\' AND amount = \''.$this->amount.'\'')->fetch(\PDO::FETCH_OBJ);
        $this->instructionId = $instruction->instruction_id;
        return true;
    }
    
    /**
     * Prepares instruction object
     * @param int|null $id instruction id
     * @return type
     */
    public function __construct($id = null) {
        if($id===NULL)
            return;
        $result = db_query('
            SELECT * 
            FROM `'.self::tableName.'` 
            WHERE instruction_id = '.(int)$id
        )->fetch(\PDO::FETCH_OBJ);
        if(empty($result))
            return;
        $this->amount = $result->amount;
        $this->channel = $result->channel;
        $this->currency = $result->currency;
        $this->hash = $result->hash;
        $this->isCash = $result->is_cash;
        $this->number = $result->number;
        $this->orderId = $result->order_id;
        $this->bankAccount = $result->bank_account_number;
        $this->instructionId = $result->instruction_id;
    }
    
    /**
     * Returns path to the image with channel logo
     * @return string
     */
    public function getChannelLogo() {
        $chData =Dotpay::getChannelData($this->orderId, $this->getChannel());
        return $chData['logo'];
    }
    
    /**
     * Returns content of page with payment instruction
     * @return string
     */
    public function getPage() {
        return $this->render('payment_info.phtml');
    }
    
    /**
     * Returns url to bank site or pdf payment instruction
     * @return string
     */
    public function getAddress() {
        if($this->isCash) {
            return $this->getPdfUrl(Dotpay::getInstance()->getSettings()->getPaymentUrl());
        } else {
            return $this->getBankPage(Dotpay::getInstance()->getSettings()->getPaymentUrl());
        }
    }
}