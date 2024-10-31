<?php

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Settings for PayPal Rest Gateway.
 */
return $this->form_fields = array(
    'enabled' => array(
        'title' => __('Enable/Disable', 'palmodule-paypal-payment-for-woocoomerce'),
        'type' => 'checkbox',
        'label' => __('Enable PayPal Credit Card (REST)', 'palmodule-paypal-payment-for-woocoomerce'),
        'default' => 'no'
    ),
    'title' => array(
        'title' => __('Title', 'palmodule-paypal-payment-for-woocoomerce'),
        'type' => 'text',
        'description' => __('This controls the title which the user sees during checkout.', 'palmodule-paypal-payment-for-woocoomerce'),
        'default' => __('PayPal Credit Card (REST)', 'palmodule-paypal-payment-for-woocoomerce')
    ),
    'description' => array(
        'title' => __('Description', 'palmodule-paypal-payment-for-woocoomerce'),
        'type' => 'text',
        'description' => __('This controls the description which the user sees during checkout.', 'palmodule-paypal-payment-for-woocoomerce'),
        'default' => __('Pay with your credit card', 'palmodule-paypal-payment-for-woocoomerce')
    ),
    'invoice_prefix' => array(
        'title' => __('Invoice Prefix', 'palmodule-paypal-payment-for-woocoomerce'),
        'type' => 'text',
        'description' => __('Please enter a prefix for your invoice numbers. If you use your PayPal account for multiple stores ensure this prefix is unique as PayPal will not allow orders with the same invoice number.', 'palmodule-paypal-payment-for-woocoomerce'),
        'default' => 'WC-PCCR',
        'desc_tip' => true,
    ),
    'sandbox' => array(
        'title' => __('Sandbox Mode', 'palmodule-paypal-payment-for-woocoomerce'),
        'type' => 'checkbox',
        'label' => __('Enable PayPal Sandbox Mode', 'palmodule-paypal-payment-for-woocoomerce'),
        'default' => 'yes',
        'description' => sprintf(__('Place the payment gateway in development mode. Sign up for a developer account <a href="%s" target="_blank">here</a>', 'palmodule-paypal-payment-for-woocoomerce'), 'https://developer.paypal.com/'),
    ),
    'rest_client_id_sandbox' => array(
        'title' => __('Sandbox Client ID', 'palmodule-paypal-payment-for-woocoomerce'),
        'type' => 'password',
        'description' => 'Enter your Sandbox PayPal Rest API Client ID',
        'default' => ''
    ),
    'rest_secret_id_sandbox' => array(
        'title' => __('Sandbox Secret ID', 'palmodule-paypal-payment-for-woocoomerce'),
        'type' => 'password',
        'description' => __('Enter your Sandbox PayPal Rest API Secret ID.', 'palmodule-paypal-payment-for-woocoomerce'),
        'default' => ''
    ),
    'rest_client_id_live' => array(
        'title' => __('Live Client ID', 'palmodule-paypal-payment-for-woocoomerce'),
        'type' => 'password',
        'description' => 'Enter your PayPal Rest API Client ID',
        'default' => ''
    ),
    'rest_secret_id_live' => array(
        'title' => __('Live Secret ID', 'palmodule-paypal-payment-for-woocoomerce'),
        'type' => 'password',
        'description' => __('Enter your PayPal Rest API Secret ID.', 'palmodule-paypal-payment-for-woocoomerce'),
        'default' => ''
    ),
    'card_icon' => array(
        'title' => __('Card Icon', 'palmodule-paypal-payment-for-woocoomerce'),
        'type' => 'text',
        'default' => PALMODULE_PAYPAL_PAYMENT_FOR_WOOCOOMERCE_ASSET_URL . '/assets/images/cards.png',
        'class' => 'button_upload'
    ),
    'debug' => array(
        'title' => __('Debug Log', 'palmodule-paypal-payment-for-woocoomerce'),
        'type' => 'checkbox',
        'label' => __('Enable logging', 'palmodule-paypal-payment-for-woocoomerce'),
        'default' => 'no',
        'description' => sprintf(__('Log PayPal events, such as Secured Token requests, inside <code>%s</code>', 'palmodule-paypal-payment-for-woocoomerce'), wc_get_log_file_path('palmodule_paypal_rest')),
    ),
    'advanced' => array(
        'title' => __('Advanced options', 'woocommerce'),
        'type' => 'title',
        'description' => '',
    ),
    'enable_tokenized_payments' => array(
        'title' => __('Enable Tokenized Payments', 'palmodule-paypal-payment-for-woocoomerce'),
        'label' => __('Enable Tokenized Payments', 'palmodule-paypal-payment-for-woocoomerce'),
        'type' => 'checkbox',
        'description' => __('Allow buyers to securely save payment details to their account for quick checkout / auto-ship orders in the future.', 'palmodule-paypal-payment-for-woocoomerce'),
        'default' => 'no',
        'class' => ''
    ),
);
