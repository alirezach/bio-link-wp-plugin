/**
 * Bio Link - Admin Scripts
 */
(function($) {
	'use strict';

	$(function() {
		// Instagram profile import
		$('#bio_link_import_btn').on('click', function(e) {
			e.preventDefault();
			var username = $('#bio_link_ig_username').val().trim();
			if (!username) {
				alert('Please enter an Instagram username first.');
				return;
			}

			var $btn = $(this);
			var $status = $('#bio_link_import_status');
			var $preview = $('#bio_link_import_preview');
			
			$btn.prop('disabled', true);
			$status.text('Fetching photos from @' + username + '...');
			$preview.html('');

			$.post(bioLinkAdmin.ajaxUrl, {
				action: 'bio_link_fetch_profile',
				nonce: bioLinkAdmin.nonce,
				username: username
			}, function(resp) {
				$btn.prop('disabled', false);
				if (resp.success) {
					$status.html('✓ ' + resp.data.message);
					// Show preview thumbnails
					var html = '<div style="display:flex;flex-wrap:wrap;gap:8px;">';
					$.each(resp.data.photos, function(i, photo) {
						html += '<div style="text-align:center;width:80px;">' +
							'<img src="' + photo.thumbnail + '" style="width:80px;height:80px;object-fit:cover;border-radius:4px;" />' +
							'<br><small>' + (photo.caption ? photo.caption.substring(0, 15) + '...' : photo.shortcode) + '</small>' +
							'</div>';
					});
					html += '</div>';
					$preview.html(html);
					// Reload page after 2 seconds to show updated list
					setTimeout(function() { location.reload(); }, 2000);
				} else {
					$status.text('✗ ' + (resp.data && resp.data.message ? resp.data.message : 'Failed'));
				}
			}).fail(function() {
				$btn.prop('disabled', false);
				$status.text('✗ Request failed');
			});
		});

		// Regenerate API key
		$('#bio_link_regenerate_key').on('click', function(e) {
			e.preventDefault();
			if (!confirm('Are you sure? You will need to update the key on your middle server.')) {
				return;
			}
			var $btn = $(this);
			$btn.prop('disabled', true);
			$.post(bioLinkAdmin.ajaxUrl, {
				action: 'bio_link_regenerate_api_key',
				nonce: bioLinkAdmin.nonce
			}, function(resp) {
				$btn.prop('disabled', false);
				if (resp.success) {
					$btn.closest('td').find('input').val(resp.data.key);
					alert('New API key generated. Copy it to your middle server.');
				}
			});
		});
	});

})(jQuery);
