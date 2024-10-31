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
        'label' => __('Enable Braintree Payment Gateway', 'palmodule-paypal-payment-for-woocoomerce'),
        'type' => 'checkbox',
        'description' => '',
        'default' => 'no'
    ),
    'title' => array(
        'title' => __('Title', 'palmodule-paypal-payment-for-woocoomerce'),
        'type' => 'text',
        'description' => __('This controls the title which the user sees during checkout.', 'palmodule-paypal-payment-for-woocoomerce'),
        'default' => __('Braintree (Credit Card/PayPal)', 'palmodule-paypal-payment-for-woocoomerce'),
        'desc_tip' => true
    ),
    'description' => array(
        'title' => __('Description', 'palmodule-paypal-payment-for-woocoomerce'),
        'type' => 'textarea',
        'description' => __('This controls the description which the user sees during checkout.', 'palmodule-paypal-payment-for-woocoomerce'),
        'default' => 'Pay securely with your Credit Card/PayPal.',
        'desc_tip' => true
    ),
    'invoice_prefix' => array(
        'title' => __('Invoice Prefix', 'palmodule-paypal-payment-for-woocoomerce'),
        'type' => 'text',
        'description' => __('Please enter a prefix for your invoice numbers. If you use your PayPal account for multiple stores ensure this prefix is unique as PayPal will not allow orders with the same invoice number.', 'palmodule-paypal-payment-for-woocoomerce'),
        'default' => 'WC-BR',
        'desc_tip' => true,
    ),
    'sandbox' => array(
        'title' => __('Sandbox', 'palmodule-paypal-payment-for-woocoomerce'),
        'label' => __('Enable Sandbox Mode', 'palmodule-paypal-payment-for-woocoomerce'),
        'type' => 'checkbox',
        'description' => __('Place the payment gateway in sandbox mode using sandbox API keys (real payments will not be taken).', 'palmodule-paypal-payment-for-woocoomerce'),
        'default' => 'yes'
    ),
    'sandbox_public_key' => array(
        'title' => __('Sandbox Public Key', 'palmodule-paypal-payment-for-woocoomerce'),
        'type' => 'password',
        'description' => __('Get your API keys from your Braintree account.', 'palmodule-paypal-payment-for-woocoomerce'),
        'default' => '',
        'desc_tip' => true
    ),
    'sandbox_private_key' => array(
        'title' => __('Sandbox Private Key', 'palmodule-paypal-payment-for-woocoomerce'),
        'type' => 'password',
        'description' => __('Get your API keys from your Braintree account.', 'palmodule-paypal-payment-for-woocoomerce'),
        'default' => '',
        'desc_tip' => true
    ),
    'sandbox_merchant_id' => array(
        'title' => __('Sandbox Merchant ID', 'palmodule-paypal-payment-for-woocoomerce'),
        'type' => 'password',
        'description' => __('Get your API keys from your Braintree account.', 'palmodule-paypal-payment-for-woocoomerce'),
        'default' => '',
        'desc_tip' => true
    ),
    'live_public_key' => array(
        'title' => __('Live Public Key', 'palmodule-paypal-payment-for-woocoomerce'),
        'type' => 'password',
        'description' => __('Get your API keys from your Braintree account.', 'palmodule-paypal-payment-for-woocoomerce'),
        'default' => '',
        'desc_tip' => true
    ),
    'live_private_key' => array(
        'title' => __('Live Private Key', 'palmodule-paypal-payment-for-woocoomerce'),
        'type' => 'password',
        'description' => __('Get your API keys from your Braintree account.', 'palmodule-paypal-payment-for-woocoomerce'),
        'default' => '',
        'desc_tip' => true
    ),
    'live_merchant_id' => array(
        'title' => __('Live Merchant ID', 'palmodule-paypal-payment-for-woocoomerce'),
        'type' => 'password',
        'description' => __('Get your API keys from your Braintree account.', 'palmodule-paypal-payment-for-woocoomerce'),
        'default' => '',
        'desc_tip' => true
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
        'default' => 'yes',
        'description' => sprintf(__('Log PayPal/Braintree events, inside <code>%s</code>', 'palmodule-paypal-payment-for-woocoomerce'), wc_get_log_file_path('braintree'))
    )
);
