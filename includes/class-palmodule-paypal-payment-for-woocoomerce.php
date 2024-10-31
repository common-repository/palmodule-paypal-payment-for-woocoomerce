<?php

/**
 * The file that defines the core plugin class
 *
 * A class definition that includes attributes and functions used across both the
 * public-facing side of the site and the admin area.
 *
 * @link       https://profiles.wordpress.org/palmoduledev
 * @since      1.0.0
 *
 * @package    Palmodule_PayPal_Payment_For_Woocoomerce
 * @subpackage Palmodule_PayPal_Payment_For_Woocoomerce/includes
 */

/**
 * The core plugin class.
 *
 * This is used to define internationalization, admin-specific hooks, and
 * public-facing site hooks.
 *
 * Also maintains the unique identifier of this plugin as well as the current
 * version of the plugin.
 *
 * @since      1.0.0
 * @package    Palmodule_PayPal_Payment_For_Woocoomerce
 * @subpackage Palmodule_PayPal_Payment_For_Woocoomerce/includes
 * @author     palmoduledev <palmoduledev@gmail.com>
 */
class Palmodule_PayPal_Payment_For_Woocoomerce {

    /**
     * The loader that's responsible for maintaining and registering all hooks that power
     * the plugin.
     *
     * @since    1.0.0
     * @access   protected
     * @var      Palmodule_PayPal_Payment_For_Woocoomerce_Loader    $loader    Maintains and registers all hooks for the plugin.
     */
    protected $loader;

    /**
     * The unique identifier of this plugin.
     *
     * @since    1.0.0
     * @access   protected
     * @var      string    $plugin_name    The string used to uniquely identify this plugin.
     */
    protected $plugin_name;

    /**
     * The current version of the plugin.
     *
     * @since    1.0.0
     * @access   protected
     * @var      string    $version    The current version of the plugin.
     */
    protected $version;

    /**
     * Define the core functionality of the plugin.
     *
     * Set the plugin name and the plugin version that can be used throughout the plugin.
     * Load the dependencies, define the locale, and set the hooks for the admin area and
     * the public-facing side of the site.
     *
     * @since    1.0.0
     */
    public function __construct() {

        $this->plugin_name = 'palmodule-paypal-payment-for-woocoomerce';
        $this->version = '1.0.8';

        $this->load_dependencies();
        $this->set_locale();
        $this->define_admin_hooks();
        $this->define_public_hooks();
        
         // register API endpoints
        add_action('init', array($this, 'add_endpoint'), 0);
        // handle paypal-ipn-for-wordpress-api endpoint requests
        add_action('parse_request', array($this, 'handle_api_requests'), 0);
        add_action('palmodule_paypal_payment_api_ipn', array($this, 'palmodule_paypal_payment_api_ipn'));
    }

    /**
     * Load the required dependencies for this plugin.
     *
     * Include the following files that make up the plugin:
     *
     * - Palmodule_PayPal_Payment_For_Woocoomerce_Loader. Orchestrates the hooks of the plugin.
     * - Palmodule_PayPal_Payment_For_Woocoomerce_i18n. Defines internationalization functionality.
     * - Palmodule_PayPal_Payment_For_Woocoomerce_Admin. Defines all hooks for the admin area.
     * - Palmodule_PayPal_Payment_For_Woocoomerce_Public. Defines all hooks for the public side of the site.
     *
     * Create an instance of the loader which will be used to register the hooks
     * with WordPress.
     *
     * @since    1.0.0
     * @access   private
     */
    private function load_dependencies() {

        /**
         * The class responsible for orchestrating the actions and filters of the
         * core plugin.
         */
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-palmodule-paypal-payment-for-woocoomerce-loader.php';

        /**
         * The class responsible for defining internationalization functionality
         * of the plugin.
         */
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-palmodule-paypal-payment-for-woocoomerce-i18n.php';

        /**
         * The class responsible for defining all actions that occur in the admin area.
         */
        require_once plugin_dir_path(dirname(__FILE__)) . 'admin/class-palmodule-paypal-payment-for-woocoomerce-admin.php';

        /**
         * The class responsible for defining all actions that occur in the public-facing
         * side of the site.
         */
        require_once plugin_dir_path(dirname(__FILE__)) . 'public/class-palmodule-paypal-payment-for-woocoomerce-public.php';

        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-palmodule-paypal-payment-for-woocoomerce-functions.php';

        $this->loader = new Palmodule_PayPal_Payment_For_Woocoomerce_Loader();
    }

