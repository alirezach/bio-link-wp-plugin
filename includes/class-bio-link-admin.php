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
			__( 'Dashboard', 'bio-link' ),
			__( 'Dashboard', 'bio-link' ),
			'manage_options',
			'bio-link',
			array( $this, 'render_dashboard_page' )
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
		include BIO_LINK_PLUGIN_DIR . 'templates/admin-dashboard.php';
	}

	public function render_settings_page() {
		include BIO_LINK_PLUGIN_DIR . 'templates/admin-settings.php';
	}

	public function render_dm_page() {
		include BIO_LINK_PLUGIN_DIR . 'templates/admin-dm.php';
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

	/**
	 * AJAX: Fetch last 15 photos from an Instagram profile
	 */
	public function ajax_fetch_profile() {
		check_ajax_referer( 'bio_link_admin_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Unauthorized', 'bio-link' ) ) );
		}

		$username = isset( $_POST['username'] ) ? sanitize_text_field( $_POST['username'] ) : '';
		if ( empty( $username ) ) {
			wp_send_json_error( array( 'message' => __( 'No username provided', 'bio-link' ) ) );
		}

		// Strip @ if present
		$username = ltrim( $username, '@' );

		$photos = $this->fetch_instagram_profile_photos( $username, 15 );

		if ( empty( $photos ) ) {
			wp_send_json_error( array( 'message' => __( 'Could not fetch photos. Make sure the profile is public.', 'bio-link' ) ) );
		}

		// Save username
		update_option( 'bio_link_ig_username', $username );

		// Auto-create photo posts
		$imported = 0;
		$skipped  = 0;
		foreach ( $photos as $photo ) {
			// Check if already exists by Instagram URL
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
				'post_title'  => $photo['caption'] ? $photo['caption'] : $photo['shortcode'],
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

		wp_send_json_success( array(
			'message'  => sprintf( __( 'Imported %d photos, %d already existed.', 'bio-link' ), $imported, $skipped ),
			'imported' => $imported,
			'skipped'  => $skipped,
			'photos'   => $photos,
		) );
	}

	/**
	 * Fetch last N photos from an Instagram public profile
	 */
	private function fetch_instagram_profile_photos( $username, $count = 15 ) {
		$photos = array();

		// Strategy 1: Public profile page scrape
		$profile_url = 'https://www.instagram.com/' . $username . '/';
		$response   = wp_remote_get( $profile_url, array(
			'timeout'  => 30,
			'headers'  => array(
				'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
				'Accept'     => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8',
			),
		) );

		if ( ! is_wp_error( $response ) && 200 === wp_remote_retrieve_response_code( $response ) ) {
			$html = wp_remote_retrieve_body( $response );

			// Try to find sharedData JSON
			if ( preg_match( '/window\._sharedData\s*=\s*(\{.+?\});<\/script>/s', $html, $matches ) ) {
				$data = json_decode( $matches[1], true );
				if ( ! empty( $data['entry_data']['ProfilePage'][0]['graphql']['user']['edge_owner_to_timeline_media']['edges'] ) ) {
					$edges = $data['entry_data']['ProfilePage'][0]['graphql']['user']['edge_owner_to_timeline_media']['edges'];
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
			}

			// Strategy 2: Look for og:image meta tags (gives only the most recent)
			if ( empty( $photos ) ) {
				if ( preg_match_all( '/<meta\s+property="og:image"\s+content="([^"]+)"/', $html, $meta_matches ) ) {
					foreach ( $meta_matches[1] as $i => $img_url ) {
						if ( $i >= $count ) break;
						$shortcode = 'imported_' . $i . '_' . time();
						$photos[] = array(
							'shortcode' => $shortcode,
							'url'       => $profile_url,
							'image_url' => $img_url,
							'thumbnail' => $img_url,
							'caption'   => '',
						);
					}
				}
			}
		}

		// Strategy 3: Try the public API endpoint (sometimes works)
		if ( empty( $photos ) ) {
			$api_url  = 'https://www.instagram.com/api/v1/users/web_profile_info/?username=' . $username;
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
			}
		}

		return $photos;
	}
}
