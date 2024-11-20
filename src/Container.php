<?php

namespace GtmEcommerceWoo\Lib;

use GtmEcommerceWoo\Lib\EventStrategy;
use GtmEcommerceWoo\Lib\Service\EventStrategiesService;
use GtmEcommerceWoo\Lib\Service\GtmSnippetService;
use GtmEcommerceWoo\Lib\Service\OrderMonitorService;
use GtmEcommerceWoo\Lib\Service\SettingsService;
use GtmEcommerceWoo\Lib\Service\PluginService;
use GtmEcommerceWoo\Lib\Service\EventInspectorService;
use GtmEcommerceWoo\Lib\Service\ProductFeedService;
use GtmEcommerceWoo\Lib\Util\WpSettingsUtil;
use GtmEcommerceWoo\Lib\Util\WcOutputUtil;
use GtmEcommerceWoo\Lib\Util\WcTransformerUtil;
use GtmEcommerceWoo\Lib\Service\OrderDiagnosticsService;

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

	public $productFeedService;

	/** @var WcTransformerUtil */
	protected $wcTransformerUtil;

	protected $orderMonitorService;

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
			'add_to_wishlist',
			'remove_from_wishlist',
			'abandon_cart',
			'abandon_checkout',
			'language',
			'change_language',
			'change_currency'
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
		$this->orderMonitorService = new OrderMonitorService($wpSettingsUtil, $wcOutputUtil);
		$this->settingsService = new SettingsService($wpSettingsUtil, $this->orderMonitorService, $events, $proEvents, $serverEvents, $tagConciergeApiUrl, $pluginVersion);
		$this->pluginService = new PluginService($spineCaseNamespace, $wpSettingsUtil, $wcOutputUtil, $pluginVersion);
		$this->eventInspectorService = new EventInspectorService($wpSettingsUtil, $wcOutputUtil);
		$this->productFeedService = new ProductFeedService($snakeCaseNamespace, $wpSettingsUtil);
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

	public function getProductFeedService(): ProductFeedService {
		return $this->productFeedService;
	}

	public function getWcTransformerUtil() {
		return $this->wcTransformerUtil;
	}

	public function getOrderMonitorService(): OrderMonitorService {
		return $this->orderMonitorService;
	}
}
