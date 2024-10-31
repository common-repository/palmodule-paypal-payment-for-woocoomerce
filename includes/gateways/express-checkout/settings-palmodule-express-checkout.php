<?php
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Settings for PayPal Rest Gateway.
 */
$require_ssl = '';
if (wc_checkout_is_https() == false) {
    $require_ssl = __('This image requires an SSL host.  Please upload your image to <a target="_blank" href="http://www.sslpic.com">www.sslpic.com</a> and enter the image URL here.', 'paypal-for-woocommerce');
}
return $this->form_fields = array(
    'enabled' => array(
        'title' => __('Enable/Disable', 'palmodule-paypal-payment-for-woocoomerce'),
        'label' => __('Enable PayPal Express', 'palmodule-paypal-payment-for-woocoomerce'),
        'type' => 'checkbox',
        'description' => '',
        'default' => 'no'
    ),
    'title' => array(
        'title' => __('Title', 'palmodule-paypal-payment-for-woocoomerce'),
        'type' => 'text',
        'description' => __('This controls the title which the user sees during checkout.', 'palmodule-paypal-payment-for-woocoomerce'),
        'default' => __('PayPal Express', 'palmodule-paypal-payment-for-woocoomerce'),
        'desc_tip' => true,
    ),
    'description' => array(
        'title' => __('Description', 'palmodule-paypal-payment-for-woocoomerce'),
        'type' => 'textarea',
        'description' => __('This controls the description which the user sees during checkout.', 'palmodule-paypal-payment-for-woocoomerce'),
        'default' => __("Pay via PayPal; you can pay with your credit card if you don't have a PayPal account", 'palmodule-paypal-payment-for-woocoomerce'),
        'desc_tip' => true,
    ),
    'account_settings' => array(
        'title' => __('Account Settings', 'palmodule-paypal-payment-for-woocoomerce'),
        'type' => 'title',
        'description' => '',
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
    'display_settings' => array(
        'title' => __('Display Settings (Optional)', 'palmodule-paypal-payment-for-woocoomerce'),
        'type' => 'title',
        'description' => __('Customize the appearance of Express Checkout in your store.', 'palmodule-paypal-payment-for-woocoomerce'),
    ),
    'page_style' => array(
        'title' => __('Page Style', 'palmodule-paypal-payment-for-woocoomerce'),
        'type' => 'text',
        'description' => __('Optionally enter the name of the page style you wish to use. These are defined within your PayPal account.', 'palmodule-paypal-payment-for-woocoomerce'),
        'default' => '',
        'desc_tip' => true,
        'placeholder' => __('Optional', 'palmodule-paypal-payment-for-woocoomerce'),
    ),
    'brand_name' => array(
        'title' => __('Brand Name', 'palmodule-paypal-payment-for-woocoomerce'),
        'type' => 'text',
        'description' => __('This controls what users see as the brand / company name on PayPal review pages.', 'palmodule-paypal-payment-for-woocoomerce'),
        'default' => __(get_bloginfo('name'), 'palmodule-paypal-payment-for-woocoomerce'),
        'desc_tip' => true,
    ),
    'checkout_logo' => array(
        'title' => __('PayPal Checkout Logo Image(190x60)', 'palmodule-paypal-payment-for-woocoomerce'),
        'type' => 'text',
        'description' => __('This controls what users see as the logo on PayPal review pages. ', 'palmodule-paypal-payment-for-woocoomerce') . $require_ssl,
        'default' => '',
        'desc_tip' => true,
        'placeholder' => __('Optional', 'palmodule-paypal-payment-for-woocoomerce'),
    ),
    'checkout_logo_hdrimg' => array(
        'title' => __('Header Image (750x90)', 'palmodule-paypal-payment-for-woocoomerce'),
        'type' => 'text',
        'description' => __('This controls what users see as the header banner on PayPal review pages. ', 'palmodule-paypal-payment-for-woocoomerce') . $require_ssl,
        'default' => '',
        'desc_tip' => true,
        'placeholder' => __('Optional', 'palmodule-paypal-payment-for-woocoomerce'),
    ),
    
    'show_on_cart' => array(
        'title' => __('Cart Page', 'palmodule-paypal-payment-for-woocoomerce'),
        'label' => __('Show Express Checkout button on shopping cart page.', 'palmodule-paypal-payment-for-woocoomerce'),
        'type' => 'checkbox',
        'default' => 'yes'
    ),
    'button_position' => array(
        'title' => __('Cart Button Position', 'palmodule-paypal-payment-for-woocoomerce'),
        'label' => __('Where to display PayPal Express Checkout button(s).', 'palmodule-paypal-payment-for-woocoomerce'),
        'class' => 'wc-enhanced-select',
        'description' => __('Set where to display the PayPal Express Checkout button(s).'),
        'type' => 'select',
        'options' => array(
            'top' => 'At the top, above the shopping cart details.',
            'bottom' => 'At the bottom, below the shopping cart details.',
            'both' => 'Both at the top and bottom, above and below the shopping cart details.'
        ),
        'default' => 'bottom',
        'desc_tip' => true,
    ),
    'show_on_product_page' => array(
        'title' => __('Product Page', 'palmodule-paypal-payment-for-woocoomerce'),
        'type' => 'checkbox',
        'label' => __('Show the Express Checkout button on product detail pages.', 'palmodule-paypal-payment-for-woocoomerce'),
        'default' => 'no',
        'description' => sprintf(__('Allows customers to checkout using PayPal directly from a product page.')),
        'desc_tip' => false,
    ),
    'paypal_account_optional' => array(
        'title' => __('PayPal Account Optional', 'palmodule-paypal-payment-for-woocoomerce'),
        'type' => 'checkbox',
        'label' => __('Allow customers to checkout without a PayPal account using their credit card.', 'palmodule-paypal-payment-for-woocoomerce'),
        'default' => 'no',
        'description' => __('PayPal Account Optional must be turned on in your PayPal account profile under Website Preferences.', 'palmodule-paypal-payment-for-woocoomerce'),
        'desc_tip' => true,
    ),
    'landing_page' => array(
        'title' => __('Landing Page', 'palmodule-paypal-payment-for-woocoomerce'),
        'type' => 'select',
        'class' => 'wc-enhanced-select',
        'description' => __('Type of PayPal page to display.', 'palmodule-paypal-payment-for-woocoomerce'),
        'default' => 'Login',
        'desc_tip' => true,
        'options' => array(
            'Billing' => _x('Billing (Non-PayPal account)', 'Type of PayPal page', 'palmodule-paypal-payment-for-woocoomerce'),
            'Login' => _x('Login (PayPal account login)', 'Type of PayPal page', 'palmodule-paypal-payment-for-woocoomerce'),
        ),
    ),
    'checkout_skip_text' => array(
        'title' => __('Express Checkout Message', 'palmodule-paypal-payment-for-woocoomerce'),
        'type' => 'text',
        'description' => __('This message will be displayed next to the PayPal Express Checkout button at the top of the checkout page.'),
        'default' => __('Skip the checkout form and pay faster with PayPal!', 'palmodule-paypal-payment-for-woocoomerce'),
        'desc_tip' => true,
    ),
    'button_styles' => array(
        'title' => __('Express Checkout Custom Button Styles', 'palmodule-paypal-payment-for-woocoomerce'),
        'type' => 'title',
        'description' => 'Customize your PayPal button with colors, sizes and shapes.',
    ),
    'button_size' => array(
        'title' => __('Button Size', 'palmodule-paypal-payment-for-woocoomerce'),
        'type' => 'select',
        'class' => 'wc-enhanced-select',
        'description' => __('Type of PayPal Button Size (small | medium | responsive).', 'palmodule-paypal-payment-for-woocoomerce'),
        'default' => 'small',
        'desc_tip' => true,
        'options' => array(
            'small' => __('Small', 'palmodule-paypal-payment-for-woocoomerce'),
            'medium' => __('Medium', 'palmodule-paypal-payment-for-woocoomerce'),
            'responsive' => __('Responsive', 'palmodule-paypal-payment-for-woocoomerce'),
        ),
    ),
    'button_shape' => array(
        'title' => __('Button Shape', 'palmodule-paypal-payment-for-woocoomerce'),
        'type' => 'select',
        'class' => 'wc-enhanced-select',
        'description' => __('Type of PayPal Button Shape (pill | rect).', 'palmodule-paypal-payment-for-woocoomerce'),
        'default' => 'pill',
        'desc_tip' => true,
        'options' => array(
            'pill' => __('Pill', 'palmodule-paypal-payment-for-woocoomerce'),
            'rect' => __('Rect', 'palmodule-paypal-payment-for-woocoomerce')
        ),
    ),
    'button_color' => array(
        'title' => __('Button Color', 'palmodule-paypal-payment-for-woocoomerce'),
        'type' => 'select',
        'class' => 'wc-enhanced-select',
        'description' => __('Type of PayPal Button Color (gold | blue | silver).', 'palmodule-paypal-payment-for-woocoomerce'),
        'default' => 'gold',
        'desc_tip' => true,
        'options' => array(
            'gold' => __('Gold', 'palmodule-paypal-payment-for-woocoomerce'),
            'blue' => __('Blue', 'palmodule-paypal-payment-for-woocoomerce'),
            'silver' => __('Silver', 'palmodule-paypal-payment-for-woocoomerce')
        ),
    ),
    'advanced' => array(
        'title' => __('Advanced Settings (Optional)', 'palmodule-paypal-payment-for-woocoomerce'),
        'type' => 'title',
        'description' => '',
    ),
    'invoice_id_prefix' => array(
        'title' => __('Invoice ID Prefix', 'palmodule-paypal-payment-for-woocoomerce'),
        'type' => 'text',
        'description' => __('Add a prefix to the invoice ID sent to PayPal. This can resolve duplicate invoice problems when working with multiple websites on the same PayPal account.', 'palmodule-paypal-payment-for-woocoomerce'),
        'desc_tip' => true,
        'default' => 'WC-EC'
    ),
//    'enable_tokenized_payments' => array(
//        'title' => __('Enable Tokenized Payments', 'palmodule-paypal-payment-for-woocoomerce'),
//        'label' => __('Enable Tokenized Payments', 'palmodule-paypal-payment-for-woocoomerce'),
//        'type' => 'checkbox',
//        'description' => __('Allow buyers to securely save payment details to their account for quick checkout / auto-ship orders in the future.', 'palmodule-paypal-payment-for-woocoomerce'),
//        'default' => 'no',
//        'class' => 'enable_tokenized_payments'
//    ),
//    'skip_final_review' => array(
//        'title' => __('Skip Final Review', 'palmodule-paypal-payment-for-woocoomerce'),
//        'label' => __('Enables the option to skip the final review page.', 'palmodule-paypal-payment-for-woocoomerce'),
//        'description' => __('By default, users will be returned from PayPal and presented with a final review page which includes shipping and tax in the order details.  Enable this option to eliminate this page in the checkout process.'),
//        'type' => 'checkbox',
//        'default' => 'no'
//    ),
    'paymentaction' => array(
        'title' => __('Payment Action', 'palmodule-paypal-payment-for-woocoomerce'),
        'type' => 'select',
        'class' => 'wc-enhanced-select',
        'description' => __('Choose whether you wish to capture funds immediately or authorize payment only.', 'palmodule-paypal-payment-for-woocoomerce'),
        'default' => 'sale',
        'desc_tip' => true,
        'options' => array(
            'Sale' => __('Sale', 'palmodule-paypal-payment-for-woocoomerce'),
            'Authorization' => __('Authorization', 'palmodule-paypal-payment-for-woocoomerce'),
            'Order' => __('Order', 'palmodule-paypal-payment-for-woocoomerce')
        ),
    ),
    'debug' => array(
        'title' => __('Debug', 'palmodule-paypal-payment-for-woocoomerce'),
        'type' => 'checkbox',
        'label' => sprintf(__('Enable logging<code>%s</code>', 'palmodule-paypal-payment-for-woocoomerce'), version_compare(WC_VERSION, '3.0', '<') ? wc_get_log_file_path('paypal_express') : WC_Log_Handler_File::get_log_file_path('paypal_express')),
        'default' => 'no'
    )
);


