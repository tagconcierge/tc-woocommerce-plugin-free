(function($) {

	function getPresets() {
		return $.ajax({
			url: 'https://api.tagconcierge.com/v2/presets?filter=' + params.filter + '&uuid=' + params.uuid
		});
		// return $.ajax({
		// 	url: ajaxurl,
		// 	data: {
		// 		action: 'gtm_ecommerce_woo_get_presets',
		// 	}
		// }).then(function(res) {
		// 	return res;
		// });
	}

	jQuery(function($) {
		var presetTemplateHtml = $("#gtm-ecommerce-woo-preset-tmpl").html();
		var $presetsGrid = $("#gtm-ecommerce-woo-presets-grid");
		getPresets()
			.then(function (res) {
				$("#gtm-ecommerce-woo-presets-loader").css("display", "none");
				var locked = false;
				res.map(function (preset) {
					var $preset = $(presetTemplateHtml);
					$(".name", $preset).text(preset.name);
					$(".description", $preset).text(preset.description);
					if (preset.locked !== true) {
						$(".download", $preset).attr("data-id", preset.id);
					} else {
						locked = true;
						$(".download", $preset).attr("disabled", "disabled");
						$(".download", $preset).attr("title", "Requires PRO version, upgrade below.");
					}
					$(".events-count", $preset).text((preset.events || []).length);
					$(".events-list", $preset).pointer({ content: "<p>- " + (preset.events || []).join("<br />- ") + "</p>" });

					$(".version", $preset).text(preset.version || "N/A");

					if (preset.documentation) {
						$(".documentation", $preset).attr("href", preset.documentation);
						$(".documentation", $preset).show();
					}

					// $(".changelog", $preset).pointer({ content: "<p>- " + (preset.changelog || []).join("<br />- ") + "</p>" });
					if (true === preset.latest) {
						$preset.css({'border-color': '#06932d', 'border-width': '3px'});
						$('.name', $preset).append('<span style="float: right;color: #06932d;">recently added <span class="dashicons dashicons-admin-post"></span></span>');
					}
					$presetsGrid.append($preset);
				});
				// if something is locked then we show the button
				if (locked === true) {
					$("#gtm-ecommerce-woo-presets-upgrade").css("display", "block");
				}
			})
			.then(function() {
				$(".events-list", $presetsGrid).click(function(ev) {
					$(ev.currentTarget).pointer("open");
				});
				$(".download", $presetsGrid).click(function(ev) {
					ev.preventDefault();
					var preset = $(ev.currentTarget).attr("data-id");
					window.location = ajaxurl + '?action=gtm_ecommerce_woo_post_preset&preset=' + encodeURIComponent(preset);
				});
			})
	});

	jQuery(function($) {
		$("[data-id].download").click(function(ev) {
			ev.preventDefault();
			var preset = $(ev.currentTarget).attr("data-id");
			window.location = ajaxurl + '?action=gtm_ecommerce_woo_post_preset&preset=' + encodeURIComponent(preset);
		});
	});

	jQuery(function($) {
		$("#gtm-ecommerce-woo-theme-validator").click(function(ev) {
			ev.preventDefault();
			$("#gtm-ecommerce-woo-theme-validator").attr("disabled", "disabled");
			var email = $("#gtm-ecommerce-woo-theme-validator-email").val();
			return $.ajax({
				url: ajaxurl,
				data: {
					email: email,
					action: 'gtm_ecommerce_woo_post_validate_theme',
				}
			}).then(function(res) {
				$("#gtm-ecommerce-woo-validator-section").css("display", "none");
				$("#gtm-ecommerce-woo-validator-sent").css("display", "block")
				console.log(res);
				return res;
			});
		});
	});

})(jQuery);
