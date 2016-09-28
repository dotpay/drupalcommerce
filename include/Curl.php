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
 * Tool for support Curl
 */
class Curl {
    /**
     *
     * @var resource cURL resource
     */
    private $_resource;
    
    /**
     * 
     * @var mixed Information about last request
     */
    private $_info;
    
    /**
     * 
     * @var boolean Information about whether cURL resource is active
     */
    private $_isActive;
    
    /**
     * Initializes a cURL session
     * @return \Curl
     */
    public function __construct() {
        $this->_resource = curl_init();
        $this->_isActive = true;
    }
    
    /**
     * Clears a cURL resource
     */
    public function __destruct() {
        if($this->_isActive)
            $this->close();
    }
    
    /**
     * Sets an CA file for a cURL transfer
     * @param string $file
     * @return \Curl
     */
    public function addCaInfo($file) {
        $this->addOption(CURLOPT_CAINFO, $file);
        
        return $this;
    }
    
    /**
     * Sets an option for a cURL transfer
     * @param mixed $option
     * @param mixed $value
     * @return \Curl
     */
    public function addOption($option, $value) {
        curl_setopt($this->_resource, $option, $value);
        return $this;
    }
    
    /**
     * Performs a cURL session
     * @return mixed
     */
    public function exec() {
        $response = curl_exec($this->_resource);
        $this->_info = curl_getinfo($this->_resource);
        
        return $response;
    }
    
    /**
     * Gets information regarding a specific transfer
     * @return mixed
     */
    public function getInfo() {
        return $this->_info;
    }
    
    /**
     * Closes a cURL session
     */
    public function close() {
        curl_close($this->_resource);
        $this->_isActive = false;
    }
}