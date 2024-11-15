<?php

namespace GtmEcommerceWoo\Lib\Service;

use GtmEcommerceWoo\Lib\Util\WcOutputUtil;
use GtmEcommerceWoo\Lib\Util\WpSettingsUtil;
use WC_Order;
use WP_REST_Request;


class OrderDiagnosticsService {

	const ORDER_META_KEY_ORDER_DIAGNOSED = 'gtm_ecommerce_woo_order_diagnosed';
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
					'/diagnose-order/(?P<order_id>\d+)',
					[
						'methods'             => 'POST',
						'callback'            => [ $this, 'endpointDiagnoseOrder' ],
						'permission_callback' => '__return_true',
					]
				);
			}
		);

		add_action(
			'woocommerce_thankyou',
			[$this, 'thankYouPage']
		);
	}

	public function endpointDiagnoseOrder( WP_REST_Request $data ) {
		if (false === isset($data['order_id'])) {
			return;
		}

		$order = wc_get_order( (int) $data['order_id'] );

		if (false === $order instanceof WC_Order) {
			return;
		}

		if (false === $this->shouldBeProcessed($order)) {
			return;
		}

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

		foreach ($requestData as $key => $value) {
			$order->update_meta_data(sprintf('%s_%s', $this->wpSettingsUtil->getSnakeCaseNamespace(), $key), $value);
		}

		$order->update_meta_data(self::ORDER_META_KEY_ORDER_DIAGNOSED, time());

		$order->save();
	}

	public function thankYouPage( $orderId) {
		$order = wc_get_order( (int) $orderId );

		if (false === $order instanceof WC_Order) {
			return;
		}

		if (false === $this->shouldBeProcessed($order)) {
			return;
		}

		$trackOrderEndpointUrlPattern = sprintf('%sgtm-ecommerce-woo/v1/diagnose-order/%d', get_rest_url(), $orderId);

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

	private function shouldBeProcessed( WC_Order $order) {
		return true === empty($order->get_meta(self::ORDER_META_KEY_ORDER_DIAGNOSED));
	}
}
