<?php
/**
 * Bio Link - DM Automation Settings Template
 *
 * @package Bio_Link
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$middle_server = new Bio_Link_Middle_Server();
$configured = $middle_server->is_configured();
?>
<div class="wrap bio-link-admin">
	<h1><?php _e( 'DM Automation', 'bio-link' ); ?></h1>
	
	<?php if ( ! $configured ) : ?>
		<div class="notice notice-warning">
			<p><?php _e( 'Middle server is not configured. Please configure it in Settings first.', 'bio-link' ); ?></p>
		</div>
	<?php else : ?>
		<div class="notice notice-success">
			<p><?php _e( 'Middle server connected.', 'bio-link' ); ?></p>
		</div>
	<?php endif; ?>

	<h2><?php _e( 'How it works', 'bio-link' ); ?></h2>
	<ol>
		<li><?php _e( 'Set a trigger keyword for each photo (e.g. "guide", "راهنما").', 'bio-link' ); ?></li>
		<li><?php _e( 'When an Instagram user comments that keyword on your post, they receive a DM.', 'bio-link' ); ?></li>
		<li><?php _e( 'The DM contains your configured message and optional link.', 'bio-link' ); ?></li>
		<li><?php _e( 'Optional: require the commenter to follow your account first.', 'bio-link' ); ?></li>
	</ol>

	<h2><?php _e( 'Requirements', 'bio-link' ); ?></h2>
	<ul>
		<li>✅ <?php _e( 'Instagram Business or Creator account', 'bio-link' ); ?></li>
		<li>✅ <?php _e( 'Facebook App with Instagram Graph API', 'bio-link' ); ?></li>
		<li>✅ <?php _e( 'Instagram Messaging permission (requires App Review)', 'bio-link' ); ?></li>
		<li>✅ <?php _e( 'Middle server deployed and configured', 'bio-link' ); ?></li>
	</ul>
</div>
