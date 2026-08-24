<?php
/**
 * Bio Link - Main Plugin Class
 *
 * @package Bio_Link
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Bio_Link {

	private static $instance = null;

	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {
		$this->init();
	}

	private function init() {
		add_action( 'init', array( $this, 'register_post_types' ) );
		add_action( 'init', array( $this, 'register_shortcodes' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_public_assets' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_assets' ) );
	}

	public function activate() {
		$this->register_post_types();
		$this->set_default_options();
		flush_rewrite_rules();
	}

	private function set_default_options() {
		$defaults = array(
			'bio_link_profile_photo' => '',
			'bio_link_bio_text'      => '',
			'bio_link_middle_server_url' => '',
			'bio_link_api_key'       => '',
			'bio_link_ig_token'      => '',
		);

		foreach ( $defaults as $key => $value ) {
			if ( false === get_option( $key ) ) {
				add_option( $key, $value );
			}
		}
	}

	public function register_post_types() {
		register_post_type( 'bio_link_photo',
			array(
				'labels' => array(
					'name'               => __( 'Photos', 'bio-link' ),
					'singular_name'      => __( 'Photo', 'bio-link' ),
					'add_new'            => __( 'Add New Photo', 'bio-link' ),
					'add_new_item'       => __( 'Add New Photo', 'bio-link' ),
					'edit_item'          => __( 'Edit Photo', 'bio-link' ),
					'new_item'           => __( 'New Photo', 'bio-link' ),
					'view_item'          => __( 'View Photo', 'bio-link' ),
					'search_items'       => __( 'Search Photos', 'bio-link' ),
					'not_found'          => __( 'No photos found', 'bio-link' ),
					'not_found_in_trash' => __( 'No photos found in Trash', 'bio-link' ),
				),
				'public'       => false,
				'show_ui'      => true,
				'show_in_menu' => 'bio-link',
				'supports'     => array( 'title', 'thumbnail', 'custom-fields' ),
				'rewrite'      => false,
				'show_in_rest' => true,
			)
		);
	}

	public function register_shortcodes() {
		add_shortcode( 'bio_link', array( 'Bio_Link_Frontend', 'render_shortcode' ) );
	}

	public function enqueue_public_assets() {
		wp_enqueue_style( 'bio-link-public', BIO_LINK_PLUGIN_URL . 'assets/css/public.css', array(), BIO_LINK_VERSION );
		wp_enqueue_script( 'bio-link-public', BIO_LINK_PLUGIN_URL . 'assets/js/public.js', array( 'jquery' ), BIO_LINK_VERSION, true );
		wp_localize_script( 'bio-link-public', 'bioLink', array(
			'ajaxUrl' => admin_url( 'admin-ajax.php' ),
			'nonce'   => wp_create_nonce( 'bio_link_nonce' ),
		));
	}

	public function enqueue_admin_assets( $hook ) {
		if ( 'toplevel_page_bio-link' !== $hook && 'bio-link_page_bio-link-settings' !== $hook ) {
			return;
		}
		wp_enqueue_style( 'bio-link-admin', BIO_LINK_PLUGIN_URL . 'assets/css/admin.css', array(), BIO_LINK_VERSION );
		wp_enqueue_script( 'bio-link-admin', BIO_LINK_PLUGIN_URL . 'assets/js/admin.js', array( 'jquery' ), BIO_LINK_VERSION, true );
		wp_localize_script( 'bio-link-admin', 'bioLinkAdmin', array(
			'ajaxUrl' => admin_url( 'admin-ajax.php' ),
			'nonce'   => wp_create_nonce( 'bio_link_admin_nonce' ),
		));
	}
}
