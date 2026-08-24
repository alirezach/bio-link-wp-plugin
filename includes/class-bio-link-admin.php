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
		add_action( 'wp_ajax_bio_link_fetch_profile', array( $this, 'ajax_fetch_profile' ) );
		add_action( 'wp_ajax_bio_link_regenerate_api_key', array( $this, 'ajax_regenerate_api_key' ) );
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
	}

	public function render_dashboard_page() {
		$photos = get_posts( array(
			'post_type'      => 'bio_link_photo',
			'posts_per_page' => -1,
			'orderby'        => 'menu_order',
			'order'          => 'ASC',
		) );
		$ig_username = get_option( 'bio_link_ig_username', '' );
		$api_key = get_option( 'bio_link_api_key', '' );
		$server_url = get_option( 'bio_link_middle_server_url', '' );
		include BIO_LINK_PLUGIN_DIR . 'templates/admin-dashboard.php';
	}

	public function render_settings_page() {
		$api_key = get_option( 'bio_link_api_key', '' );
		$server_url = get_option( 'bio_link_middle_server_url', '' );
		$ig_token = get_option( 'bio_link_ig_token', '' );
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
					<p class="description"><?php _e( 'Single post URL. For bulk import, use the Import page.', 'bio-link' ); ?></p></td>
			</tr>
			<tr>
				<th><label for="bio_link_post_url"><?php _e( 'Post Link', 'bio-link' ); ?></label></th>
				<td><input type="url" id="bio_link_post_url" name="bio_link_post_url" value="<?php echo esc_url( $post_url ); ?>" class="regular-text" />
				<p class="description"><?php _e( 'Link when user clicks this photo. Leave empty for black-and-white rendering.', 'bio-link' ); ?></p></td>
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
		if ( ! isset( $_POST['bio_link_photo_meta_nonce'] ) ||
		     ! wp_verify_nonce( $_POST['bio_link_photo_meta_nonce'], 'bio_link_photo_meta' ) ) {
			return;
		}

		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		if ( ! current_user_can( 'edit_post', $post_id ) ) {
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
				update_post_meta( $post_id, '_' . $field, $sanitize( $_POST[ $field ] ) );
			} else {
				delete_post_meta( $post_id, '_' . $field );
			}
		}
	}

	public function enqueue_admin_assets( $hook ) {
		if ( 'toplevel_page_bio-link' !== $hook && 'bio-link_page_bio-link-settings' !== $hook ) {
			return;
		}
		wp_enqueue_script( 'bio-link-admin', BIO_LINK_PLUGIN_URL . 'assets/js/admin.js', array( 'jquery' ), BIO_LINK_VERSION, true );
		wp_localize_script( 'bio-link-admin', 'bioLinkAdmin', array(
			'ajaxUrl' => admin_url( 'admin-ajax.php' ),
			'nonce'   => wp_create_nonce( 'bio_link_admin_nonce' ),
		));
	}

	public function ajax_regenerate_api_key() {
		check_ajax_referer( 'bio_link_admin_nonce', 'nonce' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => 'Unauthorized' ) );
		}
		$key = wp_generate_password( 32, false );
		update_option( 'bio_link_api_key', $key );
		wp_send_json_success( array( 'key' => $key ) );
	}

	public function ajax_fetch_profile() {
		check_ajax_referer( 'bio_link_admin_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Unauthorized', 'bio-link' ) ) );
		}

		$username = isset( $_POST['username'] ) ? sanitize_text_field( $_POST['username'] ) : '';
		if ( empty( $username ) ) {
			wp_send_json_error( array( 'message' => __( 'No username provided', 'bio-link' ) ) );
		}

		$username = ltrim( $username, '@' );
		$this->log( "Starting fetch for @{$username}" );

		$photos = $this->fetch_instagram_profile_photos( $username, 15 );

		if ( empty( $photos ) ) {
			$this->log( "Fetch FAILED for @{$username} - no photos found", 'error' );
			wp_send_json_error( array( 'message' => __( 'Could not fetch photos. Make sure the profile is public.', 'bio-link' ) ) );
		}

		update_option( 'bio_link_ig_username', $username );

		$imported = 0;
		$skipped  = 0;
		foreach ( $photos as $photo ) {
			$existing = get_posts( array(
				'post_type'  => 'bio_link_photo',
				'meta_key'   => '_bio_link_instagram_url',
				'meta_value' => $photo['url'],
				'numberposts' => 1,
			) );

			if ( ! empty( $existing ) ) {
				$skipped++;
				continue;
			}

			$post_id = wp_insert_post( array(
				'post_title'  => $photo['caption'] ? wp_trim_words( $photo['caption'], 10 ) : $photo['shortcode'],
				'post_type'   => 'bio_link_photo',
				'post_status' => 'publish',
				'menu_order'  => $imported,
			) );

			if ( $post_id && ! is_wp_error( $post_id ) ) {
				update_post_meta( $post_id, '_bio_link_instagram_url', $photo['url'] );
				update_post_meta( $post_id, '_bio_link_image_url', $photo['image_url'] );
				$imported++;
			}
		}

		$this->log( "Fetch OK for @{$username}: {$imported} imported, {$skipped} skipped" );

		wp_send_json_success( array(
			'message'  => sprintf( __( 'Imported %d photos, %d already existed.', 'bio-link' ), $imported, $skipped ),
			'imported' => $imported,
			'skipped'  => $skipped,
			'photos'   => $photos,
		) );
	}

	private function fetch_instagram_profile_photos( $username, $count = 15 ) {
		$photos = array();

		$profile_url = 'https://www.instagram.com/' . $username . '/';
		$this->log( "Fetching profile page: {$profile_url}" );

		$response = wp_remote_get( $profile_url, array(
			'timeout' => 30,
			'headers' => array(
				'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
				'Accept'     => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
			),
		) );

		if ( is_wp_error( $response ) ) {
			$this->log( "Profile fetch error: " . $response->get_error_message(), 'error' );
			return $photos;
		}

		$http_code = wp_remote_retrieve_response_code( $response );
		$this->log( "Profile fetch HTTP: {$http_code}" );

		if ( 200 === $http_code ) {
			$html = wp_remote_retrieve_body( $response );

			if ( preg_match( '/window\._sharedData\s*=\s*(\{.+?\});<\/script>/s', $html, $matches ) ) {
				$data = json_decode( $matches[1], true );
				if ( ! empty( $data['entry_data']['ProfilePage'][0]['graphql']['user']['edge_owner_to_timeline_media']['edges'] ) ) {
					$edges = $data['entry_data']['ProfilePage'][0]['graphql']['user']['edge_owner_to_timeline_media']['edges'];
					$this->log( "Found " . count( $edges) . " photos via _sharedData" );
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
				$this->log( "Could not find _sharedData in profile page", 'warning' );
			}
		}

		if ( empty( $photos ) ) {
			$this->log( "Trying public API endpoint..." );
			$api_url = 'https://www.instagram.com/api/v1/users/web_profile_info/?username=' . $username;
			$response = wp_remote_get( $api_url, array(
				'timeout' => 30,
				'headers' => array(
					'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
					'Accept'     => '*/*',
					'X-IG-App-ID' => '936619743392459',
				),
			) );

			if ( ! is_wp_error( $response ) && 200 === wp_remote_retrieve_response_code( $response ) ) {
				$body = wp_remote_retrieve_body( $response );
				$data = json_decode( $body, true );
				if ( ! empty( $data['data']['user']['edge_owner_to_timeline_media']['edges'] ) ) {
					$edges = $data['data']['user']['edge_owner_to_timeline_media']['edges'];
					$this->log( "Found " . count( $edges) . " photos via API" );
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
				$code = is_wp_error( $response ) ? $response->get_error_message() : wp_remote_retrieve_response_code( $response );
				$this->log( "API fetch failed: {$code}", 'error' );
			}
		}

		return $photos;
	}

	public function add_help_tab() {
		$screen = get_current_screen();
		if ( ! $screen || strpos( $screen->id, 'bio-link' ) === false ) {
			return;
		}

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

	public function render_help_tab() {
		?>
		<h2><?php _e( 'Bio Link — Getting Started', 'bio-link' ); ?></h2>
		<ol>
			<li><?php _e( 'Go to <strong>Settings</strong> and enter your Instagram username.', 'bio-link' ); ?></li>
			<li><?php _e( 'Go to <strong>Dashboard</strong> → enter username → click <strong>Fetch Last 15 Photos</strong>.', 'bio-link' ); ?></li>
			<li><?php _e( 'Photos are imported. Set links per photo to make them colored.', 'bio-link' ); ?></li>
			<li><?php _e( 'Place <code>[bio_link]</code> shortcode on any page.', 'bio-link' ); ?></li>
		</ol>
		<p><?php _e( 'For DM automation, you need Instagram Graph API access.', 'bio-link' ); ?></p>
		<?php
	}

	public function render_graph_api_help_tab() {
		?>
		<h2><?php _e( 'Instagram Graph API', 'bio-link' ); ?></h2>
		<p><?php _e( 'To use DM automation, you need an Instagram Graph API token.', 'bio-link' ); ?></p>
		<ol>
			<li><?php _e( 'Create a Facebook App at', 'bio-link' ); ?> <a href="https://developers.facebook.com/apps/" target="_blank">developers.facebook.com/apps</a></li>
			<li><?php _e( 'Add <strong>Instagram Graph API</strong> product to your app.', 'bio-link' ); ?></li>
			<li><?php _e( 'Add permissions: <code>instagram_basic</code>, <code>instagram_messaging</code>, <code>pages_messaging</code>.', 'bio-link' ); ?></li>
			<li><?php _e( 'Generate a long-lived Page Access Token using the <a href="https://developers.facebook.com/tools/explorer/" target="_blank">Graph API Explorer</a>.', 'bio-link' ); ?></li>
			<li><?php _e( 'Paste the token in <strong>Settings</strong> → <strong>Instagram Token</strong>.', 'bio-link' ); ?></li>
		</ol>
		<p><a href="https://developers.facebook.com/docs/instagram-api/" target="_blank" class="button"><?php _e( 'Official Docs', 'bio-link' ); ?></a></p>
		<?php
	}

	private function log( $message, $level = 'info' ) {
		$log = get_option( 'bio_link_debug_log', array() );
		$log[] = array(
			'time'    => current_time( 'mysql' ),
			'level'   => $level,
			'message' => $message,
		);
		// Keep last 100 entries
		if ( count( $log ) > 100 ) {
			$log = array_slice( $log, -100 );
		}
		update_option( 'bio_link_debug_log', $log );
	}
}
