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

function changeOcLogo() {
    if(window.positionOcLogo === 0)
        window.positionOcLogo = 360;
    else
        window.positionOcLogo = 0;
    if(jQuery('input#edit-choose:checked').length) {
        var logo = jQuery('.dotpay-card-logo');
        logo.transition({ rotateY: window.positionOcLogo });
        logo.attr('src', logo.data('card-'+jQuery('#edit-saved-cards').val()));
    }
}

function setVisibilityOcLogo() {
    if(jQuery('input#edit-choose:checked').length) {
        jQuery('.dotpay-card-logo').show();
    } else {
        jQuery('.dotpay-card-logo').hide();
    }
    changeOcLogo();
}

function performActionOC() {
    setVisibilityOcLogo();
    if(!jQuery('input#edit-choose:checked').length)
        jQuery('select[name=saved_cards]').attr('disabled', 'disabled');
    else
        jQuery('select[name=saved_cards]').removeAttr('disabled');
}

if(jQuery != undefined) {
    jQuery(document).ready(function(){
        if(jQuery('select[name=saved_cards] option').length===0 || jQuery('select[name=saved_cards]').length===0)
            jQuery('input#edit-register').click();
        else
            jQuery('input#edit-choose').click();
        jQuery('input[name=oc_type]').change(performActionOC);
        jQuery('#edit-saved-cards').change(changeOcLogo);
        performActionOC();
    });
}