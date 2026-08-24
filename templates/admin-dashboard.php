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
