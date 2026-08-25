<?php
/**
 * Bio Link - REST API
 *
 * @package Bio_Link
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Bio_Link_REST_API {

	public function __construct() {
		add_action( 'rest_api_init', array( $this, 'register_routes' ) );
	}

	public function register_routes() {
		register_rest_route( 'bio-link/v1', '/photos', array(
			'methods'  => 'GET',
			'callback' => array( $this, 'get_photos' ),
			'permission_callback' => '__return_true',
		) );

		register_rest_route( 'bio-link/v1', '/photos/(?P<id>\d+)', array(
			'methods'  => 'GET',
			'callback' => array( $this, 'get_photo' ),
			'permission_callback' => '__return_true',
		) );

		// Browser-side import: the admin page JS fetches from the middle server
		// (bypassing hosts with no outbound internet) and POSTs the result here.
		register_rest_route( 'bio-link/v1', '/import', array(
			'methods'  => 'POST',
			'callback' => array( $this, 'import_photos' ),
			'permission_callback' => function () {
				return current_user_can( 'manage_options' );
			},
		) );
	}

	public function get_photos( $request ) {
		$photos = get_posts( array(
			'post_type'      => 'bio_link_photo',
			'posts_per_page' => -1,
			'orderby'        => 'menu_order',
			'order'          => 'ASC',
		) );

		$data = array();
		foreach ( $photos as $photo ) {
			$data[] = $this->prepare_photo_for_response( $photo );
		}

		return rest_ensure_response( $data );
	}

	public function get_photo( $request ) {
		$photo = get_post( $request['id'] );
		if ( ! $photo || 'bio_link_photo' !== $photo->post_type ) {
			return new WP_Error( 'not_found', __( 'Photo not found', 'bio-link' ), array( 'status' => 404 ) );
		}

		return rest_ensure_response( $this->prepare_photo_for_response( $photo ) );
	}

	/**
	 * Import photos fetched by the browser from the middle server.
	 *
	 * Body: { username: string, followers: int, photos: [{ shortcode, image_url, thumbnail, caption }] }
	 */
	public function import_photos( $request ) {
		$params = $request->get_json_params();

		if ( ! is_array( $params ) || empty( $params['photos'] ) || ! is_array( $params['photos'] ) ) {
			return new WP_Error( 'bad_request', __( 'No photos provided', 'bio-link' ), array( 'status' => 400 ) );
		}

		$username = isset( $params['username'] ) ? sanitize_text_field( ltrim( $params['username'], '@' ) ) : '';
		$followers = isset( $params['followers'] ) ? intval( $params['followers'] ) : 0;

		if ( $username ) {
			update_option( 'bio_link_ig_username', $username );
		}
		if ( $followers > 0 ) {
			update_option( 'bio_link_followers', $followers );
		}

		$imported = 0;
		$skipped  = 0;
		$order    = 0;

		foreach ( $params['photos'] as $photo ) {
			if ( ! is_array( $photo ) ) {
				continue;
			}

			$shortcode = isset( $photo['shortcode'] ) ? sanitize_text_field( $photo['shortcode'] ) : '';
			$image_url = isset( $photo['image_url'] ) ? esc_url_raw( $photo['image_url'] ) : '';
			$thumbnail = isset( $photo['thumbnail'] ) ? esc_url_raw( $photo['thumbnail'] ) : '';
			$caption   = isset( $photo['caption'] ) ? sanitize_textarea_field( $photo['caption'] ) : '';

			if ( empty( $shortcode ) || empty( $image_url ) ) {
				continue;
			}

			$post_url = 'https://www.instagram.com/p/' . $shortcode . '/';

			$existing = get_posts( array(
				'post_type'   => 'bio_link_photo',
				'meta_key'    => '_bio_link_instagram_url',
				'meta_value'  => $post_url,
				'numberposts' => 1,
				'fields'      => 'ids',
			) );

			if ( ! empty( $existing ) ) {
				$skipped++;
				continue;
			}

			$title = ! empty( $caption ) ? wp_trim_words( $caption, 10, '…' ) : $shortcode;

			$post_id = wp_insert_post( array(
				'post_title'  => $title,
				'post_type'   => 'bio_link_photo',
				'post_status' => 'publish',
				'menu_order'  => $order++,
			) );

			if ( ! $post_id || is_wp_error( $post_id ) ) {
				continue;
			}

			// Best-effort featured image sideload. Fails silently on hosts with
			// no outbound internet — the template falls back to _bio_link_image_url.
			if ( function_exists( 'media_handle_sideload' ) ) {
				$tmp = download_url( $image_url );
				if ( ! is_wp_error( $tmp ) ) {
					$file_array = array(
						'name'     => basename( parse_url( $image_url, PHP_URL_PATH ) ) ?: 'instagram.jpg',
						'tmp_name' => $tmp,
					);
					$attach_id  = media_handle_sideload( $file_array, $post_id );
					if ( ! is_wp_error( $attach_id ) ) {
						set_post_thumbnail( $post_id, $attach_id );
					}
					@unlink( $tmp );
				}
			}

			update_post_meta( $post_id, '_bio_link_instagram_url', $post_url );
			update_post_meta( $post_id, '_bio_link_image_url', $image_url );
			update_post_meta( $post_id, '_bio_link_thumbnail_url', $thumbnail );
			update_post_meta( $post_id, '_bio_link_shortcode', $shortcode );
			update_post_meta( $post_id, '_bio_link_caption', $caption );

			$imported++;

			Bio_Link_Logger::log( 'info', "Imported photo #{$post_id} - {$post_url}" );
		}

		Bio_Link_Logger::log( 'info', "REST import OK for @{$username}: {$imported} imported, {$skipped} skipped" );

		return rest_ensure_response( array(
			'ok'       => true,
			'imported' => $imported,
			'skipped'  => $skipped,
			'followers'=> intval( get_option( 'bio_link_followers', 0 ) ),
			'message'  => sprintf( __( 'Imported %d photos, %d already existed.', 'bio-link' ), $imported, $skipped ),
		) );
	}

	private function prepare_photo_for_response( $photo ) {
		$post_url = get_post_meta( $photo->ID, '_bio_link_post_url', true );
		$has_link = ! empty( $post_url );

		return array(
			'id'           => $photo->ID,
			'title'        => $photo->post_title,
			'image_url'    => get_the_post_thumbnail_url( $photo->ID, 'full' ),
			'post_url'     => $post_url,
			'has_link'     => $has_link,
			'keyword'      => get_post_meta( $photo->ID, '_bio_link_keyword', true ),
			'dm_message'   => get_post_meta( $photo->ID, '_bio_link_dm_message', true ),
			'dm_link'      => get_post_meta( $photo->ID, '_bio_link_dm_link', true ),
			'follow_gate'  => (bool) get_post_meta( $photo->ID, '_bio_link_follow_gate', true ),
		);
	}
}
