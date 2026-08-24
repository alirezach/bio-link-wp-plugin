<?php
/**
 * Bio Link - Admin Settings Template
 *
 * @package Bio_Link
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="wrap bio-link-admin">
	<h1><?php _e( 'Bio Link Settings', 'bio-link' ); ?></h1>
	
	<form method="post" action="options.php">
		<?php settings_fields( 'bio_link_settings' ); ?>
		
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
			<tr>
				<th scope="row"><?php _e( 'Middle Server URL', 'bio-link' ); ?></th>
				<td>
					<input type="url" name="bio_link_middle_server_url" value="<?php echo esc_url( get_option( 'bio_link_middle_server_url' ) ); ?>" class="regular-text" placeholder="https://middle.yourdomain.com" />
					<p class="description"><?php _e( 'URL of your Bio Link middle server (Cloudflare Worker or VPS).', 'bio-link' ); ?></p>
				</td>
			</tr>
			<tr>
				<th scope="row"><?php _e( 'API Key', 'bio-link' ); ?></th>
				<td>
					<input type="text" name="bio_link_api_key" value="<?php echo esc_attr( get_option( 'bio_link_api_key' ) ); ?>" class="regular-text" />
					<p class="description"><?php _e( 'API key for the middle server.', 'bio-link' ); ?></p>
				</td>
			</tr>
			<tr>
				<th scope="row"><?php _e( 'Instagram Token', 'bio-link' ); ?></th>
				<td>
					<input type="password" name="bio_link_ig_token" value="<?php echo esc_attr( get_option( 'bio_link_ig_token' ) ); ?>" class="regular-text" />
					<p class="description"><?php _e( 'Instagram Graph API token (for DM automation).', 'bio-link' ); ?></p>
				</td>
			</tr>
		</table>
		
		<?php submit_button(); ?>
	</form>
</div>
