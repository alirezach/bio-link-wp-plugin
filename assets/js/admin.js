/**
 * Bio Link - Admin Scripts
 */
(function($) {
	'use strict';

	$(function() {
		var i18n = bioLinkAdmin.i18n || {
			fetching: 'Fetching...',
			fetch_failed: 'Fetch failed. Check connection.',
			checking: 'Checking...',
			connected: 'Connected ✓',
			disconnected: 'Disconnected ✗'
		};

		// Dashboard: Import from Instagram
		$('#bio_link_import_btn').on('click', function(e) {
			e.preventDefault();
			var username = $('#bio_link_ig_username').val().trim();
			if (!username) {
				alert('Please enter an Instagram username first.');
				return;
			}

			var useMiddle = $('#bio_link_use_middle').is(':checked');
			var $btn = $(this);
			var $status = $('#bio_link_import_status');
			var $preview = $('#bio_link_import_preview');
			
			$btn.prop('disabled', true);
			$status.text(i18n.fetching);
			$preview.html('');

			$.post(bioLinkAdmin.ajaxUrl, {
				action: 'bio_link_fetch_profile',
				nonce: bioLinkAdmin.nonce,
				username: username,
				use_middle: useMiddle ? 1 : 0
			}, function(resp) {
				$btn.prop('disabled', false);
				if (resp.success) {
					$status.html('✓ ' + resp.data.message);
					// Show preview thumbnails
					var html = '<div style="display:flex;flex-wrap:wrap;gap:8px;margin-top:10px;">';
					$.each(resp.data.photos, function(i, photo) {
						html += '<div style="text-align:center;width:80px;">' +
							'<img src="' + photo.thumbnail + '" style="width:80px;height:80px;object-fit:cover;border-radius:4px;" />' +
							'<br><small>' + (photo.caption ? photo.caption.substring(0, 15) + '...' : photo.shortcode.substring(0, 15)) + '</small>' +
							'</div>';
					});
					html += '</div>';
					$preview.html(html);
					setTimeout(function() { location.reload(); }, 2000);
				} else {
					$status.text('✗ ' + (resp.data && resp.data.message ? resp.data.message : i18n.fetch_failed));
				}
			}).fail(function() {
				$btn.prop('disabled', false);
				$status.text('✗ ' + i18n.fetch_failed);
			});
		});

		// Photo meta: fetch single image
		$('#bio_link_fetch_single').on('click', function(e) {
			e.preventDefault();
			var url = $('#bio_link_instagram_url').val();
			if (!url) {
				alert('Please enter an Instagram URL first.');
				return;
			}

			var postId = $('#post').val() || $('input[name="post_ID"]').val();
			var useMiddle = $('#bio_link_use_middle').is(':checked');
			var $btn = $(this);
			var $status = $('#bio_link_single_status');

			$btn.prop('disabled', true);
			$status.text(i18n.fetching);

			$.post(bioLinkAdmin.ajaxUrl, {
				action: 'bio_link_fetch_single',
				nonce: bioLinkAdmin.nonce,
				url: url,
				post_id: postId,
				use_middle: useMiddle ? 1 : 0
			}, function(resp) {
				$btn.prop('disabled', false);
				if (resp.success) {
					$status.text('✓ ' + resp.data.message);
				} else {
					$status.text('✗ ' + (resp.data && resp.data.message ? resp.data.message : i18n.fetch_failed));
				}
			}).fail(function() {
				$btn.prop('disabled', false);
				$status.text('✗ ' + i18n.fetch_failed);
			});
		});

		// Settings: Check middle server
		$('#bio_link_check_server').on('click', function(e) {
			e.preventDefault();
			var url = $('#bio_link_server_url').val() || bioLinkAdmin.serverUrl || '';
			var $btn = $(this);
			var $status = $('#server_check');
			
			$btn.prop('disabled', true);
			$status.text(i18n.checking);

			$.post(bioLinkAdmin.ajaxUrl, {
				action: 'bio_link_check_server',
				nonce: bioLinkAdmin.nonce,
				url: url
			}, function(resp) {
				$btn.prop('disabled', false);
				if (resp.success) {
					$status.html('<span style="color:green;">' + i18n.connected + '</span>');
				} else {
					$status.html('<span style="color:red;">' + i18n.disconnected + ': ' + (resp.data && resp.data.message ? resp.data.message : '') + '</span>');
				}
			}).fail(function() {
				$btn.prop('disabled', false);
				$status.html('<span style="color:red;">' + i18n.disconnected + '</span>');
			});
		});

		// Settings: Check Instagram Graph API
		$('#bio_link_check_graph').on('click', function(e) {
			e.preventDefault();
			var token = $('#bio_link_ig_token').val();
			if (!token) {
				alert('Please enter a token first.');
				return;
			}
			var $btn = $(this);
			var $status = $('#graph_check');
			
			$btn.prop('disabled', true);
			$status.text(i18n.checking);

			$.post(bioLinkAdmin.ajaxUrl, {
				action: 'bio_link_check_graph_api',
				nonce: bioLinkAdmin.nonce,
				token: token
			}, function(resp) {
				$btn.prop('disabled', false);
				if (resp.success) {
					$status.html('<span style="color:green;">' + i18n.connected + '</span>');
				} else {
					$status.html('<span style="color:red;">' + i18n.disconnected + ': ' + (resp.data && resp.data.message ? resp.data.message : '') + '</span>');
				}
			}).fail(function() {
				$btn.prop('disabled', false);
				$status.html('<span style="color:red;">' + i18n.disconnected + '</span>');
			});
		});

		// Settings: Regenerate API key
		$('#bio_link_regenerate_key').on('click', function(e) {
			e.preventDefault();
			if (!confirm('Are you sure? You will need to update the key on your middle server.')) {
				return;
			}
			var $btn = $(this);
			$.post(bioLinkAdmin.ajaxUrl, {
				action: 'bio_link_regenerate_api_key',
				nonce: bioLinkAdmin.nonce
			}, function(resp) {
				if (resp.success) {
					$('#bio_link_api_key_display').val(resp.data.key);
					alert('New API key generated. Copy it to your middle server.');
					// Re-check connection
					$('#bio_link_check_server').click();
				}
			});
		});

		// Copy API key to clipboard
		$('#bio_link_copy_key').on('click', function(e) {
			e.preventDefault();
			var $input = $('#bio_link_api_key_display');
			$input[0].select();
			$input[0].setSelectionRange(0, $input[0].value.length);
			navigator.clipboard.writeText($input.val()).then(function() {
				alert('Copied to clipboard!');
			});
		});
	});

})(jQuery);
