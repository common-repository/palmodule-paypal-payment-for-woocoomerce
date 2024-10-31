<?php

/**
 *
 * @link              https://profiles.wordpress.org/palmoduledev
 * @since             1.0.0
 * @package           Palmodule_PayPal_Payment_For_Woocoomerce
 *
 * @wordpress-plugin
 * Plugin Name:       PayPal Payment For WooCoomerce
 * Plugin URI:        palmodule-paypal-payment-for-woocoomerce
 * Description:       Easily add PayPal payment options to your WordPress / WooCommerce website.Official PayPal Partner.
 * Version:           1.0.8
 * Author:            palmoduledev
 * Author URI:        https://profiles.wordpress.org/palmoduledev
 * License: GPLv3 or later
 * License URI: http://www.gnu.org/licenses/gpl-3.0.html
 * Text Domain:       palmodule-paypal-payment-for-woocoomerce
 * Domain Path:       /languages
 * Requires at least: 3.8
 * Tested up to: 5.8.2
 * WC requires at least: 3.0.0
 * WC tested up to: 5.9.0
 */
// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}
if (!defined('PALMODULE_PAYPAL_PAYMENT_FOR_WOOCOOMERCE_PLUGIN_DIR')) {
    define('PALMODULE_PAYPAL_PAYMENT_FOR_WOOCOOMERCE_PLUGIN_DIR', untrailingslashit(dirname(__FILE__)));
}
if (!defined('PALMODULE_PAYPAL_PAYMENT_FOR_WOOCOOMERCE_ASSET_URL')) {
    define('PALMODULE_PAYPAL_PAYMENT_FOR_WOOCOOMERCE_ASSET_URL', untrailingslashit(plugin_dir_url(__FILE__)));
}

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-palmodule-paypal-payment-for-woocoomerce-activator.php
 */
function activate_pal_payment_for_woo() {
    require_once plugin_dir_path(__FILE__) . 'includes/class-palmodule-paypal-payment-for-woocoomerce-activator.php';
    Palmodule_PayPal_Payment_For_Woocoomerce_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-palmodule-paypal-payment-for-woocoomerce-deactivator.php
 */
function deactivate_pal_payment_for_woo() {
    require_once plugin_dir_path(__FILE__) . 'includes/class-palmodule-paypal-payment-for-woocoomerce-deactivator.php';
    Palmodule_PayPal_Payment_For_Woocoomerce_Deactivator::deactivate();
}

register_activation_hook(__FILE__, 'activate_pal_payment_for_woo');
register_deactivation_hook(__FILE__, 'deactivate_pal_payment_for_woo');

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require plugin_dir_path(__FILE__) . 'includes/class-palmodule-paypal-payment-for-woocoomerce.php';

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */
function run_pal_payment_for_woo() {
    $plugin = new Palmodule_PayPal_Payment_For_Woocoomerce();
    $plugin->run();
}

run_pal_payment_for_woo();

