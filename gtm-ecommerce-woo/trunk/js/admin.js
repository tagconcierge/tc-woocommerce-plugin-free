(function($) {

	function getPresets() {
		return $.ajax({
			url: ajaxurl,
			data: {
				action: 'gtm_ecommerce_woo_get_presets',
			}
		}).then(function(res) {
			return res;
		});
	}

	jQuery(function($) {
		var presetTemplateHtml = $("#gtm-ecommerce-woo-preset-tmpl").html();
		var $presetsGrid = $("#gtm-ecommerce-woo-presets-grid");
		getPresets()
			.then(function (res) {
				$("#gtm-ecommerce-woo-presets-loader").css("display", "none");
				res.map(function (preset) {
					var $preset = $(presetTemplateHtml);
					$(".name", $preset).text(preset.name);
					$(".description", $preset).text(preset.description);
					if (preset.type !== "basic") {
						$(".download", $preset).attr("disabled", "disabled");
						$(".download", $preset).attr("title", "Requires PRO version, upgrade below.");
					} else {
						$(".download", $preset).attr("data-id", preset.id);
					}
					$(".events-count", $preset).text((preset.events || []).length);
					$(".events-list", $preset).pointer({ content: "<p>- " + (preset.events || []).join("<br />- ") + "</p>" });
					$presetsGrid.append($preset);
				});
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
		$("#gtm-ecommerce-woo-validate-theme").click(function(ev) {
			ev.preventDefault();
			return $.ajax({
				url: ajaxurl,
				data: {
					action: 'gtm_ecommerce_woo_post_validate_theme',
				}
			}).then(function(res) {
				console.log(res);
				return res;
			});
		});
	});

})(jQuery);
