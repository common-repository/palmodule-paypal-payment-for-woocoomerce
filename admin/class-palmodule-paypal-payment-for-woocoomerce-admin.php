<?php

/**
 * The admin-specific functionality of the plugin.
 *
 * @link       https://profiles.wordpress.org/palmoduledev
 * @since      1.0.0
 *
 * @package    Palmodule_PayPal_Payment_For_Woocoomerce
 * @subpackage Palmodule_PayPal_Payment_For_Woocoomerce/admin
 */

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    Palmodule_PayPal_Payment_For_Woocoomerce
 * @subpackage Palmodule_PayPal_Payment_For_Woocoomerce/admin
 * @author     palmoduledev <palmoduledev@gmail.com>
 */
class Palmodule_PayPal_Payment_For_Woocoomerce_Admin {

    /**
     * The ID of this plugin.
     *
     * @since    1.0.0
     * @access   private
     * @var      string    $plugin_name    The ID of this plugin.
     */
    private $plugin_name;

    /**
     * The version of this plugin.
     *
     * @since    1.0.0
     * @access   private
     * @var      string    $version    The current version of this plugin.
     */
    private $version;

    /**
     * Initialize the class and set its properties.
     *
     * @since    1.0.0
     * @param      string    $plugin_name       The name of this plugin.
     * @param      string    $version    The version of this plugin.
     */
    public $subscription_support_enabled = false;
    public $pre_order_enabled = false;

    public function __construct($plugin_name, $version) {

        $this->plugin_name = $plugin_name;
        $this->version = $version;
    }

    /**
     * Register the stylesheets for the admin area.
     *
     * @since    1.0.0
     */
    public function enqueue_styles() {

        /**
         * This function is provided for demonstration purposes only.
         *
         * An instance of this class should be passed to the run() function
         * defined in Palmodule_PayPal_Payment_For_Woocoomerce_Loader as all of the hooks are defined
         * in that particular class.
         *
         * The Palmodule_PayPal_Payment_For_Woocoomerce_Loader will then create the relationship
         * between the defined hooks and the functions defined in this
         * class.
         */
        wp_enqueue_style($this->plugin_name, plugin_dir_url(__FILE__) . 'css/palmodule-paypal-payment-for-woocoomerce-admin.css', array(), $this->version, 'all');
    }

    /**
     * Register the JavaScript for the admin area.
     *
     * @since    1.0.0
     */
    public function enqueue_scripts() {

        /**
         * This function is provided for demonstration purposes only.
         *
         * An instance of this class should be passed to the run() function
         * defined in Palmodule_PayPal_Payment_For_Woocoomerce_Loader as all of the hooks are defined
         * in that particular class.
         *
         * The Palmodule_PayPal_Payment_For_Woocoomerce_Loader will then create the relationship
         * between the defined hooks and the functions defined in this
         * class.
         */
        wp_enqueue_script($this->plugin_name, plugin_dir_url(__FILE__) . 'js/palmodule-paypal-payment-for-woocoomerce-admin.js', array('jquery'), $this->version, false);
    }

    public function init_palmodule_paypal_payment() {
        if (class_exists('WC_Payment_Gateway')) {
            require_once( PALMODULE_PAYPAL_PAYMENT_FOR_WOOCOOMERCE_PLUGIN_DIR . '/includes/gateways/express-checkout/class-wc-gateway-palmodule-express-checkout-helper.php' );
            require_once( PALMODULE_PAYPAL_PAYMENT_FOR_WOOCOOMERCE_PLUGIN_DIR . '/includes/gateways/paypal-rest/class-wc-gateway-palmodule-paypal-rest.php' );
            require_once( PALMODULE_PAYPAL_PAYMENT_FOR_WOOCOOMERCE_PLUGIN_DIR . '/includes/gateways/braintree/class-wc-gateway-palmodule-braintree.php' );
            new WC_Gateway_Palmodule_PayPal_Express_Checkout_Helper($this->version);
            require_once( PALMODULE_PAYPAL_PAYMENT_FOR_WOOCOOMERCE_PLUGIN_DIR . '/includes/gateways/express-checkout/class-wc-gateway-palmodule-express-checkout.php' );
            require_once( PALMODULE_PAYPAL_PAYMENT_FOR_WOOCOOMERCE_PLUGIN_DIR . '/includes/gateways/paypal-pro/class-wc-gateway-palmodule-paypal-pro.php' );
            require_once( PALMODULE_PAYPAL_PAYMENT_FOR_WOOCOOMERCE_PLUGIN_DIR . '/includes/gateways/paypal-pro-payflow/class-wc-gateway-palmodule-paypal-pro-payflow.php' );
            require_once( PALMODULE_PAYPAL_PAYMENT_FOR_WOOCOOMERCE_PLUGIN_DIR . '/includes/gateways/paypal-advanced/class-wc-gateway-palmodule-paypal-advanced.php' );
            if ($this->palmodule_is_subscription_or_pre_order_enabled()) {
                require_once( PALMODULE_PAYPAL_PAYMENT_FOR_WOOCOOMERCE_PLUGIN_DIR . '/includes/gateways/paypal-rest/class-wc-gateway-palmodule-paypal-rest-addons.php' );
            } 
        }
    }

    public function palmodule_pal_payment_for_woo_add_payment_method_class($methods) {
        if (class_exists('WC_Payment_Gateway')) {
            if ($this->palmodule_is_subscription_or_pre_order_enabled()) {
               $methods[] = 'WC_Gateway_Palmodule_PayPal_Rest_Addons';
               $methods[] = 'WC_Gateway_Palmodule_Braintree';
               
            } else {
                $methods[] = 'WC_Gateway_Palmodule_PayPal_Rest';
                $methods[] = 'WC_Gateway_Palmodule_Braintree';
                $methods[] = 'WC_Gateway_Palmodule_PayPal_Express_Checkout';
                $methods[] = 'WC_Gateway_Palmodule_PayPal_Pro';
                $methods[] = 'WC_Gateway_Palmodule_PayPal_Pro_Payflow';
                $methods[] = 'WC_Gateway_Palmodule_PayPal_Advanced';
            }
            return $methods;
        }
        
        
    }

    public function palmodule_is_subscription_or_pre_order_enabled() {
        if (class_exists('WC_Subscriptions_Order') && function_exists('wcs_create_renewal_order')) {
            $this->subscription_support_enabled = true;
        }
        if (class_exists('WC_Pre_Orders_Order')) {
            $this->pre_order_enabled = true;
        }
        $load_addons = ( $this->subscription_support_enabled || $this->pre_order_enabled );
        if ($load_addons == false) {
            return false;
        } else {
            return true;
        }
    }

}
