<?php

namespace GtmEcommerceWoo\Lib;

use GtmEcommerceWoo\Lib\EventStrategy;
use GtmEcommerceWoo\Lib\Service\EventStrategiesService;
use GtmEcommerceWoo\Lib\Service\GtmSnippetService;
use GtmEcommerceWoo\Lib\Service\SettingsService;
use GtmEcommerceWoo\Lib\Service\PluginService;

use GtmEcommerceWoo\Lib\Util\WpSettingsUtil;
use GtmEcommerceWoo\Lib\Util\WcOutputUtil;
use GtmEcommerceWoo\Lib\Util\WcTransformerUtil;


class Container {

    public function __construct() {
        $snakeCaseNamespace = "gtm_ecommerce_woo";
        $spineCaseNamespace = "gtm-ecommerce-woo";

        $wpSettingsUtil = new WpSettingsUtil($snakeCaseNamespace, $spineCaseNamespace);
        $wcTransformerUtil = new WcTransformerUtil();
        $wcOutputUtil = new WcOutputUtil();

        $eventStrategies = [
            new EventStrategy\AddToCartStrategy($wcTransformerUtil, $wcOutputUtil),
            new EventStrategy\PurchaseStrategy($wcTransformerUtil, $wcOutputUtil),
            new EventStrategy\UaCompatibilityStrategy($wcTransformerUtil, $wcOutputUtil),
        ];

        $this->eventStrategiesService = new EventStrategiesService($wpSettingsUtil, $eventStrategies);
        $this->gtmSnippetService = new GtmSnippetService($wpSettingsUtil);
        $this->settingsService = new SettingsService($wpSettingsUtil);
        $this->pluginService = new PluginService($spineCaseNamespace);

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
}