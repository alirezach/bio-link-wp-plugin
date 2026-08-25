<?php
/**
 * Bio Link - Frontend Display Template
 *
 * @package Bio_Link
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$middle_server = new Bio_Link_Middle_Server();
?>

<div class="bio-link-container" data-layout="<?php echo esc_attr( $atts['layout'] ); ?>">
	<div class="bio-link-profile">
		<?php if ( $profile_photo ) : ?>
			<img src="<?php echo esc_url( $profile_photo ); ?>" alt="<?php esc_attr_e( 'Profile Photo', 'bio-link' ); ?>" class="bio-link-avatar" />
		<?php endif; ?>
		<?php if ( $bio_text ) : ?>
			<p class="bio-link-bio"><?php echo esc_html( $bio_text ); ?></p>
		<?php endif; ?>
		<?php if ( $show_followers && $followers > 0 ) : ?>
			<p class="bio-link-followers">
				<span class="bio-link-followers-icon" aria-hidden="true">👥</span>
				<span class="bio-link-followers-count"><?php echo esc_html( number_format_i18n( $followers ) ); ?></span>
				<span class="bio-link-followers-label"><?php esc_html_e( 'followers', 'bio-link' ); ?></span>
			</p>
		<?php endif; ?>
		<?php echo Bio_Link_Frontend::render_social_links( $social_links ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- icons are hardcoded, urls escaped inside. ?>
	</div>

	<div class="bio-link-grid">
		<?php foreach ( $photos as $photo ) : 
			$post_url      = get_post_meta( $photo->ID, '_bio_link_post_url', true );
			$has_link      = ! empty( $post_url );
			$instagram_url = get_post_meta( $photo->ID, '_bio_link_instagram_url', true );
			
			// Get image: try middle server first, then fall back to thumbnail
			$image_url = '';
			if ( $middle_server->is_configured() && $instagram_url ) {
				$image_url = $middle_server->get_image_url( $instagram_url, 'l' );
			}
			if ( ! $image_url ) {
				$image_url = get_the_post_thumbnail_url( $photo->ID, 'full' );
			}
			
			$classes = array( 'bio-link-item' );
			if ( ! $has_link ) {
				$classes[] = 'no-link';
			}
		?>
			<div class="<?php echo esc_attr( implode( ' ', $classes ) ); ?>" data-has-link="<?php echo $has_link ? '1' : '0'; ?>">
				<?php if ( $has_link ) : ?>
					<a href="<?php echo esc_url( $post_url ); ?>" target="_blank" rel="noopener noreferrer" class="bio-link-photo-link">
				<?php endif; ?>
				
				<?php if ( $image_url ) : ?>
					<img src="<?php echo esc_url( $image_url ); ?>" alt="<?php echo esc_attr( $photo->post_title ); ?>" class="bio-link-photo" loading="lazy" />
				<?php else : ?>
					<div class="bio-link-photo bio-link-photo-placeholder"></div>
				<?php endif; ?>
				
				<span class="bio-link-title"><?php echo esc_html( $photo->post_title ); ?></span>
				
				<?php if ( $has_link ) : ?>
					</a>
				<?php endif; ?>
			</div>
		<?php endforeach; ?>
	</div>
</div>
