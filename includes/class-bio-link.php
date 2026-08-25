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

	public static function activate() {
		self::register_post_types();
		self::set_default_options();
		flush_rewrite_rules();
		do_action( 'bio_link_activation' );
	}

	public static function deactivate() {
		flush_rewrite_rules();
		do_action( 'bio_link_deactivation' );
	}

	private function init() {
		add_action( 'init', array( $this, 'register_post_types' ) );
		add_action( 'init', array( $this, 'register_shortcodes' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_public_assets' ) );

		// Initialize sub-modules
		new Bio_Link_Admin();
		new Bio_Link_REST_API();
		new Bio_Link_Middle_Server();
		new Bio_Link_DM_Automation();
	}

	private static function set_default_options() {
		$defaults = array(
			'bio_link_profile_photo'    => '',
			'bio_link_bio_text'         => '',
			'bio_link_middle_server_url' => '',
			'bio_link_api_key'          => '',
			'bio_link_ig_token'         => '',
			'bio_link_ig_username'      => '',
			'bio_link_debug_enabled'    => 1,
			'bio_link_show_followers'   => 1,
			'bio_link_social_links'     => '',
			'bio_link_followers'        => 0,
		);

		foreach ( $defaults as $key => $value ) {
			if ( false === get_option( $key ) ) {
				add_option( $key, $value );
			}
		}
	}

	public function register_post_types() {
		$labels = array(
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
		);

		register_post_type( 'bio_link_photo',
			array(
				'labels'      => $labels,
				'public'      => false,
				'show_ui'     => true,
				'show_in_menu' => 'bio-link',
				'menu_icon'   => 'dashicons-instagram',
				'supports'    => array( 'title', 'thumbnail', 'custom-fields' ),
				'rewrite'     => false,
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
}
