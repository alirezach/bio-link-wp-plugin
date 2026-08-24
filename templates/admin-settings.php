<?php
/**
 * Bio Link - Settings Template
 *
 * @package Bio_Link
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// If API key is empty, generate one
if ( empty( $api_key ) ) {
	$api_key = wp_generate_password( 32, false );
	update_option( 'bio_link_api_key', $api_key );
}
?>
<div class="wrap bio-link-admin">
	<h1><?php _e( 'Bio Link Settings', 'bio-link' ); ?></h1>
	
	<form method="post" action="options.php">
		<?php settings_fields( 'bio_link_settings' ); ?>
		
		<h2><?php _e( 'Profile', 'bio-link' ); ?></h2>
		<table class="form-table">
			<tr>
				<th scope="row"><?php _e( 'Profile Photo', 'bio-link' ); ?></th>
				<td>
					<input type="url" name="bio_link_profile_photo" value="<?php echo esc_url( get_option( 'bio_link_profile_photo' ) ); ?>" class="regular-text" />
					<p class="description"><?php _e( 'URL to your profile photo.', 'bio-link' ); ?></p>
				</td>
			</tr>
			<tr>
				<th scope="row"><?php _e( 'Bio Text', 'bio-link' ); ?></th>
				<td>
					<textarea name="bio_link_bio_text" rows="3" class="regular-text"><?php echo esc_textarea( get_option( 'bio_link_bio_text' ) ); ?></textarea>
					<p class="description"><?php _e( 'Short bio displayed on your bio link page.', 'bio-link' ); ?></p>
				</td>
			</tr>
		</table>

		<h2><?php _e( 'Middle Server', 'bio-link' ); ?></h2>
		<table class="form-table">
			<tr>
				<th scope="row"><?php _e( 'Server URL', 'bio-link' ); ?></th>
				<td>
					<input type="url" name="bio_link_middle_server_url" value="<?php echo esc_url( $server_url ); ?>" class="regular-text" placeholder="https://middle.yourdomain.com" />
					<p class="description"><?php _e( 'URL of your Bio Link middle server (Cloudflare Worker or VPS).', 'bio-link' ); ?></p>
				</td>
			</tr>
			<tr>
				<th scope="row"><?php _e( 'API Key', 'bio-link' ); ?></th>
				<td>
					<input type="text" value="<?php echo esc_attr( $api_key ); ?>" class="regular-text" readonly style="background:#f0f0f0;" />
					<button type="button" class="button" id="bio_link_regenerate_key"><?php _e( 'Regenerate', 'bio-link' ); ?></button>
					<p class="description">
						<?php _e( 'Copy this key to your middle server config.', 'bio-link' ); ?>
						<br>
						<strong><?php _e( 'Cloudflare Worker:', 'bio-link' ); ?></strong>
						<code>wrangler secret put BIO_LINK_API_KEY</code> → paste this key
					</p>
				</td>
			</tr>
		</table>

		<h2><?php _e( 'Instagram Graph API', 'bio-link' ); ?> <a href="#" target="_blank" title="<?php _e( 'How to get this token?', 'bio-link' ); ?>" style="text-decoration:none;">ℹ️</a></h2>
		<table class="form-table">
			<tr>
				<th scope="row"><?php _e( 'Page Access Token', 'bio-link' ); ?></th>
				<td>
					<input type="password" name="bio_link_ig_token" value="<?php echo esc_attr( $ig_token ); ?>" class="regular-text" />
					<p class="description">
						<?php _e( 'Required for DM automation.', 'bio-link' ); ?>
						<a href="#" target="_blank"><?php _e( 'How to get a Graph API token?', 'bio-link' ); ?></a>
					</p>
				</td>
			</tr>
		</table>
		
		<?php submit_button(); ?>
	</form>
</div>
