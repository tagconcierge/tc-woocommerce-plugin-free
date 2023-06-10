(function($) {
	jQuery(function($) {
		var $notice = $('[data-gtm-ecommerce-woo-feedback]');
		var $questions = $('[data-section=questions]', $notice);
		$($questions).on('click', '[data-target]', function(ev) {
			$questions.hide();
			var sectionName = $(ev.currentTarget).data('target');
			var $section = $('[data-section=' + sectionName + ']', $notice);
			$section.show();
			return $.ajax({
				url: ajaxurl,
				data: {
					action: 'gtm_ecommerce_woo_dismiss_feedback',
				}
			});
		});

		$($notice).on('click', '.notice-dismiss', function(ev) {
			return $.ajax({
				url: ajaxurl,
				data: {
					action: 'gtm_ecommerce_woo_dismiss_feedback',
				}
			});
		});
	});

})(jQuery);
