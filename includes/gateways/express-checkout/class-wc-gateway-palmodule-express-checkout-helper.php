<?php

if (!defined('ABSPATH')) {
    exit;
}

class WC_Gateway_Palmodule_PayPal_Express_Checkout_Helper {

    public $version;
    public $is_us_or_uk = false;

    public function __construct($version) {
        try {
            if (substr(get_option("woocommerce_default_country"), 0, 2) == 'US' || substr(get_option("woocommerce_default_country"), 0, 2) == 'GB') {
                $this->is_us_or_uk = true;
            }
            $this->version = $version;
            add_action('wp_head', array($this, 'palmodule_add_header_meta'), 0);
            add_action('woocommerce_after_add_to_cart_button', array($this, 'buy_now_button'), 10);
            add_action('woocommerce_proceed_to_checkout', array($this, 'buy_now_button'), 999);
            add_action('woocommerce_before_checkout_form', array($this, 'buy_now_button'), 999);
            add_action('wp_enqueue_scripts', array($this, 'ec_enqueue_scripts_product_page'), 0);
            add_action('woocommerce_cart_emptied', array($this, 'palmodule_maybe_clear_session_data'));
            add_action('woocommerce_available_payment_gateways', array($this, 'palmodule_checkout_page_disable_gateways'));
            add_action('woocommerce_checkout_billing', array($this, 'palmodule_express_checkout_auto_fillup_shipping_address'));
            add_filter('the_title', array($this, 'palmodule_woocommerce_page_title'), 99, 1);
        } catch (Exception $ex) {
            
        }
    }

    public function palmodule_express_checkout_auto_fillup_shipping_address() {
        $shipping_address = palmodule_get_session('palmodule_express_checkout_shipping_address');
        if (!empty($shipping_address)) {
            foreach ($shipping_address as $key => $value) {
                $_POST['billing_' . $key] = $value;
                $_POST['shipping_' . $key] = $value;
            }
        }
    }

    public function palmodule_add_header_meta() {
        echo '<meta http-equiv="X-UA-Compatible" content="IE=edge" />';
    }

    public function buy_now_button() {
        if (is_palmodule_express_checkout_ready_to_capture() == false && $this->palmodule_is_express_checkout_enable()) {
            echo '<div class="button alt" id="palmodule_express_checkout_paypal_button"></div>';
            if( $this->is_us_or_uk ) {
                echo '<div class="button alt" id="palmodule_express_checkout_paypal_cc_button"></div>';
            }
        }
    }

    
            
    public function ec_enqueue_scripts_product_page() {
        global $post;
        try {
            if (is_palmodule_express_checkout_ready_to_capture() == false && $this->palmodule_is_express_checkout_enable()) {
                $ENV_value = palmodule_get_option('palmodule_paypal_express', 'sandbox', 'yes');
                $ENV = ($ENV_value != 'yes') ? 'production' : 'sandbox';
                wp_enqueue_script('palmodule-in-context-checkout-js', 'https://www.paypalobjects.com/api/checkout.js', array(), null, true);
                wp_enqueue_script('palmodule-in-context-checkout-js-frontend', PALMODULE_PAYPAL_PAYMENT_FOR_WOOCOOMERCE_ASSET_URL . '/public/js/palmodule-paypal-payment-for-woocoomerce-in-context-checkout.js', array('jquery'), $this->version, true);
                wp_localize_script('palmodule-in-context-checkout-js-frontend', 'palmodule_in_content_param', array(
                    'CREATE_PAYMENT_URL' => esc_url(add_query_arg('palmodule_express_checkout_action', 'create_payment_url', WC()->api_request_url( 'WC_Gateway_Palmodule_PayPal_Express_Checkout' ))),
                    'CC_CREATE_PAYMENT_URL' => esc_url(add_query_arg( array( 'palmodule_express_checkout_action' => 'create_payment_url', 'is_palmodule_cc' => 'yes' ), WC()->api_request_url( 'WC_Gateway_Palmodule_PayPal_Express_Checkout' ) )),
                    'EXECUTE_PAYMENT_URL' => esc_url(add_query_arg('palmodule_express_checkout_action', 'checkout_payment_url', WC()->api_request_url( 'WC_Gateway_Palmodule_PayPal_Express_Checkout' ))),
                    'LOCALE' => self::get_button_locale_code(),
                    'GENERATE_NONCE' => wp_create_nonce('_palmodule_nonce_'),
                    'IS_PRODUCT' => is_product() ? "yes" : "no",
                    'POST_ID' => isset($post->ID) ? $post->ID : '',
                    'CANCEL_URL' => esc_url(add_query_arg('palmodule_express_checkout_action', 'cancel_url', WC()->api_request_url( 'WC_Gateway_Palmodule_PayPal_Express_Checkout' ))),
                    'SIZE' => palmodule_get_option('palmodule_paypal_express', 'button_size', 'small'),
                    'SHAPE' => palmodule_get_option('palmodule_paypal_express', 'button_shape', 'pill'),
                    'COLOR' => palmodule_get_option('palmodule_paypal_express', 'button_color', 'gold'),
                    'ENV' => $ENV
                ));
            }
        } catch (Exception $ex) {
            
        }
    }

