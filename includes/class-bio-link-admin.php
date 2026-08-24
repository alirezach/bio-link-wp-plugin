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
	}

	public function render_dashboard_page() {
		$photos = get_posts( array(
			'post_type'      => 'bio_link_photo',
			'posts_per_page' => -1,
			'orderby'        => 'menu_order',
			'order'          => 'ASC',
		) );
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
				<td><input type="url" id="bio_link_instagram_url" name="bio_link_instagram_url" value="<?php echo esc_url( $instagram_url ); ?>" class="regular-text" />
				<p class="description"><?php _e( 'Leave empty if using manual image upload only.', 'bio-link' ); ?></p></td>
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
}
