(function($) {

    function getPresets() {
        return $.ajax({
            url: ajaxurl,
            data: {
                action: 'gtm_ecommerce_woo_get_presets',
            }
        }).then(function(res) {
            console.log(res);
            return res;
        });
    }

    jQuery(function($) {

        var $selectPreset = $("#gtm-ecommerce-woo-select-preset");
        getPresets()
            .then(function (res) {
                res.map(function (preset) {
                    var $option = $("<option>");
                    $option.attr("value", preset.id);
                    $option.text(preset.name);
                    $selectPreset.append($option);
                });
            });

        $("#gtm-ecommerce-woo-download-preset").click(function(ev) {
            ev.preventDefault();
            var preset = $selectPreset.val();
            window.location = ajaxurl + '?action=gtm_ecommerce_woo_post_preset&preset=' + encodeURIComponent(preset);
        });
    });

})(jQuery);
