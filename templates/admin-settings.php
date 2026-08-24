<?php
/**
 * Bio Link - Settings Template
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
		
		<h2><?php _e( 'Profile', 'bio-link' ); ?></h2>
		<table class="form-table">
			<tr>
				<th scope="row"><?php _e( 'Profile Photo', 'bio-link' ); ?></th>
				<td><input type="url" name="bio_link_profile_photo" value="<?php echo esc_url( get_option( 'bio_link_profile_photo' ) ); ?>" class="regular-text" /></td>
			</tr>
			<tr>
				<th scope="row"><?php _e( 'Bio Text', 'bio-link' ); ?></th>
				<td><textarea name="bio_link_bio_text" rows="3" class="regular-text"><?php echo esc_textarea( get_option( 'bio_link_bio_text' ) ); ?></textarea></td>
			</tr>
		</table>

		<h2><?php _e( 'Middle Server', 'bio-link' ); ?></h2>
		<table class="form-table">
			<tr>
				<th scope="row"><?php _e( 'Server URL', 'bio-link' ); ?> <span class="check-connection" style="display:none;" id="server_check"></span></th>
				<td>
					<input type="url" name="bio_link_middle_server_url" id="bio_link_server_url" value="<?php echo esc_url( get_option( 'bio_link_middle_server_url' ) ); ?>" class="regular-text" placeholder="https://bio-link-middle-server.YOUR_SUBDOMAIN.workers.dev" />
					<button type="button" class="button" id="bio_link_check_server"><?php _e( 'Check Connection', 'bio-link' ); ?></button>
					<p class="description"><?php _e( 'URL of your Cloudflare Worker or VPS middle server.', 'bio-link' ); ?></p>
				</td>
			</tr>
			<tr>
				<th scope="row"><?php _e( 'API Key', 'bio-link' ); ?></th>
				<td>
					<input type="text" id="bio_link_api_key_display" value="<?php echo esc_attr( get_option( 'bio_link_api_key', '' ) ); ?>" class="regular-text" readonly style="background:#f7f7f7;" />
					<button type="button" class="button" id="bio_link_regenerate_key"><?php _e( 'Regenerate', 'bio-link' ); ?></button>
					<button type="button" class="button" id="bio_link_copy_key"><?php _e( 'Copy', 'bio-link' ); ?></button>
					<p class="description">
						<?php _e( 'Copy this key to your Worker config:', 'bio-link' ); ?>
						<br>
						<code>wrangler secret put BIO_LINK_API_KEY</code> → paste this key
					</p>
				</td>
			</tr>
		</table>

		<h2><?php _e( 'Instagram Graph API', 'bio-link' ); ?> <a href="#" class="question-mark">ℹ️</a></h2>
		<table class="form-table">
			<tr>
				<th scope="row"><?php _e( 'Page Access Token', 'bio-link' ); ?> <span class="graph-check" id="graph_check"></span></th>
				<td>
					<input type="password" name="bio_link_ig_token" id="bio_link_ig_token" value="<?php echo esc_attr( get_option( 'bio_link_ig_token', '' ) ); ?>" class="regular-text" />
					<button type="button" class="button" id="bio_link_check_graph"><?php _e( 'Check Connection', 'bio-link' ); ?></button>
					<p class="description">
						<?php _e( 'Required for DM automation.', 'bio-link' ); ?>
						<a href="#" class="graph-api-help"><?php _e( 'How to get a Graph API token?', 'bio-link' ); ?></a>
					</p>
				</td>
			</tr>
		</table>

		<h2><?php _e( 'Debug', 'bio-link' ); ?></h2>
		<table class="form-table">
			<tr>
				<th scope="row"><?php _e( 'Enable Debug Log', 'bio-link' ); ?></th>
				<td>
					<label><input type="radio" name="bio_link_debug_enabled" value="1" <?php checked( get_option( 'bio_link_debug_enabled', 1 ), '1' ); ?>> <?php _e( 'Enabled', 'bio-link' ); ?></label>
					<label><input type="radio" name="bio_link_debug_enabled" value="0" <?php checked( get_option( 'bio_link_debug_enabled', 1 ), '0' ); ?>> <?php _e( 'Disabled', 'bio-link' ); ?></label>
					<p class="description"><?php _e( 'Log all fetch attempts, API calls, and errors.', 'bio-link' ); ?> <a href="<?php echo admin_url( 'admin.php?page=bio-link-debug' ); ?>"><?php _e( 'View Debug Log', 'bio-link' ); ?></a></p>
				</td>
			</tr>
		</table>
		
		<?php submit_button(); ?>
	</form>
</div>
