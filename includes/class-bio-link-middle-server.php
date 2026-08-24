<?php
/**
 * Bio Link - Middle Server Integration
 *
 * @package Bio_Link
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Bio_Link_Middle_Server {

	public function __construct() {
		add_action( 'save_post_bio_link_photo', array( $this, 'sync_photo_to_middle_server' ), 10, 3 );
		add_action( 'before_delete_post', array( $this, 'delete_photo_from_middle_server' ) );
	}

	public function get_base_url() {
		return get_option( 'bio_link_middle_server_url', '' );
	}

	public function get_api_key() {
		return get_option( 'bio_link_api_key', '' );
	}

	public function is_configured() {
		return ! empty( $this->get_base_url() ) && ! empty( $this->get_api_key() );
	}

	public function get_image_url( $instagram_url, $size = 'l' ) {
		if ( ! $this->is_configured() || empty( $instagram_url ) ) {
			return '';
		}

		$base = untrailingslashit( $this->get_base_url() );
		$endpoint = add_query_arg( array(
			'url'  => rawurlencode( $instagram_url ),
			'size' => $size,
		), $base . '/api/v1/image' );

		return $endpoint;
	}

	public function sync_photo_to_middle_server( $post_id, $post, $update ) {
		if ( 'bio_link_photo' !== $post->post_type ) {
			return;
		}

		if ( ! $this->is_configured() ) {
			return;
		}

		$keyword       = get_post_meta( $post_id, '_bio_link_keyword', true );
		$dm_message    = get_post_meta( $post_id, '_bio_link_dm_message', true );
		$dm_link       = get_post_meta( $post_id, '_bio_link_dm_link', true );
		$follow_gate   = get_post_meta( $post_id, '_bio_link_follow_gate', true );
		$instagram_url = get_post_meta( $post_id, '_bio_link_instagram_url', true );

		if ( empty( $keyword ) ) {
			return;
		}

		$body = array(
			'post_id'      => $instagram_url,
			'keyword'      => $keyword,
			'message'      => $dm_message,
			'link'         => $dm_link,
			'follow_gate'  => (bool) $follow_gate,
			'active'       => true,
		);

		$response = wp_remote_post( $this->get_base_url() . '/api/v1/automation/keyword', array(
			'headers' => array(
				'Content-Type'   => 'application/json',
				'X-BioLink-Site' => $this->get_site_id(),
				'X-BioLink-Key'  => $this->get_api_key(),
			),
			'body'    => wp_json_encode( $body ),
			'timeout' => 30,
		) );

		if ( is_wp_error( $response ) ) {
			error_log( 'Bio Link: Failed to sync keyword to middle server: ' . $response->get_error_message() );
		}
	}

	public function delete_photo_from_middle_server( $post_id ) {
		$post = get_post( $post_id );
		if ( ! $post || 'bio_link_photo' !== $post->post_type ) {
			return;
		}

		// TODO: Delete keyword trigger from middle server
	}

	private function get_site_id() {
		return md5( home_url() );
	}
}
