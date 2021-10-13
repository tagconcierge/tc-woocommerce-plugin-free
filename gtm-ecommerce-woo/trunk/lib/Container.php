<?php

namespace GtmEcommerceWoo\Lib;

use GtmEcommerceWoo\Lib\EventStrategy;
use GtmEcommerceWoo\Lib\Service\EventStrategiesService;
use GtmEcommerceWoo\Lib\Service\GtmSnippetService;
use GtmEcommerceWoo\Lib\Service\SettingsService;
use GtmEcommerceWoo\Lib\Service\PluginService;
use GtmEcommerceWoo\Lib\Service\MonitorService;
use GtmEcommerceWoo\Lib\Service\ThemeValidatorService;
use GtmEcommerceWoo\Lib\Service\EventInspectorService;

use GtmEcommerceWoo\Lib\Util\WpSettingsUtil;
use GtmEcommerceWoo\Lib\Util\WcOutputUtil;
use GtmEcommerceWoo\Lib\Util\WcTransformerUtil;

class Container {

    public function __construct() {
        $snakeCaseNamespace = "gtm_ecommerce_woo";
        $spineCaseNamespace = "gtm-ecommerce-woo";
        $proEvents = [
        	"view_item_list",
        	"view_item",
        	"select_item",
        	"remove_from_cart",
        	"begin_checkout",
        ];
        $tagConciergeApiUrl = getenv("TAG_CONCIERGE_API_URL") ? getenv("TAG_CONCIERGE_API_URL") : "https://api.tagconcierge.com";

        $wpSettingsUtil = new WpSettingsUtil($snakeCaseNamespace, $spineCaseNamespace);
        $wcTransformerUtil = new WcTransformerUtil();
        $wcOutputUtil = new WcOutputUtil();

        $eventStrategies = [
            new EventStrategy\AddToCartStrategy($wcTransformerUtil, $wcOutputUtil),
            new EventStrategy\PurchaseStrategy($wcTransformerUtil, $wcOutputUtil)
        ];

        $events = array_map(function($eventStrategy) {
        	return $eventStrategy->getEventName();
        }, $eventStrategies);

        $this->eventStrategiesService = new EventStrategiesService($wpSettingsUtil, $eventStrategies);
        $this->gtmSnippetService = new GtmSnippetService($wpSettingsUtil);
        $this->settingsService = new SettingsService($wpSettingsUtil, $events, $proEvents, $tagConciergeApiUrl);
        $this->pluginService = new PluginService($spineCaseNamespace);
        $this->monitorService = new MonitorService($snakeCaseNamespace, $spineCaseNamespace, $wcTransformerUtil, $wpSettingsUtil, $wcOutputUtil, $tagConciergeApiUrl);
        $this->themeValidatorService = new ThemeValidatorService($snakeCaseNamespace, $spineCaseNamespace, $wcTransformerUtil, $wpSettingsUtil, $wcOutputUtil, $events, $tagConciergeApiUrl);
        $this->eventInspectorService = new EventInspectorService($wpSettingsUtil);

    }

    public function getSettingsService() {
        return $this->settingsService;
    }

    public function getGtmSnippetService() {
        return $this->gtmSnippetService;
    }

    public function getEventStrategiesService() {
        return $this->eventStrategiesService;
    }

    public function getPluginService() {
        return $this->pluginService;
    }

    public function getMonitorService() {
        return $this->monitorService;
    }

    public function getThemeValidatorService() {
        return $this->themeValidatorService;
    }

    public function getEventInspectorService() {
        return $this->eventInspectorService;
    }
}
