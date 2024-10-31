<?php

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Settings for PayPal Pro Gateway.
 */
return $this->form_fields = array(
    'enabled' => array(
        'title' => __('Enable/Disable', 'palmodule-paypal-payment-for-woocoomerce'),
        'label' => __('Enable PayPal Pro', 'palmodule-paypal-payment-for-woocoomerce'),
        'type' => 'checkbox',
        'description' => '',
        'default' => 'no'
    ),
    'title' => array(
        'title' => __('Title', 'palmodule-paypal-payment-for-woocoomerce'),
        'type' => 'text',
        'description' => __('This controls the title which the user sees during checkout.', 'palmodule-paypal-payment-for-woocoomerce'),
        'default' => __('Credit card (PayPal)', 'palmodule-paypal-payment-for-woocoomerce'),
        'desc_tip' => true
    ),
    'description' => array(
        'title' => __('Description', 'palmodule-paypal-payment-for-woocoomerce'),
        'type' => 'text',
        'description' => __('This controls the description which the user sees during checkout.', 'palmodule-paypal-payment-for-woocoomerce'),
        'default' => __('Pay with your credit card via PayPal Website Payments Pro.', 'palmodule-paypal-payment-for-woocoomerce'),
        'desc_tip' => true
    ),
    'testmode' => array(
        'title' => __('Test Mode', 'palmodule-paypal-payment-for-woocoomerce'),
        'label' => __('Enable PayPal Sandbox/Test Mode', 'palmodule-paypal-payment-for-woocoomerce'),
        'type' => 'checkbox',
        'description' => __('Place the payment gateway in development mode.', 'palmodule-paypal-payment-for-woocoomerce'),
        'default' => 'no',
        'desc_tip' => true
    ),
    'sandbox_api_username' => array(
        'title' => __('Sandbox API Username', 'palmodule-paypal-payment-for-woocoomerce'),
        'type' => 'text',
        'description' => __('Get your API credentials from PayPal.', 'palmodule-paypal-payment-for-woocoomerce'),
        'default' => '',
        'desc_tip' => true
    ),
    'sandbox_api_password' => array(
        'title' => __('Sandbox API Password', 'palmodule-paypal-payment-for-woocoomerce'),
        'type' => 'text',
        'description' => __('Get your API credentials from PayPal.', 'palmodule-paypal-payment-for-woocoomerce'),
        'default' => '',
        'desc_tip' => true
    ),
    'sandbox_api_signature' => array(
        'title' => __('Sandbox API Signature', 'palmodule-paypal-payment-for-woocoomerce'),
        'type' => 'text',
        'description' => __('Get your API credentials from PayPal.', 'palmodule-paypal-payment-for-woocoomerce'),
        'default' => '',
        'desc_tip' => true
    ),
    'api_username' => array(
        'title' => __('API Username', 'palmodule-paypal-payment-for-woocoomerce'),
        'type' => 'text',
        'description' => __('Get your API credentials from PayPal.', 'palmodule-paypal-payment-for-woocoomerce'),
        'default' => '',
        'desc_tip' => true
    ),
    'api_password' => array(
        'title' => __('API Password', 'palmodule-paypal-payment-for-woocoomerce'),
        'type' => 'text',
        'description' => __('Get your API credentials from PayPal.', 'palmodule-paypal-payment-for-woocoomerce'),
        'default' => '',
        'desc_tip' => true
    ),
    'api_signature' => array(
        'title' => __('API Signature', 'palmodule-paypal-payment-for-woocoomerce'),
        'type' => 'text',
        'description' => __('Get your API credentials from PayPal.', 'palmodule-paypal-payment-for-woocoomerce'),
        'default' => '',
        'desc_tip' => true
    ),
    'paymentaction' => array(
        'title' => __('Payment Action', 'palmodule-paypal-payment-for-woocoomerce'),
        'type' => 'select',
        'description' => __('Choose whether you wish to capture funds immediately or authorize payment only.', 'palmodule-paypal-payment-for-woocoomerce'),
        'default' => 'sale',
        'desc_tip' => true,
        'options' => array(
            'sale' => __('Capture', 'palmodule-paypal-payment-for-woocoomerce'),
            'authorization' => __('Authorize', 'palmodule-paypal-payment-for-woocoomerce')
        )
    ),
    'send_items' => array(
        'title' => __('Send Item Details', 'palmodule-paypal-payment-for-woocoomerce'),
        'label' => __('Send Line Items to PayPal', 'palmodule-paypal-payment-for-woocoomerce'),
        'type' => 'checkbox',
        'description' => __('Sends line items to PayPal. If you experience rounding errors this can be disabled.', 'palmodule-paypal-payment-for-woocoomerce'),
        'default' => 'no',
        'desc_tip' => true
    ),
    'invoice_prefix' => array(
        'title' => __('Invoice Prefix', 'palmodule-paypal-payment-for-woocoomerce'),
        'type' => 'text',
        'description' => __('Please enter a prefix for your invoice numbers. If you use your PayPal account for multiple stores ensure this prefix is unique as PayPal will not allow orders with the same invoice number.', 'palmodule-paypal-payment-for-woocoomerce'),
        'default' => 'WC-PayPal_Pro',
        'desc_tip' => true,
    ),
    'soft_descriptor' => array(
        'title' => __('Soft Descriptor', 'palmodule-paypal-payment-for-woocoomerce'),
        'type' => 'text',
        'description' => __('(Optional) Information that is usually displayed in the account holder\'s statement, for example your website name. Only 23 alphanumeric characters can be included, including the special characters dash (-) and dot (.) . Asterisks (*) and spaces ( ) are NOT permitted.', 'palmodule-paypal-payment-for-woocoomerce'),
        'default' => '',
        'desc_tip' => true,
        'custom_attributes' => array(
            'maxlength' => 23,
            'pattern' => '[a-zA-Z0-9.-]+'
        )
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
        'desc_tip' => true,
        'description' => __('Log PayPal Pro events inside <code>woocommerce/logs/paypal-pro.txt</code>', 'palmodule-paypal-payment-for-woocoomerce'),
    )
);
