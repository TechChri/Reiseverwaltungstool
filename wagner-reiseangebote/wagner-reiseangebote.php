<?php
/**
 * Plugin Name: Wagner Reiseangebote
 * Description: Theme-unabhängige Verwaltung und Ausgabe von Wagner-Reisen mit externer Busfinder-Buchung.
 * Version: 0.2.0
 * Author: Wagner Reisen
 * Text Domain: wagner-reiseangebote
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'WRA_VERSION', '0.2.0' );
define( 'WRA_FILE', __FILE__ );
define( 'WRA_DIR', plugin_dir_path( __FILE__ ) );
define( 'WRA_URL', plugin_dir_url( __FILE__ ) );

require_once WRA_DIR . 'includes/class-wra-post-type.php';
require_once WRA_DIR . 'includes/class-wra-meta-boxes.php';
require_once WRA_DIR . 'includes/class-wra-frontend.php';
require_once WRA_DIR . 'includes/class-wra-rest-api.php';

function wra_bootstrap() {
	$post_type = new WRA_Post_Type();
	$post_type->register_hooks();

	$meta_boxes = new WRA_Meta_Boxes();
	$meta_boxes->register_hooks();

	$frontend = new WRA_Frontend();
	$frontend->register_hooks();

	$rest_api = new WRA_REST_API();
	$rest_api->register_hooks();
}
add_action( 'plugins_loaded', 'wra_bootstrap' );

function wra_activate() {
	$post_type = new WRA_Post_Type();
	$post_type->register_content_types();
	flush_rewrite_rules();
}
register_activation_hook( __FILE__, 'wra_activate' );

function wra_deactivate() {
	flush_rewrite_rules();
}
register_deactivation_hook( __FILE__, 'wra_deactivate' );

