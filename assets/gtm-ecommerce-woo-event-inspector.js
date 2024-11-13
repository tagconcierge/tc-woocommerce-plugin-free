(function($, w) {
    dataLayer = window.dataLayer || [];

    var dataLayerIndex = 0;

    function checkDataLayer() {
        var currentDataLayerLength = dataLayer.length || 0;
        if (currentDataLayerLength > dataLayerIndex + 1) {
            var newDataLayer = dataLayer.slice(dataLayerIndex + 1);
            dataLayerIndex = currentDataLayerLength - 1;
            var existingStoredEvents = JSON.parse(sessionStorage.getItem("gtmDatalayerDebugger")) || [];

            newDataLayer.map(function(event) {
                if ((!event.event && !event.ecommerce) || (event.event && event.event.substring(0, 4) === "gtm.")) {
                    return
                }
                existingStoredEvents.push(event);
            });
            sessionStorage.setItem("gtmDatalayerDebugger", JSON.stringify(existingStoredEvents));
            renderItems(existingStoredEvents);
        }
    }

    function renderItems(items) {
        var template = $('#gtm-ecommerce-woo-event-inspector-list-template').html();
        // render items in reverse order
        var reverseItems = items.reverse();
        var rendered = reverseItems.map(function(item) {

            var eventName = item.event;

            if (!item.event && item.ecommerce && item.ecommerce.purchase) {
                eventName = "Purchase (UA)";
            }

            if (!item.event && item.ecommerce && item.ecommerce.impressions) {
                eventName = "Product Impression (UA)";
            }

            if (!item.event && item.ecommerce && item.ecommerce.detail) {
                eventName = "Product Detail (UA)";
            }

            if (item.event === "addToCart") {
                eventName = "addToCart (UA)";
            }

            if (item.event === "productClick") {
                eventName = "productClick (UA)";
            }

            if (item.event === "removeFromCart") {
                eventName = "removeFromCart (UA)";
            }

            if (item.event === "checkout") {
                eventName = "checkout (UA)";
            }

            return template
                .replace('{{event}}', eventName)
                .replace('{{json}}', hljs.highlight(JSON.stringify(item, null, 2), { language: 'json' }).value);
        });
        $("#gtm-ecommerce-woo-event-inspector-list").html(rendered);

    }

    $(function() {
        // Toggle tool size
        $('#gtm-ecommerce-woo-event-inspector').on('click', '.toggle-size', function() {
            $('#gtm-ecommerce-woo-event-inspector').toggleClass('minimized');
            sessionStorage.setItem('gtmDatalayerDebuggerMinimized', $('#gtm-ecommerce-woo-event-inspector').hasClass('minimized'));
        });

        if (sessionStorage.getItem('gtmDatalayerDebuggerMinimized') === 'true') {
            $('#gtm-ecommerce-woo-event-inspector').addClass('minimized');
        }

        $('#gtm-ecommerce-woo-event-inspector').on('click', '.clear-history', function() {
            sessionStorage.setItem("gtmDatalayerDebugger", '[]');
            renderItems([]);
        });

        $("#gtm-ecommerce-woo-event-inspector-list").on("click", "li span", function(ev) {
            $(ev.currentTarget).siblings('pre').toggle();
        });

        var existingStoredEvents = JSON.parse(sessionStorage.getItem("gtmDatalayerDebugger")) || [];
        renderItems(existingStoredEvents);

        setInterval(checkDataLayer, 100);

        $('#gtm-ecommerce-woo-event-inspector').show();
    });
})(jQuery, window);
