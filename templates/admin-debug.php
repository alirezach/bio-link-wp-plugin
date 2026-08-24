<?php
/**
 * Bio Link - Debug Log Template
 *
 * @package Bio_Link
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$log = get_option( 'bio_link_debug_log', array() );
$log = array_reverse( $log ); // newest first
?>
<div class="wrap bio-link-admin">
	<h1><?php _e( 'Debug Log', 'bio-link' ); ?></h1>
	
	<p>
		<a href="<?php echo wp_nonce_url( admin_url( 'admin-ajax.php?action=bio_link_clear_log' ), 'bio_link_admin_nonce' ); ?>" class="button"><?php _e( 'Clear Log', 'bio-link' ); ?></a>
	</p>

	<?php if ( empty( $log ) ) : ?>
		<p><?php _e( 'No log entries yet.', 'bio-link' ); ?></p>
	<?php else : ?>
		<table class="wp-list-table widefat fixed striped">
			<thead>
				<tr>
					<th><?php _e( 'Time', 'bio-link' ); ?></th>
					<th><?php _e( 'Level', 'bio-link' ); ?></th>
					<th><?php _e( 'Message', 'bio-link' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ( $log as $entry ) : 
					$level_class = '';
					if ( 'error' === $entry['level'] ) $level_class = 'color:#dc3232;';
					elseif ( 'warning' === $entry['level'] ) $level_class = 'color:#dba617;';
				?>
					<tr>
						<td><code><?php echo esc_html( $entry['time'] ); ?></code></td>
						<td style="<?php echo $level_class; ?>"><?php echo esc_html( $entry['level'] ); ?></td>
						<td><?php echo esc_html( $entry['message'] ); ?></td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
	<?php endif; ?>
</div>