    public static function get_button_locale_code() {
        $_supportedLocale = array(
            'en_US', 'fr_XC', 'es_XC', 'zh_XC', 'en_AU', 'de_DE', 'nl_NL',
            'fr_FR', 'pt_BR', 'fr_CA', 'zh_CN', 'ru_RU', 'en_GB', 'zh_HK',
            'he_IL', 'it_IT', 'ja_JP', 'pl_PL', 'pt_PT', 'es_ES', 'sv_SE', 'zh_TW', 'tr_TR'
        );
        $wpml_locale = self::palmodule_ec_get_wpml_locale();
        if ($wpml_locale) {
            if (in_array($wpml_locale, $_supportedLocale)) {
                return $wpml_locale;
            }
        }
        $locale = get_locale();
        if (!in_array($locale, $_supportedLocale)) {
            $locale = 'en_US';
        }
        return $locale;
    }

    public static function palmodule_ec_get_wpml_locale() {
        $locale = false;
        if (defined('ICL_LANGUAGE_CODE') && function_exists('icl_object_id')) {
            global $sitepress;
            if (isset($sitepress)) { // avoids a fatal error with Polylang
                $locale = $sitepress->get_current_language();
            } else if (function_exists('pll_current_language')) { // adds Polylang support
                $locale = pll_current_language('locale'); //current selected language requested on the broswer
            } else if (function_exists('pll_default_language')) {
                $locale = pll_default_language('locale'); //default lanuage of the blog
            }
        }
        return $locale;
    }

    public function palmodule_checkout_page_disable_gateways($gateways) {
        if (is_palmodule_express_checkout_ready_to_capture()) {
            foreach ($gateways as $id => $gateway) {
                if ($id !== 'palmodule_paypal_express') {
                    unset($gateways[$id]);
                }
            }
        }
        return $gateways;
    }

    public function palmodule_maybe_clear_session_data() {
        palmodule_maybe_clear_session_data();
    }
    
    public function palmodule_woocommerce_page_title($page_title) {
            if (!function_exists('WC')) {
                return false;
            }
            if (sizeof(WC()->session) == 0) {
                return $page_title;
            }
            if ('Checkout' == $page_title && is_palmodule_express_checkout_ready_to_capture()) {
                remove_filter('the_title', array($this, 'palmodule_woocommerce_page_title'));
                return 'Review Order';
            } else {
                return $page_title;
            }
        }
        
        
        public function palmodule_is_express_checkout_enable() {
            if(!class_exists('WC_Gateway_Palmodule_PayPal_Express_Checkout')) {
                include_once 'WC_Gateway_Palmodule_PayPal_Express_Checkout';
            }
            $palmodule_express_checkout_obj = new WC_Gateway_Palmodule_PayPal_Express_Checkout();
            if( $palmodule_express_checkout_obj->is_available() ) {
                return true;
            } else {
                return false;
            }
        }

}
