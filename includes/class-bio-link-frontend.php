<?php
/**
 * Bio Link - Frontend Display
 *
 * @package Bio_Link
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Bio_Link_Frontend {

	public static function render_shortcode( $atts ) {
		$atts = shortcode_atts( array(
			'layout' => 'grid',
		), $atts, 'bio_link' );

		$photos = get_posts( array(
			'post_type'      => 'bio_link_photo',
			'posts_per_page' => -1,
			'orderby'        => 'menu_order',
			'order'          => 'ASC',
		) );

		if ( empty( $photos ) ) {
			return '<p>' . __( 'No photos added yet.', 'bio-link' ) . '</p>';
		}

		$profile_photo = get_option( 'bio_link_profile_photo', '' );
		$bio_text      = get_option( 'bio_link_bio_text', '' );

		ob_start();
		include BIO_LINK_PLUGIN_DIR . 'templates/frontend-display.php';
		return ob_get_clean();
	}
}
