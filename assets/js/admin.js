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

		// Dashboard: Import from Instagram — browser-side fetch
		// (The WP host often has no outbound internet; the admin's BROWSER
		// calls the middle server, then POSTs the result to WP REST.)
		$('#bio_link_import_btn').on('click', function(e) {
			e.preventDefault();
			var username = $('#bio_link_ig_username').val().trim().replace(/^@/, '');
			if (!username) {
				alert('Please enter an Instagram username first.');
				return;
			}

			var serverUrl = bioLinkAdmin.serverUrl || '';
			var $btn = $(this);
			var $status = $('#bio_link_import_status');
			var $preview = $('#bio_link_import_preview');

			$btn.prop('disabled', true);
			$status.text(i18n.fetching);
			$preview.html('');

			if (!serverUrl) {
				$btn.prop('disabled', false);
				$status.text('✗ ' + i18n.no_server);
				return;
			}

			var base = serverUrl.replace(/\/+$/, '');
			var fetchUrl = base + '/api/v1/fetch-profile?username=' +
				encodeURIComponent(username) + '&count=15&token=' +
				encodeURIComponent(bioLinkAdmin.igToken || '');

			fetch(fetchUrl, {
				headers: {
					'X-BioLink-Site': bioLinkAdmin.siteId,
					'X-BioLink-Key': bioLinkAdmin.apiKey
				}
			})
			.then(function(r) {
				if (!r.ok) throw new Error('Middle server HTTP ' + r.status);
				return r.json();
			})
			.then(function(data) {
				if (!data.ok || !data.photos || !data.photos.length) {
					throw new Error(data.error || 'no_photos_found');
				}
				var followers = data.followers || 0;

				// Show preview thumbnails
				var html = '<div style="display:flex;flex-wrap:wrap;gap:8px;margin-top:10px;">';
				$.each(data.photos, function(i, photo) {
					html += '<div style="text-align:center;width:80px;">' +
						'<img src="' + (photo.thumbnail || photo.image_url) + '" style="width:80px;height:80px;object-fit:cover;border-radius:4px;" />' +
						'<br><small>' + ((photo.caption || photo.shortcode).substring(0, 15)) + '</small></div>';
				});
				html += '</div>';
				$preview.html(html);

				// Import into WordPress via REST
				return fetch(bioLinkAdmin.restUrl + '/import', {
					method: 'POST',
					headers: {
						'Content-Type': 'application/json',
						'X-WP-Nonce': bioLinkAdmin.restNonce
					},
					body: JSON.stringify({
						username: username,
						followers: followers,
						photos: data.photos
					})
				});
			})
			.then(function(r) {
				if (!r.ok) throw new Error('Import HTTP ' + r.status);
				return r.json();
			})
			.then(function(importResp) {
				$btn.prop('disabled', false);
				if (importResp.ok) {
					var followersTxt = importResp.followers ? ' · 👥 ' + importResp.followers.toLocaleString() : '';
					$status.html('<span style="color:green;">✓</span> ' + importResp.message + followersTxt);
					setTimeout(function() { location.reload(); }, 2000);
				} else {
					$status.text('✗ ' + (importResp.message || i18n.fetch_failed));
				}
			})
			.catch(function(err) {
				$btn.prop('disabled', false);
				$status.text('✗ ' + err.message + ' — check middle server URL / CORS.');
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
