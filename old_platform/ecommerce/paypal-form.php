<?php
/**
 * Created by PhpStorm.
 * User: Davide
 * Date: 22/01/2017
 * Time: 12.48
 */
require_once '../config.php';

?>

    <form id="paypal" action="<?=PAYPAL_URL?>" method="POST">
        <input type="hidden" name="business" value="<?=PAYPAL_ACCOUNT?>">
        <input type="hidden" name="cmd" value="_xclick">
        <input type="hidden" name="item_name" value="{% trans 'ordine id:'|upper %} {{ order.order_id }}">
        <input type="hidden" name="no_shipping" value="1">
        <input type="hidden" name="no_note" value="0">
        <input TYPE="hidden" name="rm" value="2">
        <input type="hidden" name="shopping_url" value="<?=HUB_URL?>">
        <input type="hidden" name="image_url" value="<?=HUB_URL?>/img/logo_Tutor81.png">
        <input type="hidden" name="page_style" value="primary">
        <input TYPE="hidden" name="return" value="<?=HUB_URL?>/paypalpayed/?id=<?=$course_id?>">
        <input TYPE="hidden" name="cancel_return" value="http://{{ server }}/basket">
        <input TYPE="hidden" name="address_override" value="1">
        <input TYPE="hidden" name="address1" value="{{ order.billing_address|upper }}">
        <input TYPE="hidden" name="city" value="{{ order.billing_city|upper }}">
        <input TYPE="hidden" name="country" value="{{ order.billing_country|upper }}">
        <input TYPE="hidden" name="email" value="{{ order.user.email }}">
        <input TYPE="hidden" name="first_name" value="{{ order.user.first_name|upper }}">
        <input TYPE="hidden" name="last_name" value="{{ order.user.last_name|upper }}">
        <input TYPE="hidden" name="zip_code" value="{{ order.billing_zip_code|upper }}">
        <input TYPE="hidden" name="lc" value="{{ LANGUAGE_CODE|upper }}">
        <input TYPE="hidden" name="charset" value="utf-8">
        <input TYPE="hidden" name="night_ phone_a" value="{{ order.billing_phone }}">
        <input type="hidden" name="currency_code" value="EUR">
        <input type="hidden" name="amount" value="{{ order.value|safe|cut:'00' }}">
    </form>