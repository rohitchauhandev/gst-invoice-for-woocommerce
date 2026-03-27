<?php
/**
 * Plugin Name: GST Invoice for WooCommerce India
 * Plugin URI: https://example.com/
 * Description: Adds GST invoice settings for WooCommerce stores in India.
 * Version: 1.0.0
 * Author: OpenAI
 * License: GPL-2.0-or-later
 * Text Domain: gst-invoice-for-woocommerce-india
 * Domain Path: /languages
 * Requires at least: 6.0
 * Requires PHP: 7.4
 * WC requires at least: 7.0
 * WC tested up to: 9.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'GIWI_PLUGIN_FILE', __FILE__ );
define( 'GIWI_PLUGIN_PATH', plugin_dir_path( __FILE__ ) );
define( 'GIWI_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'GIWI_VERSION', '1.0.0' );

require_once GIWI_PLUGIN_PATH . 'includes/class-giwi-settings.php';

/**
 * Declare compatibility with WooCommerce custom order tables.
 */
function giwi_declare_wc_compatibility() {
	if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
		\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', __FILE__, true );
	}
}
add_action( 'before_woocommerce_init', 'giwi_declare_wc_compatibility' );

/**
 * Load plugin text domain.
 */
function giwi_load_textdomain() {
	load_plugin_textdomain( 'gst-invoice-for-woocommerce-india', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
}
add_action( 'init', 'giwi_load_textdomain' );

/**
 * Initialize admin settings.
 */
function giwi_bootstrap() {
	if ( is_admin() && ! class_exists( 'WooCommerce' ) ) {
		add_action( 'admin_notices', 'giwi_missing_woocommerce_notice' );
		return;
	}

	new GIWI_Settings();
}
add_action( 'plugins_loaded', 'giwi_bootstrap' );

/**
 * Show admin notice when WooCommerce is missing.
 */
function giwi_missing_woocommerce_notice() {
	printf(
		'<div class="notice notice-warning"><p>%s</p></div>',
		esc_html__( 'GST Invoice for WooCommerce India requires WooCommerce to be installed and active.', 'gst-invoice-for-woocommerce-india' )
	);
}
