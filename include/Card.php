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
 * Model for credit cards, used by One Click payments
 */
class Card {
    const tableName = 'dotpay_oneclick_cards';
    
    /**
     * Adds card to database and return card hash
     * @param int $customerId Customer id
     * @param int $orderId Order id
     * @return string
     */
    public static function addCard($customerId, $orderId) {
        $existedCard = db_query('SELECT hash FROM '.self::tableName.' WHERE customer_id = '.(int)$customerId.' AND order_id = '.(int)$orderId);
        if(!$existedCard->rowCount()) {
            $hash = self::generateCardHash();
            db_insert(self::tableName)
            ->fields(array(
                'customer_id' => $customerId, 
                'order_id' => $orderId,
                'hash' => $hash
            ))
            ->execute();
        } else {
            $result = $existedCard->fetchObject();
            $hash = $result->hash;
        }
        return $hash;
    }
    
    /**
     * Adds additional info to saved card
     * @param int $id Card id
     * @param int $cardId Card identifier from Dotpay
     * @param string $mask Card mask name
     * @param string $brand Card brand
     */
    public static function updateCard($id, $cardId, $mask, $brand) {
        db_update(self::tableName)
        ->fields(array(
            'card_id' => $cardId,
            'mask' => $mask,
            'brand' => $brand,
            'register_date' => date('Y-m-d')
        ))
        ->condition('cc_id', $id, '=')
        ->execute();
    }
    
    /**
     * Removes card for the given id
     * @param int $id Card id
     */
    public static function removeCard($id) {
        db_delete(self::tableName)
        ->condition('cc_id', $id)
        ->execute();
    }
    
    /**
     * Removes all cards for the given customer
     * @param int $customerId Customer id
     */
    public static function removeAllCardsForCustomer($customerId) {
        db_delete(self::tableName)
        ->condition('customer_id', $customerId)
        ->execute();
    }
    
    /**
     * Returns card data for the given order id
     * @param int $orderId Order id
     * @return \stdClass
     */
    public static function getCardFromOrder($orderId) {
        return db_query('SELECT * FROM '.self::tableName.' WHERE order_id = '.(int)$orderId)->fetch(\PDO::FETCH_OBJ);
    }
    
    /**
     * Returns array of cards with only the cards, which can be used in One Click payments
     * @param int $customerId Customer id
     * @return type
     */
    public static function getUsefulCardsForCustomer($customerId) {
        return db_query('SELECT * FROM '.self::tableName.' c JOIN '.CardBrand::tableName.' cb ON cb.name = c.brand WHERE c.customer_id = '.(int)$customerId .' AND c.card_id IS NOT NULL')->fetchAll(\PDO::FETCH_OBJ);
    }
    
    /**
     * Returns array of all cards for the given customer
     * @param int $customerId Customer id
     * @return array
     */
    public static function getCardsForCustomer($customerId) {
        return db_query('SELECT * FROM '.self::tableName.' WHERE customer_id = '.(int)$customerId)->fetchAll(\PDO::FETCH_OBJ);
    }
    
    /**
     * Returns card data fot the given card id
     * @param int $id Card id
     * @return type
     */
    public static function getCardById($id) {
        return db_query('SELECT * FROM '.self::tableName.' WHERE cc_id = '.(int)$id)->fetch(\PDO::FETCH_OBJ);
    }
    
    /**
     * Returns card hash
     * @return type
     */
    private static function generateCardHash() {
        $microtime = '' . microtime(true);
        $md5 = md5($microtime);

        $mtRand = mt_rand(0, 11);

        $md5Substr = substr($md5, $mtRand, 21);

        $a = substr($md5Substr, 0, 6);
        $b = substr($md5Substr, 6, 5);
        $c = substr($md5Substr, 11, 6);
        $d = substr($md5Substr, 17, 4);

        return "{$a}-{$b}-{$c}-{$d}";
    }
    
    /**
     * Generates card hash, unique in the database
     * @return string
     */
    private static function getUniqueHash() {
        $count = 200;
        $result = false;
        do {
            $cardHash = $this->generateCardHash();
            $test = db_query('SELECT * FROM '.self::tableName.' WHERE hash = '.$cardHash)->rowCount();
            
            if ($test == 0) {
                $result = $cardHash;
                break;
            }

            $count--;
        } while ($count);
        
        return $result;
    }
    
    /**
     * Installs database structure for this model
     */
    public static function install() {
        $sql = 'CREATE TABLE IF NOT EXISTS `'.self::tableName.'` (
                    `cc_id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
                    `order_id` INT UNSIGNED NOT NULL,
                    `customer_id` INT(10) UNSIGNED NOT NULL,
                    `mask` varchar(20) DEFAULT NULL,
                    `brand` varchar(20) DEFAULT NULL,
                    `hash` varchar(100) NOT NULL,
                    `card_id` VARCHAR(128) DEFAULT NULL,
                    `register_date` DATE DEFAULT NULL,
                    PRIMARY KEY (`cc_id`),
                    UNIQUE KEY `hash` (`hash`),
                    UNIQUE KEY `cc_order` (`order_id`),
                    UNIQUE KEY `card_id` (`card_id`),
                    KEY `customer_id` (`customer_id`),
                    CONSTRAINT fk_customer_id
                        FOREIGN KEY (customer_id)
                        REFERENCES `users` (`uid`)
                        ON DELETE CASCADE
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
}