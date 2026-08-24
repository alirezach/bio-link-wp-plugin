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
