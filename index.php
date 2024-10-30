<?php
/**
 * Plugin Name: Betterpay
 * Plugin URI: https://www.Betterpay.me
 * Description: Betterpay. Fast and Automated Payments. Built for Merchants.
 * Version: 1.2.1
 * Author: Betterpay
 * Author URI: https://github.com/shahrul95-dev
 * WC requires at least: 2.6.0
 * WC tested up to: 6.6
 **/

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

# Include Betterpay Class and register Payment Gateway with WooCommerce
add_action( 'plugins_loaded', 'Betterpay_init', 0 );

function Betterpay_init() {
	if ( ! class_exists( 'WC_Payment_Gateway' ) ) {
		return;
	}

	include_once( 'src/Betterpay.php' );

	add_filter( 'woocommerce_payment_gateways', 'add_Betterpay_to_woocommerce' );
	function add_Betterpay_to_woocommerce( $methods ) {
		$methods[] = 'Betterpay';

		return $methods;
	}
}

# Add custom action links
add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), 'Betterpay_links' );

function Betterpay_links( $links ) {
	$plugin_links = array(
		'<a href="' . admin_url( 'admin.php?page=wc-settings&tab=checkout&section=Betterpay' ) . '">' . __( 'Settings', 'Betterpay' ) . '</a>',
	);

	# Merge our new link with the default ones
	return array_merge( $plugin_links, $links );
}

add_action( 'init', 'Betterpay_check_response', 15 );

function Betterpay_check_response() {
	# If the parent WC_Payment_Gateway class doesn't exist it means WooCommerce is not installed on the site, so do nothing
	if ( ! class_exists( 'WC_Payment_Gateway' ) ) {
		return;
	}

	include_once( 'src/Betterpay.php' );

	$Betterpay = new Betterpay();
	$Betterpay->check_Betterpay_response();
}

function Betterpay_hash_error_msg( $content ) {
	return '<div class="woocommerce-error">Invalid data entered. Please contact your merchant for more info.</div>' . $content;
}

function Betterpay_payment_declined_msg( $content ) {
	return '<div class="woocommerce-error">Fail transaction. Please check with your bank system.</div>' . $content;
}

function Betterpay_success_msg( $content ) {
	return '<div class="woocommerce-info">The payment was successful. Thank you.</div>' . $content;
}


/**
 * Custom function to declare compatibility with cart_checkout_blocks feature 
*/
function declare_cart_checkout_blocks_compatibility() {
    // Check if the required class exists
    if (class_exists('\Automattic\WooCommerce\Utilities\FeaturesUtil')) {
        // Declare compatibility for 'cart_checkout_blocks'
        \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility('cart_checkout_blocks', __FILE__, true);
    }
}
// Hook the custom function to the 'before_woocommerce_init' action
add_action('before_woocommerce_init', 'declare_cart_checkout_blocks_compatibility');

// Hook the custom function to the 'woocommerce_blocks_loaded' action
add_action( 'woocommerce_blocks_loaded', 'oawoo_register_order_approval_payment_method_type' );

/**
 * Custom function to register a payment method type

 */
function oawoo_register_order_approval_payment_method_type() {
    // Check if the required class exists
    if ( ! class_exists( 'Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType' ) ) {
        return;
    }

    // Include the custom Blocks Checkout class
    require_once plugin_dir_path(__FILE__) . 'class-block.php';

    // Hook the registration function to the 'woocommerce_blocks_payment_method_type_registration' action
    add_action(
        'woocommerce_blocks_payment_method_type_registration',
        function( Automattic\WooCommerce\Blocks\Payments\PaymentMethodRegistry $payment_method_registry ) {
            // Register an instance of Betterpay_Blocks
            $payment_method_registry->register( new Betterpay_Blocks );
        }
    );
}