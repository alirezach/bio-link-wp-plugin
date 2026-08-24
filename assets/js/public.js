/**
 * Bio Link - Public Scripts
 */
(function($) {
	'use strict';

	$(document).on('click', '.bio-link-item', function(e) {
		var hasLink = $(this).data('has-link');
		if (!hasLink) {
			e.preventDefault();
			return false;
		}
	});

})(jQuery);
