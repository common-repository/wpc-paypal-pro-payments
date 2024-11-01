<?php

/**
 * Plugin Name:       WPC PayPal Pro Payments
 * Plugin URI:        #
 * Description:       WPC PayPal Pro Payments allows you to accept credit cards on your website.
 * Version:           1.0.1
 * Author:            WPCodelibrary
 * Author URI:        http://www.wpcodelibrary.com
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       wpc-paypal-pro-payments
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}
if (!defined('WPC_PAYPALPRO_URL')) {
    define('WPC_PAYPALPRO_URL', plugin_dir_url(__FILE__));
}

if (!defined('WPC_SLUG')) {
    define('WPC_SLUG', 'wpc-paypal-pro-payments');
}
/**
 * define plugin basename
 */
if (!defined('WPC_PAYPALPRO_BASENAME')) {
    define('WPC_PAYPALPRO_BASENAME', plugin_basename(__FILE__));
}

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-wpc-paypal-pro-payments-activator.php
 */
function activate_wpc_paypal_pro_payments() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-wpc-paypal-pro-payments-activator.php';
	Wpc_Paypal_Pro_Payments_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-wpc-paypal-pro-payments-deactivator.php
 */
function deactivate_wpc_paypal_pro_payments() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-wpc-paypal-pro-payments-deactivator.php';
	Wpc_Paypal_Pro_Payments_Deactivator::deactivate();
}

register_activation_hook( __FILE__, 'activate_wpc_paypal_pro_payments' );
register_deactivation_hook( __FILE__, 'deactivate_wpc_paypal_pro_payments' );

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require plugin_dir_path( __FILE__ ) . 'includes/class-wpc-paypal-pro-payments.php';

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */
function run_wpc_paypal_pro_payments() {

	$plugin = new Wpc_Paypal_Pro_Payments();
	$plugin->run();

}
add_action('plugins_loaded', 'load_wpc_paypal_pro_payments');

function load_wpc_paypal_pro_payments() {
    run_wpc_paypal_pro_payments();
}