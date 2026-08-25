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
			'layout'        => 'grid',
			'show_followers' => '',
			'username'      => '',
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
		$followers     = intval( get_option( 'bio_link_followers', 0 ) );

		$show_followers = $atts['show_followers'];
		if ( '' === $show_followers ) {
			$show_followers = (bool) get_option( 'bio_link_show_followers', 1 );
		} else {
			$show_followers = in_array( strtolower( (string) $show_followers ), array( '1', 'true', 'yes', 'on' ), true );
		}

		$social_links = self::parse_social_links( get_option( 'bio_link_social_links', '' ) );

		ob_start();
		include BIO_LINK_PLUGIN_DIR . 'templates/frontend-display.php';
		return ob_get_clean();
	}

	/**
	 * Parse social links from a JSON string into [['platform'=>, 'url'=>], ...].
	 */
	public static function parse_social_links( $json ) {
		$links = array();
		if ( empty( $json ) ) {
			return $links;
		}
		$decoded = json_decode( $json, true );
		if ( ! is_array( $decoded ) ) {
			return $links;
		}
		foreach ( $decoded as $item ) {
			if ( ! is_array( $item ) || empty( $item['url'] ) ) {
				continue;
			}
			$links[] = array(
				'platform' => isset( $item['platform'] ) ? sanitize_key( $item['platform'] ) : 'custom',
				'url'      => esc_url_raw( $item['url'] ),
			);
		}
		return $links;
	}

	/**
	 * Inline SVG icon for a social platform.
	 */
	public static function get_social_icon( $platform ) {
		$icons = array(
			'telegram' => '<svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M11.944 0A12 12 0 0 0 0 12a12 12 0 0 0 12 12 12 12 0 0 0 12-12A12 12 0 0 0 12 0a12 12 0 0 0-.056 0zm4.962 7.224c.1-.002.321.023.465.14a.506.506 0 0 1 .171.325c.016.093.036.306.02.472-.18 1.898-.962 6.502-1.36 8.627-.168.9-.499 1.201-.82 1.23-.696.065-1.225-.46-1.9-.902-1.056-.693-1.653-1.124-2.678-1.8-1.185-.78-.417-1.21.258-1.91.177-.184 3.247-2.977 3.307-3.23.007-.032.014-.15-.056-.212s-.174-.041-.249-.024c-.106.024-1.793 1.14-5.061 3.345-.48.33-.913.49-1.302.48-.428-.008-1.252-.241-1.865-.44-.752-.245-1.349-.374-1.297-.789.027-.216.325-.437.893-.663 3.498-1.524 5.83-2.529 6.998-3.014 3.332-1.386 4.025-1.627 4.476-1.635z"/></svg>',
			'x' => '<svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M18.901 1.153h3.68l-8.04 9.19L24 22.846h-7.406l-5.8-7.584-6.638 7.584H.474l8.6-9.83L0 1.154h7.594l5.243 6.932ZM17.61 20.644h2.039L6.486 3.24H4.298Z"/></svg>',
			'twitter' => '<svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M18.901 1.153h3.68l-8.04 9.19L24 22.846h-7.406l-5.8-7.584-6.638 7.584H.474l8.6-9.83L0 1.154h7.594l5.243 6.932ZM17.61 20.644h2.039L6.486 3.24H4.298Z"/></svg>',
			'linkedin' => '<svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433a2.062 2.062 0 0 1-2.063-2.065 2.064 2.064 0 1 1 2.063 2.065zm1.782 13.019H3.555V9h3.564v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0h.003z"/></svg>',
			'instagram' => '<svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zm0-2.163c-3.259 0-3.667.014-4.947.072-4.358.2-6.78 2.618-6.98 6.98-.059 1.281-.073 1.689-.073 4.948 0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98-1.281-.059-1.69-.073-4.949-.073zm0 5.838a6.162 6.162 0 1 0 0 12.324 6.162 6.162 0 0 0 0-12.324zM12 16a4 4 0 1 1 0-8 4 4 0 0 1 0 8zm6.406-11.845a1.44 1.44 0 1 0 0 2.881 1.44 1.44 0 0 0 0-2.881z"/></svg>',
			'whatsapp' => '<svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 0 1-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 0 1-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 0 1 2.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0 0 12.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 0 0 5.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 0 0-3.48-8.413Z"/></svg>',
			'bluesky' => '<svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M12 10.8c-1.087-2.114-4.046-6.053-6.798-7.995C2.566 1.006 1.03 1.47.642 2.884c-.309 1.125-.222 2.928.42 6.715.155 1.026.629 1.232 1.183.952-1.776 3.383-2.042 6.138-1.038 8.04 1.547 2.93 5.082 1.515 6.613-1.104.873-1.49 1.195-3.079 1.18-4.687zm0 0c1.087-2.114 4.046-6.053 6.798-7.995 2.636-1.8 4.172-1.335 4.56.079.309 1.125.222 2.928-.42 6.715-.155 1.026-.629 1.232-1.183.952 1.776 3.383 2.042 6.138 1.038 8.04-1.547 2.93-5.082 1.515-6.613-1.104-.873-1.49-1.195-3.079-1.18-4.687z"/></svg>',
		);

		$platform = sanitize_key( $platform );
		if ( isset( $icons[ $platform ] ) ) {
			return $icons[ $platform ];
		}

		// Generic link icon fallback (custom).
		return '<svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M3.9 12c0-1.71 1.39-3.1 3.1-3.1h4V7H7c-2.76 0-5 2.24-5 5s2.24 5 5 5h4v-1.9H7c-1.71 0-3.1-1.39-3.1-3.1zM8 13h8v-2H8v2zm9-6h-4v1.9h4c1.71 0 3.1 1.39 3.1 3.1s-1.39 3.1-3.1 3.1h-4V17h4c2.76 0 5-2.24 5-5s-2.24-5-5-5z"/></svg>';
	}

	/**
	 * Render a list of social links (array of ['platform'=>, 'url'=>]).
	 */
	public static function render_social_links( $links ) {
		if ( empty( $links ) ) {
			return '';
		}
		$html = '<div class="bio-link-socials">';
		foreach ( $links as $link ) {
			if ( empty( $link['url'] ) ) {
				continue;
			}
			$html .= '<a href="' . esc_url( $link['url'] ) . '" class="bio-link-social bio-link-social-' . esc_attr( $link['platform'] ) . '" target="_blank" rel="noopener noreferrer" aria-label="' . esc_attr( $link['platform'] ) . '">'
				. self::get_social_icon( $link['platform'] ) . '</a>';
		}
		$html .= '</div>';
		return $html;
	}
}
