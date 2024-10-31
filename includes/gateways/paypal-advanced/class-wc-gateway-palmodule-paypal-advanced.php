<?php

/**
 * WC_Gateway_Palmodule_PayPal_Pro_Advanced class.
 *
 * @extends WC_Payment_Gateway
 */
class WC_Gateway_Palmodule_PayPal_Advanced extends WC_Payment_Gateway {

    public $api_request_handler;
    public static $log_enabled = false;
    public static $log = false;

    public function __construct() {
        $this->id = 'palmodule_paypal_advanced';
        $this->method_title = __('PayPal Advanced', 'palmodule-paypal-payment-for-woocoomerce');
        $this->method_description = __('PayPal Advanced works by adding credit card fields on the checkout and then sending the details to PayPal for verification.', 'palmodule-paypal-payment-for-woocoomerce');
        $this->icon = apply_filters('woocommerce_palmodule_paypal_advanced_icon', WP_PLUGIN_URL . "/" . plugin_basename(dirname(dirname(__FILE__))) . '/assets/images/cards.png');
        $this->has_fields = true;
        $this->supports = array(
            'products',
            'refunds'
        );
        $this->liveurl = 'https://payflowpro.paypal.com';
        $this->testurl = 'https://pilot-payflowpro.paypal.com';
        $this->allowed_currencies = apply_filters('woocommerce_palmodule_paypal_advanced_allowed_currencies', array('USD', 'EUR', 'GBP', 'CAD', 'JPY', 'AUD'));
        $this->init_form_fields();
        $this->init_settings();
        $this->icon = $this->get_option('card_icon', '');
        if (is_ssl()) {
            $this->icon = preg_replace("/^http:/i", "https:", $this->icon);
        }
        $this->icon = apply_filters('woocommerce_palmodule_paypal_advanced_icon', $this->icon);
        $this->title = $this->get_option('title');
        $this->description = $this->get_option('description');
        $this->enabled = $this->get_option('enabled');
        $this->testmode = $this->get_option('testmode') === "yes" ? true : false;
        if( $this->testmode ) {
            $this->paypal_vendor = $this->get_option('sandbox_paypal_vendor');
            $this->paypal_partner = $this->get_option('sandbox_paypal_partner', 'PayPal');
            $this->paypal_password = trim($this->get_option('sandbox_paypal_password'));
            $this->paypal_user = $this->get_option('sandbox_paypal_user', $this->paypal_vendor);
        } else {
            $this->paypal_vendor = $this->get_option('paypal_vendor');
            $this->paypal_partner = $this->get_option('paypal_partner', 'PayPal');
            $this->paypal_password = trim($this->get_option('paypal_password'));
            $this->paypal_user = $this->get_option('paypal_user', $this->paypal_vendor);
        }
        $this->debug = 'yes' === $this->get_option('debug', 'no');
        self::$log_enabled = $this->debug;
        $this->order_button_text = __('Enter payment details', 'palmodule-paypal-payment-for-woocoomerce');
        $this->soft_descriptor = str_replace(' ', '-', preg_replace('/[^A-Za-z0-9\-\.]/', '', $this->get_option('soft_descriptor', "")));
        $this->paymentaction = strtoupper($this->get_option('paymentaction', 'S'));
        $this->invoice_prefix = $this->get_option('invoice_prefix');
        add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options'));
        add_action('woocommerce_receipt_palmodule_paypal_advanced', array($this, 'receipt_page'));
        add_action('woocommerce_api_wc_gateway_palmodule_paypal_advanced', array($this, 'return_handler'));
    }

    public function init_form_fields() {
        try {
            $this->form_fields = include( 'settings-palmodule-paypal-advanced.php' );
        } catch (Exception $ex) {
            
        }
    }

    public function is_available() {
        if ($this->enabled === "yes") {
            if (!is_ssl() && !$this->testmode) {
                return false;
            }
            if (!in_array(get_option('woocommerce_currency'), $this->allowed_currencies)) {
                return false;
            }
            if (!$this->paypal_vendor || !$this->paypal_password) {
                return false;
            }
            return true;
        }
        return false;
    }

    public function payment_fields() {
        parent::payment_fields();
    }

    public function process_payment($order_id) {
        $order = new WC_Order($order_id);
        self::log('Processing order #' . $order_id);
        return array(
            'result' => 'success',
            'redirect' => $order->get_checkout_payment_url(true)
        );
    }

    public function init_request_api() {
        try {
            include_once( PALMODULE_PAYPAL_PAYMENT_FOR_WOOCOOMERCE_PLUGIN_DIR . '/includes/gateways/paypal-advanced/class-wc-gateway-palmodule-paypal-advanced-api-handler.php' );
            $this->api_request_handler = new WC_Gateway_Palmodule_PayPal_Advanced_API_Handler();
            $this->api_request_handler->gateway_settings = $this;
        } catch (Exception $ex) {
            self::log($ex->getMessage());
        }
    }

    public function receipt_page($order_id) {
        self::log('Receipt Page order #' . $order_id);
        $this->init_request_api();
        $this->api_request_handler->request_receipt_page($order_id);
    }

    public function return_handler() {
        self::log('Return Handler');
        $this->init_request_api();
        $this->api_request_handler->request_return_handler();
    }

    public function process_refund($order_id, $amount = null, $reason = '') {
        self::log('Process Refund order #' . $order_id);
        $this->init_request_api();
        return $this->api_request_handler->request_process_refund($order_id, $amount, $reason);
    }

    public static function log($message, $level = 'info') {
        if (self::$log_enabled) {
            if (empty(self::$log)) {
                self::$log = wc_get_logger();
            }
            self::$log->log($level, $message, array('source' => 'palmodule_paypal_advanced'));
        }
    }

}
