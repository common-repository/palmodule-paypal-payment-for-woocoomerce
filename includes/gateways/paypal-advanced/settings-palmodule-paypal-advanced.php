<?php

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Settings for PayPal Pro Advanced Gateway.
 */
return $this->form_fields = array(
    'enabled' => array(
        'title' => __('Enable/Disable', 'palmodule-paypal-payment-for-woocoomerce'),
        'label' => __('Enable PayPal Pro Advanced Edition', 'palmodule-paypal-payment-for-woocoomerce'),
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
        'default' => __('Pay with your credit card.', 'palmodule-paypal-payment-for-woocoomerce'),
        'desc_tip' => true
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
    'invoice_prefix' => array(
        'title' => __('Invoice Prefix', 'palmodule-paypal-payment-for-woocoomerce'),
        'type' => 'text',
        'description' => __('Please enter a prefix for your invoice numbers. If you use your PayPal account for multiple stores ensure this prefix is unique as PayPal will not allow orders with the same invoice number.', 'palmodule-paypal-payment-for-woocoomerce'),
        'default' => 'WC-PayPal_AD',
        'desc_tip' => true,
    ),
    'testmode' => array(
        'title' => __('Test Mode', 'palmodule-paypal-payment-for-woocoomerce'),
        'label' => __('Enable PayPal Sandbox/Test Mode', 'palmodule-paypal-payment-for-woocoomerce'),
        'type' => 'checkbox',
        'description' => __('Place the payment gateway in development mode.', 'palmodule-paypal-payment-for-woocoomerce'),
        'default' => 'no',
        'desc_tip' => true
    ),
    'sandbox_paypal_partner' => array(
        'title' => __('Partner', 'palmodule-paypal-payment-for-woocoomerce'),
        'type' => 'text',
        'description' => __('The ID provided to you by the authorized PayPal Reseller who registered you
			for the Payflow SDK. If you purchased your account directly from PayPal, use PayPal or leave blank.', 'palmodule-paypal-payment-for-woocoomerce'),
        'default' => 'PayPal',
        'desc_tip' => true
    ),
    'sandbox_paypal_vendor' => array(
        'title' => __('Merchant Login', 'palmodule-paypal-payment-for-woocoomerce'),
        'type' => 'text',
        'description' => __('Your merchant login ID that you created when you registered for the account.', 'palmodule-paypal-payment-for-woocoomerce'),
        'default' => '',
        'desc_tip' => true
    ),
    'sandbox_paypal_user' => array(
        'title' => __('User (optional)', 'palmodule-paypal-payment-for-woocoomerce'),
        'type' => 'text',
        'description' => __('If you set up one or more additional users on the account, this value is the ID
			of the user authorized to process transactions. Otherwise, leave this field blank.', 'palmodule-paypal-payment-for-woocoomerce'),
        'default' => '',
        'desc_tip' => true
    ),
    'sandbox_paypal_password' => array(
        'title' => __('Password', 'palmodule-paypal-payment-for-woocoomerce'),
        'type' => 'password',
        'description' => __('The password that you defined while registering for the account.', 'palmodule-paypal-payment-for-woocoomerce'),
        'default' => '',
        'desc_tip' => true
    ),
    'paypal_partner' => array(
        'title' => __('Partner', 'palmodule-paypal-payment-for-woocoomerce'),
        'type' => 'text',
        'description' => __('The ID provided to you by the authorized PayPal Reseller who registered you
			for the Payflow SDK. If you purchased your account directly from PayPal, use PayPal or leave blank.', 'palmodule-paypal-payment-for-woocoomerce'),
        'default' => 'PayPal',
        'desc_tip' => true
    ),
    'paypal_vendor' => array(
        'title' => __('Merchant Login', 'palmodule-paypal-payment-for-woocoomerce'),
        'type' => 'text',
        'description' => __('Your merchant login ID that you created when you registered for the account.', 'palmodule-paypal-payment-for-woocoomerce'),
        'default' => '',
        'desc_tip' => true
    ),
    'paypal_user' => array(
        'title' => __('User (optional)', 'palmodule-paypal-payment-for-woocoomerce'),
        'type' => 'text',
        'description' => __('If you set up one or more additional users on the account, this value is the ID
			of the user authorized to process transactions. Otherwise, leave this field blank.', 'palmodule-paypal-payment-for-woocoomerce'),
        'default' => '',
        'desc_tip' => true
    ),
    'paypal_password' => array(
        'title' => __('Password', 'palmodule-paypal-payment-for-woocoomerce'),
        'type' => 'password',
        'description' => __('The password that you defined while registering for the account.', 'palmodule-paypal-payment-for-woocoomerce'),
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
            'S' => __('Capture', 'palmodule-paypal-payment-for-woocoomerce'),
            'A' => __('Authorize', 'palmodule-paypal-payment-for-woocoomerce')
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
        'description' => __('Log PayPal Pro (Advanced) events inside <code>woocommerce/logs/paypal-pro-advanced.txt</code>', 'palmodule-paypal-payment-for-woocoomerce'),
    )
);
