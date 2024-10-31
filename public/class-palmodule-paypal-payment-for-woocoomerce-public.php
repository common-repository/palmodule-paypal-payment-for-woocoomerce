<?php

/**
 * The public-facing functionality of the plugin.
 *
 * @link       https://profiles.wordpress.org/palmoduledev
 * @since      1.0.0
 *
 * @package    Palmodule_PayPal_Payment_For_Woocoomerce
 * @subpackage Palmodule_PayPal_Payment_For_Woocoomerce/public
 */

/**
 * The public-facing functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    Palmodule_PayPal_Payment_For_Woocoomerce
 * @subpackage Palmodule_PayPal_Payment_For_Woocoomerce/public
 * @author     palmoduledev <palmoduledev@gmail.com>
 */
class Palmodule_PayPal_Payment_For_Woocoomerce_Public {

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
	 * @param      string    $plugin_name       The name of the plugin.
	 * @param      string    $version    The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {

		$this->plugin_name = $plugin_name;
		$this->version = $version;

	}

	/**
	 * Register the stylesheets for the public-facing side of the site.
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
                if (is_palmodule_express_checkout_ready_to_capture() == false) {
                    wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'css/palmodule-paypal-payment-for-woocoomerce-public.css', array(), $this->version, 'all' );
                } else {
                    wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'css/palmodule-paypal-payment-for-woocoomerce-public-ec.css', array(), $this->version, 'all' );
                }

	}

	/**
	 * Register the JavaScript for the public-facing side of the site.
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
               if (is_palmodule_express_checkout_ready_to_capture() == false) {
                    wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'js/palmodule-paypal-payment-for-woocoomerce-public.js', array( 'jquery' ), $this->version, false );
               }

	}

}
