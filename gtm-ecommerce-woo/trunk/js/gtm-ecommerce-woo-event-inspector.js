(function($) {
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

            return template.replace('{{event}}', eventName);
        });
        $("#gtm-ecommerce-woo-event-inspector-list").html(rendered);
    }

    $(function() {
        $("#gtm-ecommerce-woo-event-inspector-list").on("click", "li", function(ev) {
            var index = $(ev.target).index();
            var existingStoredEvents = JSON.parse(sessionStorage.getItem("gtmDatalayerDebugger")) || [];
            // since items are stored in chronological order, but we render them in reverse order:
            alert(JSON.stringify(existingStoredEvents.reverse()[index], null, 2));
        });

        var existingStoredEvents = JSON.parse(sessionStorage.getItem("gtmDatalayerDebugger")) || [];
        renderItems(existingStoredEvents);

        setInterval(checkDataLayer, 100);
    });

    // dataLayer.push = function() {
    //     originalPush.call(arguments);
    //     var event = arguments[0];
    //     if (!event.event || event.event.substring(0, 4) === "gtm.") {
    //         return
    //     }
    //     var existingStoredEvents = JSON.parse(sessionStorage.getItem("gtmDatalayerDebugger")) || [];
    //     existingStoredEvents.push(event);
    //     sessionStorage.setItem("gtmDatalayerDebugger", JSON.stringify(existingStoredEvents));
    //     $(function() {
    //         renderItems(existingStoredEvents);
    //     });
    // }


})(jQuery);
