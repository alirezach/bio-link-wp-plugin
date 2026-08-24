<?php
/**
 * Bio Link - DM Automation
 *
 * @package Bio_Link
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Bio_Link_DM_Automation {

	public function __construct() {
		add_action( 'bio_link_sync_keyword', array( $this, 'sync_keyword' ), 10, 2 );
	}

	public function sync_keyword( $post_id, $data ) {
		$middle_server = new Bio_Link_Middle_Server();
		if ( ! $middle_server->is_configured() ) {
			return;
		}

		$response = wp_remote_post( $middle_server->get_base_url() . '/api/v1/automation/keyword', array(
			'headers' => array(
				'Content-Type'   => 'application/json',
				'X-BioLink-Site' => md5( home_url() ),
				'X-BioLink-Key'  => $middle_server->get_api_key(),
			),
			'body'    => wp_json_encode( $data ),
			'timeout' => 30,
		) );

		if ( is_wp_error( $response ) ) {
			error_log( 'Bio Link: DM sync failed: ' . $response->get_error_message() );
		}
	}
}
