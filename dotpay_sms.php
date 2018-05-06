<?php

/*
  Plugin Name: Dotpay SMS
  Plugin URI: http://1street.pl
  Description: Extends WooCommerce by Adding Dotpay SMS Premium payment method
  Version: 1
  Author: Sebastian Weszler
  Author URI: http://1street.pl
 */

add_action('plugins_loaded', 'dotpay_sms_init', 0);

function dotpay_sms_init() {
    if (!class_exists('WC_Payment_Gateway'))
        return;
    include_once('dotpay_sms_inc.php');

    add_filter('woocommerce_payment_gateways', 'dotpay_sms_gateway');

    function dotpay_sms_gateway($methods) {
        $methods[] = 'DOTPAY_SMS';
        return $methods;
    }

}

add_filter('plugin_action_links_' . plugin_basename(__FILE__), 'dotpay_sms_action_links');

function dotpay_sms_action_links($links) {
    $plugin_links = array(
        '<a href="' . admin_url('admin.php?page=wc-settings&tab=checkout') . '">' . __('Settings', 'dotpay_sms') . '</a>'
    );

    return array_merge($plugin_links, $links);
}
