<?php
/**
 * Plugin Name: Bio Link
 * Plugin URI: https://github.com/alirezach/bio-link-wp-plugin
 * Description: Instagram-style bio-link page with photo grid, color/BW, DM automation, and auto-import
 * Version: 1.4.1
 * Author: Hermes Swarm
 * Text Domain: bio-link
 * Domain Path: /languages
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'BIO_LINK_VERSION', '1.4.1' );
define( 'BIO_LINK_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'BIO_LINK_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'BIO_LINK_TEXT_DOMAIN', 'bio-link' );

require_once __DIR__ . '/includes/class-bio-link.php';

register_activation_hook( __FILE__, array( 'Bio_Link', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'Bio_Link', 'deactivate' ) );

add_action( 'plugins_loaded', function() {
	load_plugin_textdomain( 'bio-link', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );

	require_once __DIR__ . '/includes/class-bio-link-logger.php';
	require_once __DIR__ . '/includes/class-bio-link-admin.php';
	require_once __DIR__ . '/includes/class-bio-link-frontend.php';
	require_once __DIR__ . '/includes/class-bio-link-rest-api.php';
	require_once __DIR__ . '/includes/class-bio-link-middle-server.php';
	require_once __DIR__ . '/includes/class-bio-link-dm-automation.php';
	require_once __DIR__ . '/includes/class-bio-link-elementor.php';

	Bio_Link::instance();
	new Bio_Link_Elementor();
} );
