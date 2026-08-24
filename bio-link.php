<?php
/**
 * Plugin Name: Bio Link
 * Plugin URI: https://github.com/alirezach/bio-link-wp-plugin
 * Description: Instagram-style bio-link page with photo grid, color/BW rendering, and comment-to-DM automation.
 * Version: 1.0.0
 * Author: Alireza Chamanzar
 * Author URI: https://github.com/alirezach
 * License: GPL-2.0+
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain: bio-link
 * Domain Path: /languages
 * Requires at least: 6.0
 * Requires PHP: 7.4
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'BIO_LINK_VERSION', '1.0.0' );
define( 'BIO_LINK_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'BIO_LINK_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'BIO_LINK_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );

// Load text domain
add_action( 'plugins_loaded', 'bio_link_load_textdomain' );
function bio_link_load_textdomain() {
	load_plugin_textdomain( 'bio-link', false, dirname( BIO_LINK_PLUGIN_BASENAME ) . '/languages' );
}

// Include required files
require_once BIO_LINK_PLUGIN_DIR . 'includes/class-bio-link.php';
require_once BIO_LINK_PLUGIN_DIR . 'includes/class-bio-link-admin.php';
require_once BIO_LINK_PLUGIN_DIR . 'includes/class-bio-link-frontend.php';
require_once BIO_LINK_PLUGIN_DIR . 'includes/class-bio-link-rest-api.php';
require_once BIO_LINK_PLUGIN_DIR . 'includes/class-bio-link-middle-server.php';
require_once BIO_LINK_PLUGIN_DIR . 'includes/class-bio-link-dm-automation.php';

// Initialize plugin
add_action( 'init', 'bio_link_init' );
function bio_link_init() {
	Bio_Link::instance();
}

// Activation hook
register_activation_hook( __FILE__, 'bio_link_activate' );
function bio_link_activate() {
	require_once BIO_LINK_PLUGIN_DIR . 'includes/class-bio-link.php';
	Bio_Link::instance()->activate();
	flush_rewrite_rules();
}

// Deactivation hook
register_deactivation_hook( __FILE__, 'bio_link_deactivate' );
function bio_link_deactivate() {
	flush_rewrite_rules();
}
