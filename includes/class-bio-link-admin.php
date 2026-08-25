<?php
/**
 * Bio Link - Admin Settings
 *
 * @package Bio_Link
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Bio_Link_Admin {

	public function __construct() {
		add_action( 'admin_menu', array( $this, 'add_menu_pages' ) );
		add_action( 'admin_init', array( $this, 'register_settings' ) );
		add_action( 'add_meta_boxes', array( $this, 'add_photo_meta_boxes' ) );
		add_action( 'save_post_bio_link_photo', array( $this, 'save_photo_meta' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_assets' ) );
		add_action( 'updated_option', array( $this, 'save_settings_callback' ), 10, 3 );
		add_action( 'wp_ajax_bio_link_fetch_profile', array( $this, 'ajax_fetch_profile' ) );
		add_action( 'wp_ajax_bio_link_fetch_single', array( $this, 'ajax_fetch_single' ) );
		add_action( 'wp_ajax_bio_link_check_server', array( $this, 'ajax_check_server' ) );
		add_action( 'wp_ajax_bio_link_check_graph_api', array( $this, 'ajax_check_graph_api' ) );
		add_action( 'wp_ajax_bio_link_regenerate_api_key', array( $this, 'ajax_regenerate_api_key' ) );
		add_action( 'wp_ajax_bio_link_clear_log', array( $this, 'ajax_clear_log' ) );
		add_action( 'admin_head', array( $this, 'add_help_tab' ) );
	}

	public function add_menu_pages() {
		add_menu_page(
			__( 'Bio Link', 'bio-link' ),
			__( 'Bio Link', 'bio-link' ),
			'manage_options',
			'bio-link',
			array( $this, 'render_dashboard_page' ),
			'dashicons-instagram',
			30
		);

		add_submenu_page(
			'bio-link',
			__( 'Photos', 'bio-link' ),
			__( 'Photos', 'bio-link' ),
			'manage_options',
			'edit.php?post_type=bio_link_photo'
		);

		add_submenu_page(
			'bio-link',
			__( 'Add New Photo', 'bio-link' ),
			__( 'Add New', 'bio-link' ),
			'manage_options',
			'post-new.php?post_type=bio_link_photo'
		);

		add_submenu_page(
			'bio-link',
			__( 'Settings', 'bio-link' ),
			__( 'Settings', 'bio-link' ),
			'manage_options',
			'bio-link-settings',
			array( $this, 'render_settings_page' )
		);

		add_submenu_page(
			'bio-link',
			__( 'DM Automation', 'bio-link' ),
			__( 'DM Automation', 'bio-link' ),
			'manage_options',
			'bio-link-dm',
			array( $this, 'render_dm_page' )
		);

		add_submenu_page(
			'bio-link',
			__( 'Debug Log', 'bio-link' ),
			__( 'Debug Log', 'bio-link' ),
			'manage_options',
			'bio-link-debug',
			array( $this, 'render_debug_page' )
		);
	}

	public function register_settings() {
		register_setting( 'bio_link_settings', 'bio_link_profile_photo' );
		register_setting( 'bio_link_settings', 'bio_link_bio_text' );
		register_setting( 'bio_link_settings', 'bio_link_middle_server_url' );
		register_setting( 'bio_link_settings', 'bio_link_api_key' );
		register_setting( 'bio_link_settings', 'bio_link_ig_token' );
		register_setting( 'bio_link_settings', 'bio_link_ig_username' );
		register_setting( 'bio_link_settings', 'bio_link_debug_enabled' );
	}

	public function save_settings_callback( $option, $old_value, $new_value ) {
		$watch = array( 'bio_link_middle_server_url', 'bio_link_api_key' );
		if ( ! in_array( $option, $watch, true ) ) {
			return;
		}

		$server_url = get_option( 'bio_link_middle_server_url', '' );
		$api_key    = get_option( 'bio_link_api_key', '' );

		if ( ! empty( $server_url ) && ! empty( $api_key ) ) {
			$this->register_with_middle_server( $server_url, $api_key );
		}
	}

	private function register_with_middle_server( $server_url, $api_key ) {
		add_action( 'shutdown', function() use ( $server_url, $api_key ) {
			$site_id = md5( home_url() );
			$url = untrailingslashit( $server_url ) . '/api/v1/register-site';

			Bio_Link_Logger::log( 'info', "Registering site with middle server: {$url}" );

			$response = wp_remote_post( $url, array(
				'timeout' => 15,
				'headers' => array(
					'Content-Type' => 'application/json',
				),
				'body' => json_encode( array(
					'site_id' => $site_id,
					'api_key' => $api_key,
				) ),
			) );

			if ( is_wp_error( $response ) ) {
				Bio_Link_Logger::log( 'error', 'Registration failed: ' . $response->get_error_message() );
				return;
			}

			$code = wp_remote_retrieve_response_code( $response );
			$body = json_decode( wp_remote_retrieve_body( $response ), true );

			if ( $code === 200 && ! empty( $body['ok'] ) ) {
				Bio_Link_Logger::log( 'info', '✓ Site registered with middle server' );
			} else {
				Bio_Link_Logger::log( 'error', "Registration failed: HTTP {$code} - " . ( $body['error'] ?? 'unknown' ) );
			}
		} );
	}

	public function render_dashboard_page() {
		$photos = get_posts( array(
			'post_type'      => 'bio_link_photo',
			'posts_per_page' => -1,
			'orderby'        => 'menu_order',
			'order'          => 'ASC',
		) );
		$ig_username  = get_option( 'bio_link_ig_username', '' );
		$server_url   = get_option( 'bio_link_middle_server_url', '' );
		$api_key      = get_option( 'bio_link_api_key', '' );
		$configured   = ! empty( $server_url );
		include BIO_LINK_PLUGIN_DIR . 'templates/admin-dashboard.php';
	}

	public function render_add_new_page() {
		include BIO_LINK_PLUGIN_DIR . 'templates/admin-add-new.php';
	}

	public function render_settings_page() {
		$api_key   = get_option( 'bio_link_api_key', '' );
		$server_url = get_option( 'bio_link_middle_server_url', '' );
		$ig_token  = get_option( 'bio_link_ig_token', '' );
		include BIO_LINK_PLUGIN_DIR . 'templates/admin-settings.php';
	}

	public function render_dm_page() {
		include BIO_LINK_PLUGIN_DIR . 'templates/admin-dm.php';
	}

	public function render_debug_page() {
		$log = get_option( 'bio_link_debug_log', array() );
		include BIO_LINK_PLUGIN_DIR . 'templates/admin-debug.php';
	}

	public function add_photo_meta_boxes() {
		add_meta_box(
			'bio_link_photo_meta',
			__( 'Photo Details', 'bio-link' ),
			array( $this, 'render_photo_meta_box' ),
			'bio_link_photo',
			'normal',
			'high'
		);
	}

	public function render_photo_meta_box( $post ) {
		wp_nonce_field( 'bio_link_photo_meta', 'bio_link_photo_meta_nonce' );
		$instagram_url = get_post_meta( $post->ID, '_bio_link_instagram_url', true );
		$post_url      = get_post_meta( $post->ID, '_bio_link_post_url', true );
		$keyword       = get_post_meta( $post->ID, '_bio_link_keyword', true );
		$dm_message    = get_post_meta( $post->ID, '_bio_link_dm_message', true );
		$dm_link       = get_post_meta( $post->ID, '_bio_link_dm_link', true );
		$follow_gate   = get_post_meta( $post->ID, '_bio_link_follow_gate', true );
		?>
		<table class="form-table">
			<tr>
				<th><label for="bio_link_instagram_url"><?php _e( 'Instagram Post URL', 'bio-link' ); ?></label></th>
				<td>
					<input type="url" id="bio_link_instagram_url" name="bio_link_instagram_url" value="<?php echo esc_url( $instagram_url ); ?>" class="regular-text" />
					<button type="button" class="button" id="bio_link_fetch_single"><?php _e( 'Fetch Image', 'bio-link' ); ?></button>
					<span id="bio_link_single_status" style="margin-left:10px;"></span></td>
			</tr>
			<tr>
				<th><label for="bio_link_post_url"><?php _e( 'Post Link', 'bio-link' ); ?></label></th>
				<td><input type="url" id="bio_link_post_url" name="bio_link_post_url" value="<?php echo esc_url( $post_url ); ?>" class="regular-text" /></td>
			</tr>
			<tr>
				<th colspan="2"><h3><?php _e( 'DM Automation', 'bio-link' ); ?></h3></th>
			</tr>
			<tr>
				<th><label for="bio_link_keyword"><?php _e( 'Trigger Keyword', 'bio-link' ); ?></label></th>
				<td><input type="text" id="bio_link_keyword" name="bio_link_keyword" value="<?php echo esc_attr( $keyword ); ?>" class="regular-text" /></td>
			</tr>
			<tr>
				<th><label for="bio_link_dm_message"><?php _e( 'DM Message', 'bio-link' ); ?></label></th>
				<td><textarea id="bio_link_dm_message" name="bio_link_dm_message" rows="3" class="regular-text"><?php echo esc_textarea( $dm_message ); ?></textarea></td>
			</tr>
			<tr>
				<th><label for="bio_link_dm_link"><?php _e( 'DM Link (optional)', 'bio-link' ); ?></label></th>
				<td><input type="url" id="bio_link_dm_link" name="bio_link_dm_link" value="<?php echo esc_url( $dm_link ); ?>" class="regular-text" /></td>
			</tr>
			<tr>
				<th><label for="bio_link_follow_gate"><?php _e( 'Follow Gate', 'bio-link' ); ?></label></th>
				<td><input type="checkbox" id="bio_link_follow_gate" name="bio_link_follow_gate" value="1" <?php checked( $follow_gate, '1' ); ?> />
				<?php _e( 'Only send DM after the commenter follows your account.', 'bio-link' ); ?></td>
			</tr>
		</table>
		<?php
	}

	public function save_photo_meta( $post_id ) {
		Bio_Link_Logger::log( 'info', "Saving photo meta for post #{$post_id}" );

		if ( ! isset( $_POST['bio_link_photo_meta_nonce'] ) ||
		     ! wp_verify_nonce( $_POST['bio_link_photo_meta_nonce'], 'bio_link_photo_meta' ) ) {
			Bio_Link_Logger::log( 'error', "Save failed: nonce check failed for post #{$post_id}" );
			return;
		}

		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			Bio_Link_Logger::log( 'error', "Save failed: capability check failed for post #{$post_id}" );
			return;
		}

		$fields = array(
			'bio_link_instagram_url' => 'esc_url_raw',
			'bio_link_post_url'      => 'esc_url_raw',
			'bio_link_keyword'       => 'sanitize_text_field',
			'bio_link_dm_message'    => 'sanitize_textarea_field',
			'bio_link_dm_link'       => 'esc_url_raw',
			'bio_link_follow_gate'   => 'absint',
		);

		foreach ( $fields as $field => $sanitize ) {
			if ( isset( $_POST[ $field ] ) ) {
				$value = $sanitize( $_POST[ $field ] );
				update_post_meta( $post_id, '_' . $field, $value );
				Bio_Link_Logger::log( 'info', "Saved {$field} for post #{$post_id}" );
			}
		}

		Bio_Link_Logger::log( 'info', "Photo meta saved: post #{$post_id}" );
	}

	public function enqueue_admin_assets( $hook ) {
		wp_enqueue_script( 'bio-link-admin', BIO_LINK_PLUGIN_URL . 'assets/js/admin.js', array( 'jquery' ), BIO_LINK_VERSION, true );
		wp_localize_script( 'bio-link-admin', 'bioLinkAdmin', array(
			'ajaxUrl' => admin_url( 'admin-ajax.php' ),
			'nonce'   => wp_create_nonce( 'bio_link_admin_nonce' ),
			'serverUrl' => get_option( 'bio_link_middle_server_url', '' ),
			'i18n'    => array(
				'fetching'      => __( 'Fetching...', 'bio-link' ),
				'fetch_failed'    => __( 'Fetch failed. Check connection.', 'bio-link' ),
				'checking'        => __( 'Checking...', 'bio-link' ),
				'connected'       => __( 'Connected ✓', 'bio-link' ),
				'disconnected'    => __( 'Disconnected ✗', 'bio-link' ),
			),
		));
	}

	public function ajax_fetch_profile() {
		check_ajax_referer( 'bio_link_admin_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => 'Unauthorized' ) );
		}

		$username = isset( $_POST['username'] ) ? sanitize_text_field( $_POST['username'] ) : '';
		$use_middle = isset( $_POST['use_middle'] ) ? true : false;
		$server_url = get_option( 'bio_link_middle_server_url', '' );

		if ( empty( $username ) ) {
			wp_send_json_error( array( 'message' => 'No username provided' ) );
		}

		$username = ltrim( $username, '@' );
		Bio_Link_Logger::log( 'info', "Starting fetch for @{$username}" );

		if ( $use_middle && ! empty( $server_url ) ) {
			$photos = $this->fetch_via_middle_server( $username, $server_url, 15 );
		} else {
			$photos = $this->fetch_instagram_direct( $username, 15 );
		}

		if ( empty( $photos ) ) {
			Bio_Link_Logger::log( 'error', "Fetch FAILED for @{$username} - no photos found" );
			wp_send_json_error( array( 'message' => 'Could not fetch photos. Check Debug Log for details.' ) );
		}

		update_option( 'bio_link_ig_username', $username );

		$imported = 0;
		$skipped  = 0;
		foreach ( $photos as $photo ) {
			$existing = get_posts( array(
				'post_type'   => 'bio_link_photo',
				'meta_key'    => '_bio_link_instagram_url',
				'meta_value'  => $photo['url'],
				'numberposts' => 1,
			) );

			if ( ! empty( $existing ) ) {
				$skipped++;
				continue;
			}

			$post_id = wp_insert_post( array(
				'post_title'  => ! empty( $photo['caption'] ) ? wp_trim_words( $photo['caption'], 10 ) : $photo['shortcode'],
				'post_type'   => 'bio_link_photo',
				'post_status' => 'publish',
				'menu_order'  => $imported,
			) );

			if ( $post_id && ! is_wp_error( $post_id ) ) {
				if ( function_exists( 'wp_generate_attachment_metadata' ) ) {
					$image_url = $photo['image_url'];
					if ( $image_url ) {
						$tmp = download_url( $image_url );
						if ( ! is_wp_error( $tmp ) ) {
							$file_array = array( 'name' => basename( parse_url( $image_url, PHP_URL_PATH ) ), 'tmp_name' => $tmp );
							$attach_id = media_handle_sideload( $file_array, $post_id );
							if ( ! is_wp_error( $attach_id ) ) {
								set_post_thumbnail( $post_id, $attach_id );
							}
						}
					}
				}

				update_post_meta( $post_id, '_bio_link_instagram_url', $photo['url'] );
				update_post_meta( $post_id, '_bio_link_image_url', $photo['image_url'] );
				$imported++;

				Bio_Link_Logger::log( 'info', "Imported photo #{$post_id} - {$photo['url']}" );
			}
		}

		Bio_Link_Logger::log( 'info', "Fetch OK for @{$username}: {$imported} imported, {$skipped} skipped" );

		wp_send_json_success( array(
			'message'  => sprintf( 'Imported %d photos, %d already existed.', $imported, $skipped ),
			'imported' => $imported,
			'skipped'  => $skipped,
			'photos'   => $photos,
		) );
	}

	public function ajax_fetch_single() {
		check_ajax_referer( 'bio_link_admin_nonce', 'nonce' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => 'Unauthorized' ) );
		}

		$url = isset( $_POST['url'] ) ? esc_url_raw( $_POST['url'] ) : '';
		$post_id = isset( $_POST['post_id'] ) ? intval( $_POST['post_id'] ) : 0;
		$use_middle = isset( $_POST['use_middle'] ) ? true : false;
		$server_url = get_option( 'bio_link_middle_server_url', '' );

		if ( empty( $url ) ) {
			wp_send_json_error( array( 'message' => 'No URL provided' ) );
		}

		Bio_Link_Logger::log( 'info', "Fetching single image from {$url}" );

		$image_url = null;
		if ( $use_middle && ! empty( $server_url ) ) {
			$image_url = $this->fetch_single_via_middle( $url, $server_url );
		} else {
			$image_url = $this->fetch_single_direct( $url );
		}

		if ( ! $image_url ) {
			Bio_Link_Logger::log( 'error', "Single fetch FAILED for {$url}" );
			wp_send_json_error( array( 'message' => 'Could not fetch image. Check Debug Log.' ) );
		}

		if ( $post_id ) {
			$tmp = download_url( $image_url );
			if ( ! is_wp_error( $tmp ) ) {
				$file_array = array( 'name' => basename( parse_url( $image_url, PHP_URL_PATH ) ), 'tmp_name' => $tmp );
				$attach_id = media_handle_sideload( $file_array, $post_id );
				if ( ! is_wp_error( $attach_id ) ) {
					set_post_thumbnail( $post_id, $attach_id );
					update_post_meta( $post_id, '_bio_link_image_url', $image_url );
					wp_send_json_success( array(
						'message'   => 'Image fetched and set!',
						'image_url' => $image_url,
					) );
				}
			}
		}

		wp_send_json_success( array(
			'message'   => 'Image fetched!',
			'image_url' => $image_url,
		) );
	}

	private function fetch_single_via_middle( $url, $server_url ) {
		$base = untrailingslashit( $server_url );
		$api_url = add_query_arg( array(
			'url'  => $url,
			'size' => 'l',
		), $base . '/api/v1/image' );

		$api_key = get_option( 'bio_link_api_key', '' );
		$response = wp_remote_get( $api_url, array(
			'timeout' => 60,
			'headers' => array(
				'X-BioLink-Site' => md5( home_url() ),
				'X-BioLink-Key'  => $api_key,
			),
		) );

		if ( is_wp_error( $response ) ) {
			Bio_Link_Logger::log( 'error', 'Single fetch middle server error: ' . $response->get_error_message() );
			return null;
		}

		$code = wp_remote_retrieve_response_code( $response );
		Bio_Link_Logger::log( 'info', "Single fetch middle server HTTP: {$code}" );

		if ( 200 !== $code ) {
			$body = wp_remote_retrieve_body( $response );
			$json = json_decode( $body, true );
			$msg  = ! empty( $json['error'] ) ? $json['error'] : $body;
			Bio_Link_Logger::log( 'error', "Single fetch middle server error: {$msg}" );
			return null;
		}

		$content_type = wp_remote_retrieve_header( $response, 'content-type' );
		$body = wp_remote_retrieve_body( $response );
		if ( strpos( $content_type, 'image' ) !== false ) {
			return 'data:' . $content_type . ';base64,' . base64_encode( $body );
		}

		return null;
	}

	private function fetch_single_direct( $url ) {
		$shortcode = $this->extract_shortcode( $url );
		if ( ! $shortcode ) return null;

		$media_url = 'https://www.instagram.com/p/' . $shortcode . '/media/?size=l';
		$response = wp_remote_get( $media_url, array(
			'timeout' => 30,
			'headers' => array(
				'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
			),
		) );

		if ( is_wp_error( $response ) ) {
			Bio_Link_Logger::log( 'error', 'Single fetch direct error: ' . $response->get_error_message() );
			return null;
		}

		$img_url = wp_remote_retrieve_header( $response, 'location' );
		if ( $img_url ) {
			return $img_url;
		}

		return null;
	}

	public function ajax_check_server() {
		check_ajax_referer( 'bio_link_admin_nonce', 'nonce' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => 'Unauthorized' ) );
		}

		$server_url = isset( $_POST['url'] ) ? esc_url_raw( $_POST['url'] ) : get_option( 'bio_link_middle_server_url', '' );
		$api_key = get_option( 'bio_link_api_key', '' );

		Bio_Link_Logger::log( 'info', "Checking connection to middle server: {$server_url}" );

		if ( empty( $server_url ) ) {
			wp_send_json_error( array( 'message' => 'No server URL configured' ) );
		}

		$health_url = untrailingslashit( $server_url ) . '/api/v1/health';
		$response = wp_remote_get( $health_url, array(
			'timeout' => 10,
		) );

		if ( is_wp_error( $response ) ) {
			Bio_Link_Logger::log( 'error', 'Server check failed: ' . $response->get_error_message() );
			wp_send_json_error( array( 'message' => $response->get_error_message() ) );
		}

		$code = wp_remote_retrieve_response_code( $response );
		if ( 200 === $code ) {
			$body = json_decode( wp_remote_retrieve_body( $response ), true );
			Bio_Link_Logger::log( 'info', 'Server check OK: ' . ( $body['version'] ?? 'unknown' ) );
			wp_send_json_success( array( 'message' => 'Connected!', 'version' => $body['version'] ?? 'unknown' ) );
		}

		Bio_Link_Logger::log( 'error', "Server check failed: HTTP {$code}" );
		wp_send_json_error( array( 'message' => "HTTP {$code}" ) );
	}

	public function ajax_check_graph_api() {
		check_ajax_referer( 'bio_link_admin_nonce', 'nonce' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => 'Unauthorized' ) );
		}

		$token = isset( $_POST['token'] ) ? sanitize_text_field( $_POST['token'] ) : get_option( 'bio_link_ig_token', '' );

		Bio_Link_Logger::log( 'info', "Checking Instagram Graph API token" );

		if ( empty( $token ) ) {
			wp_send_json_error( array( 'message' => 'No token provided' ) );
		}

		$response = wp_remote_get( 'https://graph.facebook.com/v18.0/me?fields=id,name&access_token=' . $token, array(
			'timeout' => 15,
		) );

		if ( is_wp_error( $response ) ) {
			Bio_Link_Logger::log( 'error', 'Graph API check failed: ' . $response->get_error_message() );
			wp_send_json_error( array( 'message' => $response->get_error_message() ) );
		}

		$code = wp_remote_retrieve_response_code( $response );
		$body = json_decode( wp_remote_retrieve_body( $response ), true );

		if ( $code === 200 && ! empty( $body['id'] ) ) {
			Bio_Link_Logger::log( 'info', 'Graph API token valid: ' . ( $body['name'] ?? 'Unknown' ) );
			wp_send_json_success( array( 'message' => 'Valid! Account: ' . ( $body['name'] ?? 'Unknown' ) ) );
		}

		$error = $body['error']['message'] ?? 'Invalid token';
		Bio_Link_Logger::log( 'error', "Graph API token invalid: {$error}" );
		wp_send_json_error( array( 'message' => $error ) );
	}

	public function ajax_regenerate_api_key() {
		check_ajax_referer( 'bio_link_admin_nonce', 'nonce' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => 'Unauthorized' ) );
		}
		$key = wp_generate_password( 32, false );
		update_option( 'bio_link_api_key', $key );
		Bio_Link_Logger::log( 'info', 'API key regenerated' );
		wp_send_json_success( array( 'key' => $key ) );
	}

	public function ajax_clear_log() {
		check_ajax_referer( 'bio_link_admin_nonce', 'nonce' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( 'Unauthorized' );
		}
		Bio_Link_Logger::clear();
		wp_redirect( admin_url( 'admin.php?page=bio-link-debug' ) );
		exit;
	}

	private function extract_shortcode( $url ) {
		if ( preg_match( '/instagram\.com\/(?:p|reel|tv)\/([A-Za-z0-9_-]+)/', $url, $m ) ) {
			return $m[1];
		}
		return false;
	}

	private function fetch_instagram_direct( $username, $count = 15 ) {
		$photos = array();
		$profile_url = 'https://www.instagram.com/' . $username . '/';

		Bio_Link_Logger::log( 'info', "Fetching profile page: {$profile_url}" );

		$response = wp_remote_get( $profile_url, array(
			'timeout' => 30,
			'headers' => array(
				'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
				'Accept'     => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
			),
		) );

		if ( is_wp_error( $response ) ) {
			Bio_Link_Logger::log( 'error', 'Profile fetch error: ' . $response->get_error_message() );
			return $photos;
		}

		$http_code = wp_remote_retrieve_response_code( $response );
		Bio_Link_Logger::log( 'info', "Profile fetch HTTP: {$http_code}" );

		if ( 200 === $http_code ) {
			$html = wp_remote_retrieve_body( $response );

			if ( preg_match( '/window\._sharedData\s*=\s*(\{.+?\});<\/script>/s', $html, $matches ) ) {
				$data = json_decode( $matches[1], true );
				if ( ! empty( $data['entry_data']['ProfilePage'][0]['graphql']['user']['edge_owner_to_timeline_media']['edges'] ) ) {
					$edges = $data['entry_data']['ProfilePage'][0]['graphql']['user']['edge_owner_to_timeline_media']['edges'];
					Bio_Link_Logger::log( 'info', "Found " . count( $edges ) . " photos via _sharedData" );
					foreach ( $edges as $i => $edge ) {
						if ( $i >= $count ) break;
						$node = $edge['node'];
						$photos[] = array(
							'shortcode' => $node['shortcode'],
							'url'       => 'https://www.instagram.com/p/' . $node['shortcode'] . '/',
							'image_url' => $node['display_url'],
							'thumbnail' => $node['thumbnail_src'],
							'caption'   => ! empty( $node['edge_media_to_caption']['edges'][0]['node']['text'] ) ? $node['edge_media_to_caption']['edges'][0]['node']['text'] : '',
						);
					}
				}
			} else {
				Bio_Link_Logger::log( 'warning', 'Could not find _sharedData in profile page' );
			}
		}

		return $photos;
	}

	private function fetch_via_middle_server( $username, $server_url, $count = 15 ) {
		$base = untrailingslashit( $server_url );
		$url  = add_query_arg( array(
			'username' => $username,
			'count'    => $count,
			'token'    => get_option( 'bio_link_ig_token', '' ),
		), $base . '/api/v1/fetch-profile' );

		Bio_Link_Logger::log( 'info', "Fetching via middle server: {$url}" );

		$api_key = get_option( 'bio_link_api_key', '' );
		$response = wp_remote_get( $url, array(
			'timeout' => 60,
			'headers' => array(
				'X-BioLink-Site' => md5( home_url() ),
				'X-BioLink-Key'  => $api_key,
			),
		) );

		if ( is_wp_error( $response ) ) {
			Bio_Link_Logger::log( 'error', 'Middle server error: ' . $response->get_error_message() );
			return array();
		}

		$http_code = wp_remote_retrieve_response_code( $response );
		Bio_Link_Logger::log( 'info', "Middle server HTTP: {$http_code}" );

		if ( 200 !== $http_code ) {
			$body = wp_remote_retrieve_body( $response );
			$json = json_decode( $body, true );
			$msg  = ! empty( $json['error'] ) ? $json['error'] : $body;
			Bio_Link_Logger::log( 'error', "Middle server error: {$msg}" );
			return array();
		}

		$body = wp_remote_retrieve_body( $response );
		$data = json_decode( $body, true );

		if ( empty( $data['photos'] ) ) {
			return array();
		}

		$photos = array();
		foreach ( $data['photos'] as $p ) {
			$photos[] = array(
				'shortcode' => $p['shortcode'],
				'url'       => 'https://www.instagram.com/p/' . $p['shortcode'] . '/',
				'image_url' => $p['image_url'],
				'thumbnail' => $p['thumbnail'],
				'caption'   => $p['caption'],
			);
		}

		Bio_Link_Logger::log( 'info', "Middle server returned " . count( $photos ) . " photos" );
		return $photos;
	}

	public function add_help_tab() {
		$screen = get_current_screen();
		if ( ! $screen ) {
			return;
		}

		if ( strpos( $screen->id, 'bio-link' ) !== false || $screen->post_type === 'bio_link_photo' ) {
			$screen->add_help_tab( array(
				'id'      => 'bio_link_help',
				'title'   => __( 'Getting Started', 'bio-link' ),
				'callback' => array( $this, 'render_help_tab' ),
			) );

			$screen->add_help_tab( array(
				'id'      => 'bio_link_graph_api',
				'title'   => __( 'Instagram Graph API', 'bio-link' ),
				'callback' => array( $this, 'render_graph_api_help_tab' ),
			) );
		}
	}

	public function render_help_tab() {
		?>
		<h2><?php _e( 'Bio Link — Getting Started', 'bio-link' ); ?></h2>
		<ol>
			<li><?php _e( 'Deploy the middle server on Cloudflare Worker', 'bio-link' ); ?></li>
			<li><?php _e( 'Go to Settings → enter Middle Server URL + save', 'bio-link' ); ?></li>
			<li><?php _e( 'API Key auto-generates → auto-registers with your Worker', 'bio-link' ); ?></li>
			<li><?php _e( 'Dashboard → username → Fetch Last 15 Photos', 'bio-link' ); ?></li>
			<li><?php _e( 'Place [bio_link] shortcode on any page', 'bio-link' ); ?></li>
		</ol>
		<?php
	}

	public function render_graph_api_help_tab() {
		?>
		<h2><?php _e( 'Instagram Graph API Setup', 'bio-link' ); ?></h2>
		<p><?php _e( 'For DM automation you need an Instagram Graph API token:', 'bio-link' ); ?></p>
		<ol>
			<li><?php _e( 'Create a Facebook App at', 'bio-link' ); ?> <a href="https://developers.facebook.com/apps/" target="_blank">developers.facebook.com/apps</a></li>
			<li><?php _e( 'Add Instagram Graph API product', 'bio-link' ); ?></li>
			<li><?php _e( 'Permissions needed: instagram_basic, instagram_messaging, pages_messaging', 'bio-link' ); ?></li>
			<li><?php _e( 'Generate token at', 'bio-link' ); ?> <a href="https://developers.facebook.com/tools/explorer/" target="_blank">Graph API Explorer</a></li>
			<li><?php _e( 'Paste in Settings → Instagram Token', 'bio-link' ); ?></li>
		</ol>
		<p><a href="https://developers.facebook.com/docs/instagram-api/" target="_blank" class="button"><?php _e( 'Official Docs', 'bio-link' ); ?></a></p>
		<?php
	}
}
