<?php
if (!defined('ABSPATH')) {
    exit;
}

class WC_Gateway_Palmodule_PayPal_Express_Checkout extends WC_Payment_Gateway {

    public static $log_enabled = false;
    public static $log = false;
    public $card_data;
    public $rest_api_handler;

    public function __construct() {
        try {
            $this->id = 'palmodule_paypal_express';
            $this->method_title = __('PayPal Express Checkout ', 'paypal-for-woocommerce');
            $this->woocommerce_paypal_supported_currencies = array('AUD', 'BRL', 'CAD', 'MXN', 'NZD', 'HKD', 'SGD', 'USD', 'EUR', 'JPY', 'NOK', 'CZK', 'DKK', 'HUF', 'ILS', 'MYR', 'PHP', 'PLN', 'SEK', 'CHF', 'TWD', 'THB', 'GBP');
            $this->method_description = __('Increase sales by using PayPal express checkout to accept payments.', 'paypal-for-woocommerce');
            $this->has_fields = false;
            $this->supports = array(
                'products',
                'refunds'
            );
            $this->init_form_fields();
            $this->init_settings();
            $this->title = $this->get_option('title');
            $this->description = $this->get_option('description');
            $this->testmode = 'yes' === $this->get_option('sandbox', 'yes');
            $this->debug = 'yes' === $this->get_option('debug', 'no');
            $this->invoice_prefix = $this->get_option('invoice_id_prefix');
            self::$log_enabled = $this->debug;
            if ($this->testmode) {
                $this->rest_client_id = $this->get_option('rest_client_id_sandbox', false);
                $this->rest_secret_id = $this->get_option('rest_secret_id_sandbox', false);
            } else {
                $this->rest_client_id = $this->get_option('rest_client_id_live', false);
                $this->rest_secret_id = $this->get_option('rest_secret_id_live', false);
            }
            add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options'));
            if (!has_action('woocommerce_api_' . strtolower('WC_Gateway_Palmodule_PayPal_Express_Checkout'))) {
                add_action('woocommerce_api_' . strtolower('WC_Gateway_Palmodule_PayPal_Express_Checkout'), array($this, 'handle_wc_api'));
            }
        } catch (Exception $ex) {
            
        }
    }

    public function admin_options() {
        ?>
        <h3><?php _e('PayPal Express Checkout', 'paypal-for-woocommerce'); ?></h3>
        <p><?php _e($this->method_description, 'paypal-for-woocommerce'); ?></p>
        <table class="form-table"><?php $this->generate_settings_html(); ?></table>
        <?php
    }

    public function init_form_fields() {
        try {
            $this->form_fields = include( 'settings-palmodule-express-checkout.php' );
        } catch (Exception $ex) {
            
        }
    }

    public function handle_wc_api() {
        try {
            $this->init_api();
            if (!empty($_REQUEST['palmodule_express_checkout_action'])) {
                $request_name = $_REQUEST['palmodule_express_checkout_action'];
                switch ($request_name) {
                    case 'create_payment_url': {
                            $this->palmodule_product_add_to_cart();
                            $this->rest_api_handler->create_payment_url();
                            break;
                        }
                    case 'execute_payment_url': {
                            $this->rest_api_handler->palmodule_execute_payments();
                            break;
                        }
                    case 'return_url': {
                            $this->rest_api_handler->palmodule_return_url();
                            break;
                        }
                    case 'cancel_url': {
                            $this->rest_api_handler->palmodule_cancel_url();
                            break;
                        }
                    case 'checkout_payment_url' : {
                            $this->rest_api_handler->checkout_payment_url();
                        }
                }
            }
        } catch (Exception $ex) {
            
        }
    }

    public function palmodule_product_add_to_cart() {
        if (!wp_verify_nonce($_POST['nonce'], '_palmodule_nonce_')) {
            wp_die(__('Cheatin&#8217; huh?', 'woocommerce-gateway-paypal-express-checkout'));
        }
        if( !isset($_POST['is_add_to_cart']) || $_POST['is_add_to_cart'] == 'no') {
            return false;
        }
        if (!isset($_POST['product_id']) && empty($_POST['product_id'])) {
            return false;
        }
        if (!defined('WOOCOMMERCE_CART')) {
            define('WOOCOMMERCE_CART', true);
        }
        if (!defined('WOOCOMMERCE_CHECKOUT')) {
            define('WOOCOMMERCE_CHECKOUT', true);
        }
        WC()->shipping->reset_shipping();
        $product = wc_get_product($_POST['product_id']);
        $qty = !isset($_POST['qty']) ? 1 : absint($_POST['qty']);
        if ($product->is_type('variable')) {
            $attributes = array_map('wc_clean', $_POST['attributes']);
            if (version_compare(WC_VERSION, '3.0', '<')) {
                $variation_id = $product->get_matching_variation($attributes);
            } else {
                $data_store = WC_Data_Store::load('product');
                $variation_id = $data_store->find_matching_product_variation($product, $attributes);
            }
            WC()->cart->add_to_cart($product->get_id(), $qty, $variation_id, $attributes);
        } elseif ($product->is_type('simple')) {
            WC()->cart->add_to_cart($product->get_id(), $qty);
        }
        WC()->cart->calculate_totals();
    }

