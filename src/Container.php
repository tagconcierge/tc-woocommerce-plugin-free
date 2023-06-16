<?php

namespace GtmEcommerceWoo\Lib;

use GtmEcommerceWoo\Lib\EventStrategy;
use GtmEcommerceWoo\Lib\Service\EventStrategiesService;
use GtmEcommerceWoo\Lib\Service\GtmSnippetService;
use GtmEcommerceWoo\Lib\Service\SettingsService;
use GtmEcommerceWoo\Lib\Service\PluginService;
use GtmEcommerceWoo\Lib\Service\EventInspectorService;
use GtmEcommerceWoo\Lib\Util\WpSettingsUtil;
use GtmEcommerceWoo\Lib\Util\WcOutputUtil;
use GtmEcommerceWoo\Lib\Util\WcTransformerUtil;

class Container {

	/** @var EventStrategiesService */
	public $eventStrategiesService;

	/** @var GtmSnippetService */
	public $gtmSnippetService;

	/** @var SettingsService */
	public $settingsService;

	/** @var PluginService */
	public $pluginService;

	/** @var EventInspectorService */
	public $eventInspectorService;

	/** @var WcTransformerUtil */
	protected $wcTransformerUtil;

	public function __construct( string $pluginVersion ) {
		$snakeCaseNamespace = 'gtm_ecommerce_woo';
		$spineCaseNamespace = 'gtm-ecommerce-woo';
		$proEvents = [
			'view_item_list',
			'view_item',
			'select_item',
			'remove_from_cart',
			'view_cart',
			'begin_checkout',
			'add_billing_info',
			'add_payment_info',
			'add_shipping_info',
			'abandon_cart',
			'abandon_checkout',
		];
		$serverEvents = [
			// 'add_to_cart',
			// 'remove_from_cart',
			// 'begin_checkout',
			'purchase',
			// 'refund',
		];
		$tagConciergeApiUrl = getenv('TAG_CONCIERGE_API_URL') ? getenv('TAG_CONCIERGE_API_URL') : 'https://api.tagconcierge.com';

		$wpSettingsUtil = new WpSettingsUtil($snakeCaseNamespace, $spineCaseNamespace);
		$this->wcTransformerUtil = new WcTransformerUtil();
		$wcOutputUtil = new WcOutputUtil($pluginVersion);

		$eventStrategies = [
			new EventStrategy\AddToCartStrategy($this->wcTransformerUtil, $wcOutputUtil),
			new EventStrategy\PurchaseStrategy($this->wcTransformerUtil, $wcOutputUtil)
		];

		$events = array_map(static function( $eventStrategy) {
			return $eventStrategy->getEventName();
		}, $eventStrategies);

		$this->eventStrategiesService = new EventStrategiesService($wpSettingsUtil, $wcOutputUtil, $eventStrategies);
		$this->gtmSnippetService = new GtmSnippetService($wpSettingsUtil);
		$this->settingsService = new SettingsService($wpSettingsUtil, $events, $proEvents, $serverEvents, $tagConciergeApiUrl, $pluginVersion);
		$this->pluginService = new PluginService($spineCaseNamespace, $wpSettingsUtil, $wcOutputUtil, $pluginVersion);
		$this->eventInspectorService = new EventInspectorService($wpSettingsUtil, $wcOutputUtil);
	}

	public function getSettingsService(): SettingsService {
		return $this->settingsService;
	}

	public function getGtmSnippetService(): GtmSnippetService {
		return $this->gtmSnippetService;
	}

	public function getEventStrategiesService(): EventStrategiesService {
		return $this->eventStrategiesService;
	}

	public function getPluginService(): PluginService {
		return $this->pluginService;
	}

	public function getEventInspectorService(): EventInspectorService {
		return $this->eventInspectorService;
	}

	public function getWcTransformerUtil() {
		return $this->wcTransformerUtil;
	}
}
