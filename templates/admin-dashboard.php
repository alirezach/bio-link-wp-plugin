<?php
/**
 * Bio Link - Admin Dashboard Template
 *
 * @package Bio_Link
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="wrap bio-link-admin">
	<h1><?php _e( 'Bio Link Dashboard', 'bio-link' ); ?></h1>
	
	<!-- Import from Instagram -->
	<div class="bio-link-import-box" style="background:#fff;border:1px solid #ccd0d4;padding:20px;margin:20px 0;border-radius:4px;">
		<h2><?php _e( 'Import from Instagram', 'bio-link' ); ?></h2>
		<table class="form-table">
			<tr>
				<th><label for="bio_link_ig_username"><?php _e( 'Instagram Username', 'bio-link' ); ?></label></th>
				<td>
					<input type="text" id="bio_link_ig_username" value="<?php echo esc_attr( $ig_username ); ?>" class="regular-text" placeholder="username (without @)" />
					<button type="button" class="button button-primary" id="bio_link_import_btn"><?php _e( 'Fetch Last 15 Photos', 'bio-link' ); ?></button>
					<span id="bio_link_import_status" style="margin-left:10px;"></span>
					<p class="description"><?php _e( 'Enter a public Instagram username and click Fetch to automatically import the last 15 photos.', 'bio-link' ); ?></p>
				</td>
			</tr>
			<tr>
				<th><?php _e( 'Options', 'bio-link' ); ?></th>
				<td>
					<label>
						<input type="checkbox" id="bio_link_use_middle" <?php echo $configured ? 'checked' : ''; ?> />
						<?php _e( 'Use Middle Server (recommended if your host blocks Instagram)', 'bio-link' ); ?>
					</label>
					<?php if ( ! $configured ) : ?>
						<p class="description" style="color:#dba617;">
							<?php _e( '⚠️ Middle server not configured. Go to Settings to set it up, or import will fail.', 'bio-link' ); ?>
						</p>
					<?php endif; ?>
				</td>
			</tr>
		</table>
		<div id="bio_link_import_preview" style="margin-top:15px;"></div>
	</div>

	<!-- Stats -->
	<div class="bio-link-stats">
		<div class="bio-link-stat-box">
			<span class="bio-link-stat-number"><?php echo count( $photos ); ?></span>
			<span class="bio-link-stat-label"><?php _e( 'Photos', 'bio-link' ); ?></span>
		</div>
	</div>

	<h2><?php _e( 'Photos', 'bio-link' ); ?></h2>
	<table class="wp-list-table widefat fixed striped">
		<thead>
			<tr>
				<th><?php _e( 'Photo', 'bio-link' ); ?></th>
				<th><?php _e( 'Title', 'bio-link' ); ?></th>
				<th><?php _e( 'Has Link', 'bio-link' ); ?></th>
				<th><?php _e( 'DM Keyword', 'bio-link' ); ?></th>
			</tr>
		</thead>
		<tbody>
			<?php foreach ( $photos as $photo ) : 
				$post_url = get_post_meta( $photo->ID, '_bio_link_post_url', true );
				$keyword  = get_post_meta( $photo->ID, '_bio_link_keyword', true );
			?>
				<tr>
					<td><?php echo get_the_post_thumbnail( $photo->ID, 'thumbnail' ); ?></td>
					<td><strong><?php echo esc_html( $photo->post_title ); ?></strong></td>
					<td><?php echo $post_url ? '✅' : '⚫'; ?></td>
					<td><?php echo $keyword ? esc_html( $keyword ) : '—'; ?></td>
				</tr>
			<?php endforeach; ?>
		</tbody>
	</table>

	<p><a href="<?php echo admin_url( 'post-new.php?post_type=bio_link_photo' ); ?>" class="button button-primary"><?php _e( 'Add New Photo', 'bio-link' ); ?></a></p>
</div>
