<?php

/**
 * Define the internationalization functionality
 *
 * Loads and defines the internationalization files for this plugin
 * so that it is ready for translation.
 *
 * @link       https://profiles.wordpress.org/palmoduledev
 * @since      1.0.0
 *
 * @package    Palmodule_PayPal_Payment_For_Woocoomerce
 * @subpackage Palmodule_PayPal_Payment_For_Woocoomerce/includes
 */

/**
 * Define the internationalization functionality.
 *
 * Loads and defines the internationalization files for this plugin
 * so that it is ready for translation.
 *
 * @since      1.0.0
 * @package    Palmodule_PayPal_Payment_For_Woocoomerce
 * @subpackage Palmodule_PayPal_Payment_For_Woocoomerce/includes
 * @author     palmoduledev <palmoduledev@gmail.com>
 */
class Palmodule_PayPal_Payment_For_Woocoomerce_i18n {


	/**
	 * Load the plugin text domain for translation.
	 *
	 * @since    1.0.0
	 */
	public function load_plugin_textdomain() {

		load_plugin_textdomain(
			'palmodule-paypal-payment-for-woocoomerce',
			false,
			dirname( dirname( plugin_basename( __FILE__ ) ) ) . '/languages/'
		);

	}



}
