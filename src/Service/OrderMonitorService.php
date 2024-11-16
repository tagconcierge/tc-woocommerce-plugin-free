<?php

namespace GtmEcommerceWoo\Lib\Service;

use GtmEcommerceWoo\Lib\Util\WcOutputUtil;
use GtmEcommerceWoo\Lib\Util\WpSettingsUtil;
use WC_Order;
use WP_REST_Request;

class OrderMonitorService {

	const ORDER_META_KEY_ORDER_MONITOR_CHECK = 'gtm_ecommerce_woo_order_monitor_check';

	const ORDER_META_KEY_ORDER_MONITOR_THANK_YOU_PAGE_VISITED = 'gtm_ecommerce_woo_order_monitor_thank_you_page_visited';

	const SESSION_KEY_ORDER_MONITOR = 'gtm_ecommerce_woo_order_monitor';
	protected $wpSettingsUtil;
	protected $wcOutputUtil;
	public function __construct( WpSettingsUtil $wpSettingsUtil, WcOutputUtil $wcOutputUtil) {
		$this->wpSettingsUtil = $wpSettingsUtil;
		$this->wcOutputUtil = $wcOutputUtil;
	}

	public function initialize() {
		if ('1' === $this->wpSettingsUtil->getOption('monitor_disabled')) {
			return;
		}

		add_action(
			'rest_api_init',
			function () {
				register_rest_route(
					'gtm-ecommerce-woo/v1',
					'/diagnostics',
					[
						'methods'             => 'POST',
						'callback'            => [ $this, 'endpointDiagnostics' ],
						'permission_callback' => '__return_true',
					]
				);
			}
		);

		add_action(
			'wp_footer',
			[$this, 'handleDiagnosticsScript']
		);

		add_action(
			'woocommerce_checkout_order_created',
			[$this, 'handleDiagnosticsSave']
		);

		add_action(
			'woocommerce_store_api_checkout_update_order_meta',
			[$this, 'handleDiagnosticsSave']
		);

		add_action(
			'woocommerce_thankyou',
			[$this, 'handleThankYouPage']
		);
	}

	public function endpointDiagnostics( WP_REST_Request $data ) {
		$expectedKeys = [
			'gtm' => null,
			'adblock' => null,
			'itp' => null,
			'ad_storage' => null,
			'analytics_storage' => null,
		];

		$requestData = array_intersect_key($data->get_params(), $expectedKeys);

		$requestData = array_map(function ( $item) {
			return sanitize_key($item);
		}, $requestData);

		WC()->session->set(self::SESSION_KEY_ORDER_MONITOR, $requestData);
	}

	public function handleDiagnosticsSave( WC_Order $order) {
		$data = WC()->session->get(self::SESSION_KEY_ORDER_MONITOR);

		if (false === is_array($data)) {
			return;
		}

		foreach ($data as $key => $value) {
			$order->update_meta_data(sprintf('%s_order_monitor_%s', $this->wpSettingsUtil->getSnakeCaseNamespace(), $key), $value);
		}

		$order->update_meta_data(self::ORDER_META_KEY_ORDER_MONITOR_THANK_YOU_PAGE_VISITED, -1);
		$order->update_meta_data(self::ORDER_META_KEY_ORDER_MONITOR_CHECK, time());
		$order->save();

		WC()->session->set(self::SESSION_KEY_ORDER_MONITOR, null);
	}

	public function handleThankYouPage( $orderId) {
		$order = wc_get_order( $orderId );

		if (false === $order instanceof WC_Order) {
			return;
		}

		if (0 < (int) $order->get_meta(self::ORDER_META_KEY_ORDER_MONITOR_THANK_YOU_PAGE_VISITED)) {
			return;
		}

		$order->update_meta_data(self::ORDER_META_KEY_ORDER_MONITOR_THANK_YOU_PAGE_VISITED, time());
		$order->save();
	}

	public function handleDiagnosticsScript() {
		if (!is_checkout() || is_order_received_page()) {
			return;
		}

		$trackOrderEndpointUrlPattern = sprintf('%sgtm-ecommerce-woo/v1/diagnostics', get_rest_url());

		$this->wcOutputUtil->script(<<<EOD
(function($, window, dataLayer){
	const ad = document.createElement('ins');
	ad.className = 'AdSense';
	ad.style.display = 'block';
	ad.style.position = 'absolute';
	ad.style.top = '-1px';
	ad.style.height = '1px';
	document.body.appendChild(ad);

	setTimeout(function() {
		const gtm = undefined !== window.google_tag_manager;
		const itp = navigator.userAgent.includes('Safari') &&
			!navigator.userAgent.includes('Chrome') &&
			(navigator.userAgent.includes('iPhone') ||
			navigator.userAgent.includes('iPad') ||
			navigator.platform.includes('Mac'));
		const adblock = !document.querySelector('.AdSense').clientHeight;
		document.body.removeChild(ad);

		let consents = {
			ad_storage: 'denied',
			analytics_storage: 'denied',
		};

		dataLayer.forEach(event => {
			if ('object' === typeof event && event[0] === 'consent') {
				consents = {
					...consents,
					...event[2]
				};
			}
		});

		$.ajax({
			type: 'POST',
			async: false,
			url: '{$trackOrderEndpointUrlPattern}',
			data: {
				gtm,
				adblock,
				itp,
				...consents
			},
		});
	}, 1000);
})(jQuery, window, dataLayer);
EOD
		);
	}
}
