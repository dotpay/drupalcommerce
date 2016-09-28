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

function validateRequiredFields(form) {
    var result = true;
    jQuery(form).find('.required').each(function(){
        if((jQuery(this).attr('type')=='checkbox' && jQuery(this).attr('checked')!=true)) {
            alert(window.validateMessages.requiredFields);
            result = false;
            return false;
        }
    });
    return result;
}

function validateBlik(form) {
    var code = form.find('[name=blik_code]').val();
    if(code.length != 6 || code != parseInt(code, 10)) {
        alert(window.validateMessages.badBlikCode);
        return false;
    }
    return true;
}

function validateCC(form) {
    var phone = form.find('[name=phone]').val();
    var pattern = /^[^a-zA-Z]{7,}$/g;
    if(!pattern.test(phone)) {
        alert(window.validateMessages.badPhoneNumber);
        return false;
    }
    return true;
}

function validateWidget(form) {
    if(form.find('[name=channel]:checked').val() == undefined) {
        alert(window.validateMessages.emptyChannel);
        return false;
    }
    return true;
}

if(jQuery != undefined) {
    jQuery(document).ready(function(){
        jQuery('.dotpay-form input[type=submit]').click(function(){
            var form = jQuery(this).parents('form');
            if(!validateRequiredFields(form))
                return false;
            if(form.hasClass('blik-form-payment'))
                return validateBlik(form);
            else if(form.hasClass('cc-form-payment'))
                return validateCC(form);
            else if(form.hasClass('widget-form-payment'))
                return validateWidget(form);
            return true;
        });
    });
}
