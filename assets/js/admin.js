/**
 * Bio Link - Admin Scripts
 */
(function($) {
	'use strict';

	$(function() {
		// Instagram fetch button
		$('#bio_link_fetch_btn').on('click', function(e) {
			e.preventDefault();
			var url = $('#bio_link_instagram_url').val();
			if (!url) {
				alert('Please enter an Instagram URL first.');
				return;
			}

			var $btn = $(this);
			var $status = $('#bio_link_fetch_status');
			$btn.prop('disabled', true);
			$status.text('Fetching...');

			$.post(bioLinkAdmin.ajaxUrl, {
				action: 'bio_link_fetch_instagram',
				nonce: bioLinkAdmin.nonce,
				url: url
			}, function(resp) {
				$btn.prop('disabled', false);
				if (resp.success) {
					$status.text('✓ Image fetched! Save to apply.');
					// Set featured image if possible, or show preview
					$status.append(' <img src="' + resp.data.image_url + '" style="max-height:60px;vertical:middle;margin-left:8px;" />');
				} else {
					$status.text('✗ ' + (resp.data && resp.data.message ? resp.data.message : 'Failed'));
				}
			}).fail(function() {
				$btn.prop('disabled', false);
				$status.text('✗ Request failed');
			});
		});
	});

})(jQuery);