    /**
     * Define the locale for this plugin for internationalization.
     *
     * Uses the Palmodule_PayPal_Payment_For_Woocoomerce_i18n class in order to set the domain and to register the hook
     * with WordPress.
     *
     * @since    1.0.0
     * @access   private
     */
    private function set_locale() {

        $plugin_i18n = new Palmodule_PayPal_Payment_For_Woocoomerce_i18n();

        $this->loader->add_action('plugins_loaded', $plugin_i18n, 'load_plugin_textdomain');
    }

    /**
     * Register all of the hooks related to the admin area functionality
     * of the plugin.
     *
     * @since    1.0.0
     * @access   private
     */
    private function define_admin_hooks() {
        $plugin_admin = new Palmodule_PayPal_Payment_For_Woocoomerce_Admin($this->get_plugin_name(), $this->get_version());
        $this->loader->add_action('admin_enqueue_scripts', $plugin_admin, 'enqueue_styles');
        $this->loader->add_action('admin_enqueue_scripts', $plugin_admin, 'enqueue_scripts');
        $this->loader->add_action('plugins_loaded', $plugin_admin, 'init_palmodule_paypal_payment');
        $this->loader->add_filter('woocommerce_payment_gateways', $plugin_admin, 'palmodule_pal_payment_for_woo_add_payment_method_class', 9999, 1);
    }

    /**
     * Register all of the hooks related to the public-facing functionality
     * of the plugin.
     *
     * @since    1.0.0
     * @access   private
     */
    private function define_public_hooks() {
        $plugin_public = new Palmodule_PayPal_Payment_For_Woocoomerce_Public($this->get_plugin_name(), $this->get_version());
        
            $this->loader->add_action('wp_enqueue_scripts', $plugin_public, 'enqueue_styles');
            $this->loader->add_action('wp_enqueue_scripts', $plugin_public, 'enqueue_scripts');
        
    }

    /**
     * Run the loader to execute all of the hooks with WordPress.
     *
     * @since    1.0.0
     */
    public function run() {
        $this->loader->run();
    }

    /**
     * The name of the plugin used to uniquely identify it within the context of
     * WordPress and to define internationalization functionality.
     *
     * @since     1.0.0
     * @return    string    The name of the plugin.
     */
    public function get_plugin_name() {
        return $this->plugin_name;
    }

    /**
     * The reference to the class that orchestrates the hooks with the plugin.
     *
     * @since     1.0.0
     * @return    Palmodule_PayPal_Payment_For_Woocoomerce_Loader    Orchestrates the hooks of the plugin.
     */
    public function get_loader() {
        return $this->loader;
    }

    /**
     * Retrieve the version number of the plugin.
     *
     * @since     1.0.0
     * @return    string    The version number of the plugin.
     */
    public function get_version() {
        return $this->version;
    }
    
    public function handle_api_requests() {
        global $wp;
        if (isset($_GET['palmodule_ipn_action']) && $_GET['palmodule_ipn_action'] == 'ipn') {
            $wp->query_vars['Palmodule_PayPal_Payment_For_Woocoomerce'] = $_GET['palmodule_ipn_action'];
        }
        if (!empty($wp->query_vars['Palmodule_PayPal_Payment_For_Woocoomerce'])) {
            ob_start();
            $api = strtolower(esc_attr($wp->query_vars['Palmodule_PayPal_Payment_For_Woocoomerce']));
            do_action('palmodule_paypal_payment_api_' . $api);
            ob_end_clean();
            die('1');
        }
    }

    public function add_endpoint() {
        add_rewrite_endpoint('Palmodule_PayPal_Payment_For_Woocoomerce', EP_ALL);
    }

    public function palmodule_paypal_payment_api_ipn() {
        require_once( PALMODULE_PAYPAL_PAYMENT_FOR_WOOCOOMERCE_PLUGIN_DIR . '/includes/paypal-ipn/class-palmodule-paypal-payment-for-woocoomerce-paypal-ipn-handler.php' );
        $Palmodule_PayPal_Payment_For_Woocoomerce_Paypal_IPN_Handler_Object = new Palmodule_PayPal_Payment_For_Woocoomerce_Paypal_IPN_Handler();
        $Palmodule_PayPal_Payment_For_Woocoomerce_Paypal_IPN_Handler_Object->check_response();
    }

}