    public function is_available() {
        if ($this->enabled === "yes") {
            if (!$this->rest_client_id || !$this->rest_secret_id) {
                return false;
            }
            if (!in_array(get_woocommerce_currency(), apply_filters('woocommerce_paypal_rest_api_supported_currencies', $this->woocommerce_paypal_supported_currencies))) {
                return false;
            }
            return true;
        }
        return false;
    }

    public function is_valid_for_use() {
        return in_array(get_woocommerce_currency(), apply_filters('woocommerce_paypal_rest_api_supported_currencies', $this->woocommerce_paypal_supported_currencies));
    }

    public function process_payment($order_id) {
        $order = new WC_Order($order_id);
        if (is_palmodule_express_checkout_ready_to_capture()) {
            $this->init_api();
            $response = $this->rest_api_handler->palmodule_execute_payments($order);
            if (!empty($response['state']) && $response['state'] == 'approved') {
                $order->payment_complete($response['id']);
                palmodule_maybe_clear_session_data();
                return array(
                    'result' => 'success',
                    'redirect' => $this->get_return_url($order)
                );
            } else {
                return array(
                    'result' => 'fail',
                    'redirect' => ''
                );
            }
        } else {
            
            $this->init_api();
            $approval_url = $this->rest_api_handler->create_payment_url($is_in_content = false);
            return array(
                    'result' => 'success',
                    'redirect' => $approval_url
                );
        }
    }

    public function init_api() {
        try {
            if(!class_exists('ComposerAutoloaderInited5cff5a8f8574b72d4f6a04d4c34a6e')) {
                include_once( PALMODULE_PAYPAL_PAYMENT_FOR_WOOCOOMERCE_PLUGIN_DIR . '/includes/php-library/paypal-rest/vendor/autoload.php' );
            }
            include_once( PALMODULE_PAYPAL_PAYMENT_FOR_WOOCOOMERCE_PLUGIN_DIR . '/includes/gateways/express-checkout/class-wc-gateway-palmodule-paypal-express-checkout-api-handler.php' );
            $this->rest_api_handler = new WC_Gateway_Palmodule_PayPal_Express_Checkout_API_Handler();
            $this->rest_api_handler->rest_client_id = $this->rest_client_id;
            $this->rest_api_handler->rest_secret_id = $this->rest_secret_id;
            $this->rest_api_handler->sandbox = $this->testmode;
            $this->rest_api_handler->payment_method = $this->id;
            $this->rest_api_handler->invoice_prefix = $this->invoice_prefix;
        } catch (Exception $ex) {
            self::log($ex->getMessage());
        }
    }

    public function process_refund($order_id, $amount = null, $reason = '') {
        try {
            $this->init_api();
            $response = $this->rest_api_handler->create_refund_request($order_id, $amount, $reason = '');
            return $response;
        } catch (Exception $ex) {
            self::log($ex->getMessage());
        }
    }

    public static function log($message, $level = 'info') {
        if (self::$log_enabled) {
            if (empty(self::$log)) {
                self::$log = wc_get_logger();
            }
            self::$log->log($level, $message, array('source' => 'palmodule_paypal_rest'));
        }
    }

    public function add_payment_method() {
        $this->init_api();
        $this->card_data = palmodule_get_posted_card($this->id);
        $response = $this->rest_api_handler->palmodule_add_payment_method($this->card_data);
        return $response;
    }

    public function process_pre_order($order_id, $used_payment_token) {
        if (WC_Pre_Orders_Order::order_requires_payment_tokenization($order_id)) {
            try {
                $order = wc_get_order($order_id);
                $this->init_api();
                if ($this->rest_api_handler->is_request_using_save_card_data($order_id) == true) {
                    $this->rest_api_handler->palmodule_set_card_token($order_id);
                } else {
                    $this->rest_api_handler->palmodule_set_card_data($card_data);
                    if ($this->rest_api_handler->is_save_card_data() == true) {
                        $this->rest_api_handler->palmodule_save_card_data_in_vault();
                    }
                }
                $this->rest_api_handler->save_payment_token($order, $this->rest_api_handler->restcreditcardid);
                $order->add_payment_token($this->rest_api_handler->restcreditcardid);
                WC()->cart->empty_cart();
                WC_Pre_Orders_Order::mark_order_as_pre_ordered($order);
                return array(
                    'result' => 'success',
                    'redirect' => $this->get_return_url($order),
                );
            } catch (Exception $e) {
                wc_add_notice($e->getMessage(), 'error');
                return;
            }
        } else {
            return parent::process_payment($order_id, $used_payment_token = true);
        }
    }

}
