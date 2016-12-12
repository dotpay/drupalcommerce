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

class CardBrand {
    const tableName = 'dotpay_card_brands';
    
    /**
     * Installs database structure for this model
     */
    public static function install() {
        $sql = 'CREATE TABLE IF NOT EXISTS `'.self::tableName.'` (
                    `name` varchar(20) DEFAULT NULL,
                    `image` varchar(170) DEFAULT NULL,
                    PRIMARY KEY (`name`),
                    UNIQUE KEY `brand_img` (`image`(200))
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
    
    public static function updateBrand($name, $image) {
        db_query('
            INSERT INTO `'.self::tableName.'`
                (name, image)
            VALUES
                (\''.$name.'\', \''.$image.'\')
            ON DUPLICATE KEY UPDATE
                name  = \''.$name.'\',
                image = \''.$image.'\'
        ');
    }
    
    public static function getImage($name) {
        return db_query('SELECT image FROM '.self::tableName.' WHERE name = \''.$name.'\'')->fetch(\PDO::FETCH_OBJ);
    }
}

?>