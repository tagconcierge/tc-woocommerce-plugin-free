<?php

namespace GtmEcommerceWoo\Lib\Service;

use GtmEcommerceWoo\Lib\EventStrategy\PurchaseStrategy;
use GtmEcommerceWoo\Lib\Util\OrderWrapper;
use GtmEcommerceWoo\Lib\Util\WcOutputUtil;
use GtmEcommerceWoo\Lib\Util\WooCommerceFeaturesUtil;
use GtmEcommerceWoo\Lib\Util\WpSettingsUtil;
use GtmEcommerceWoo\Lib\ValueObject\OrderMonitorStatistics;
use WC_Meta_Data;
use WC_Order;
use WP_REST_Request;

class OrderMonitorService {

	const ORDER_META_KEY_ORDER_MONITOR_CHECK = 'gtm_ecommerce_woo_order_monitor_check';

	const ORDER_META_KEY_ORDER_MONITOR_GTM = 'gtm_ecommerce_woo_order_monitor_gtm';

	const ORDER_META_KEY_ORDER_MONITOR_ANALYTICS_STORAGE = 'gtm_ecommerce_woo_order_monitor_analytics_storage';

	const ORDER_META_KEY_ORDER_MONITOR_AD_STORAGE = 'gtm_ecommerce_woo_order_monitor_ad_storage';

	const ORDER_META_KEY_ORDER_MONITOR_THANK_YOU_PAGE_VISITED = 'gtm_ecommerce_woo_order_monitor_thank_you_page_visited';

	const ORDER_META_KEY_ORDER_MONITOR_ADBLOCK = 'gtm_ecommerce_woo_order_monitor_adblock';

	const ORDER_META_KEY_ORDER_MONITOR_ITP = 'gtm_ecommerce_woo_order_monitor_itp';

	const ORDER_META_KEY_PURCHASE_EVENT_TRACKED = PurchaseStrategy::ORDER_META_KEY_PURCHASE_EVENT_TRACKED;

	const ORDER_META_KEY_PURCHASE_EVENT_TRACKED_ON_ORDER_FORM = 'gtm_ecommerce_woo_purchase_event_tracked_on_order_form';

	const ORDER_META_KEY_PURCHASE_SERVER_EVENT_TRACKED = 'gtm_ecommerce_woo_purchase_server_event_tracked';

	const ORDER_LIST_COLUMN_NAME_TRACKING_STATUS = 'gtm_ecommerce_woo_tracking_status';

	const TRANSIENT_KEY_PATTERN_ORDER_MONITOR = 'gtm_ecommerce_woo_order_monitor_%s';

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

		if (WooCommerceFeaturesUtil::isHposEnabled()) {
			add_filter('woocommerce_shop_order_list_table_columns', [$this, 'addTrackingStatusColumn']);
			add_action('woocommerce_shop_order_list_table_custom_column', [$this, 'handleTrackingStatusColumnValue'], 20, 2);
		} else {
			add_filter('manage_edit-shop_order_columns', [$this, 'addTrackingStatusColumn']);
			add_action('manage_shop_order_posts_custom_column', [$this, 'handleTrackingStatusColumnValue'], 20, 2);
		}

		add_action('add_meta_boxes', [$this, 'addOrderMetaBox']);
		add_filter('is_protected_meta', [$this, 'hideMetaData'], 10, 2);
	}

	public function addTrackingStatusColumn( $columns) {
		$newColumns = [];
		foreach ($columns as $key => $column) {
			$newColumns[$key] = $column;
			if ('order_status' === $key) {
				$newColumns[self::ORDER_LIST_COLUMN_NAME_TRACKING_STATUS] = 'Conversion Tracking';
			}
		}
		return $newColumns;
	}

	public function handleTrackingStatusColumnValue( $columnId, $order) {
		if (false === is_object($order) && function_exists('wc_get_order')) {
			$order = wc_get_order($order);
		}

		if (null === $order) {
			return;
		}

		if (self::ORDER_LIST_COLUMN_NAME_TRACKING_STATUS === $columnId) {
			$orderWrapper = new OrderWrapper($order);

			if (false === $orderWrapper->isMonitoringAvailable()) {
				echo 'n/a';
				return;
			}

			if (true === $orderWrapper->isTrackedSuccessfully()) {
				echo '<span class="dashicons dashicons-yes-alt tips" style="color: green;" data-tip="Event was correctly tracked by Google Tag Manager and not issues were detected"></span>';
				return;
			}

			if (true === $orderWrapper->isTrackedWithWarnings()) {
				echo '<span class="dashicons dashicons-warning tips" style="color: orange;" data-tip="Event was correctly tracked by Google Tag Manager but we detected: adblock was detected, analytical consent was denied or advertising consent was denied"></span>';
				return;
			}

			if (true === $orderWrapper->isNotTracked()) {
				echo '<span class="dashicons dashicons-dismiss tips" style="color: red;" data-tip="Event wasn\'t correctly tracked by Google Tag Manager. Depending on tracking implementation it can be caused by user not returning to the order confirmation page"></span>';
			}
		}
	}

	public function endpointDiagnostics( WP_REST_Request $data ) {
		$expectedKeys = [
			'c' => null,
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

		$customerHash = $requestData['c'];

		if (true === empty($customerHash)) {
			return;
		}

		unset($requestData['c']);

		$this->setTransient($customerHash, $requestData);
	}

	public function handleDiagnosticsSave( WC_Order $order) {
		$data = $this->getTransient($this->getCustomerHash());

		if (false === is_array($data)) {
			return;
		}

		foreach ($data as $key => $value) {
			$order->update_meta_data(sprintf('%s_order_monitor_%s', $this->wpSettingsUtil->getSnakeCaseNamespace(), $key), $value);
		}

		$order->update_meta_data(self::ORDER_META_KEY_ORDER_MONITOR_THANK_YOU_PAGE_VISITED, -1);
		$order->update_meta_data(self::ORDER_META_KEY_ORDER_MONITOR_CHECK, time());
		$order->save();

		$this->removeTransient($this->getCustomerHash());
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
		$customerIdHash = $this->getCustomerHash();

		$this->wcOutputUtil->script(<<<EOD
(function($, window, dataLayer){
	const c = '{$customerIdHash}';
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
		let consentsDefault = {};
		let consentsUpdate = {};

		dataLayer.forEach(event => {
			if ('object' === typeof event && event[0] === 'consent') {
				switch (event[1] ?? null) {
					case 'default':
						consentsDefault = {
							...consentsDefault,
							...event[2]
						};
						break;
					case 'update':
						consentsUpdate = {
							...consentsUpdate,
							...event[2]
						};
						break;
				}
			}
		});

		consents = {
			...consents,
			...consentsDefault,
			...consentsUpdate,
		}

		$.ajax({
			type: 'POST',
			async: false,
			url: '{$trackOrderEndpointUrlPattern}',
			data: {
				c,
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

	public function getStatistics( int $timeLimitInSeconds = 7*24*60*60) {

		if (!function_exists('wc_get_orders')) {
			return null;
		}

		$orders = wc_get_orders([
			'limit' => -1,
			'date_created' => '>' . ( time() - $timeLimitInSeconds ),
			'meta_key' => self::ORDER_META_KEY_ORDER_MONITOR_CHECK,
			'meta_value' => '',
			'meta_compare' => '!='
		]);


		$data = array_map(function ( WC_Order $order) {
			if (null === $order->get_date_paid()) {
				return null;
			}

			$baseAcc = array_map(function ( $item) {
				return null;
			}, array_flip(OrderMonitorStatistics::IMPORTANT_KEYS));

			$baseAcc['value'] = (float) $order->get_total();
			$baseAcc['id'] = $order->get_id();

			return array_reduce($order->get_meta_data(), function ( $acc, WC_Meta_Data $item) {
				$data = $item->get_data();
				if (in_array($data['key'], OrderMonitorStatistics::IMPORTANT_KEYS)) {
					$acc[$data['key']] = $data['value'];
				}

				return $acc;
			}, $baseAcc);
		}, $orders);

		$data = array_filter($data, function ( $item) {
			return null !== $item;
		});

		return new OrderMonitorStatistics($data);
	}

	private function setTransient( $customerHash, $data) {
		set_transient(sprintf(self::TRANSIENT_KEY_PATTERN_ORDER_MONITOR, $customerHash), $data, 3600);
	}

	private function getTransient( $customerHash) {
		return get_transient(sprintf(self::TRANSIENT_KEY_PATTERN_ORDER_MONITOR, $customerHash));
	}

	private function getCustomerHash() {
		return hash('sha512', WC()->session->get_customer_id());
	}

	private function removeTransient( $customerHash) {
		delete_transient(sprintf(self::TRANSIENT_KEY_PATTERN_ORDER_MONITOR, $customerHash));
	}

	public function addOrderMetaBox() {

		add_meta_box(
			$this->wpSettingsUtil->getSnakeCaseNamespace() . '_order_monitor_meta_box',
			'Conversion Tracking',
			[$this, 'renderOrderMetaBox'],
			'woocommerce_page_wc-orders', //'shop_order',
			'side',
			'high'
		);
	}

	public function renderOrderMetaBox( $order) {
		$orderWrapper = new OrderWrapper($order);

		$format = fn( $val) => is_bool($val) ? ( $val === true ? 'Yes' : 'No' ) : ucfirst($val);

		$status = $orderWrapper->isTrackedSuccessfully()
			? 'Success'
			: ( $orderWrapper->isTrackedWithWarnings()
				? 'Warning'
				: 'Error'
			);

		if ($orderWrapper->isMonitoringAvailable()) :
			?>
			<h4 style="margin-bottom: .1em;">Conversion tracking status<?php echo wc_help_tip("Success means the conversion was tracked correctly, warning means there were issue detected that could impacted data and error means the event wasn't tracked at all."); ?></h4>
			<span><?php echo $status; ?></span>


			<h4>Details</h4>

			<h5 style="margin-bottom: .1em;">Event generated<?php echo wc_help_tip('The purchase events was correctly generated and available for Google Tag Manager'); ?></h5>
			<span><?php echo $format($orderWrapper->purchaseTracked()); ?></span>

			<h5 style="margin-bottom: .1em;">GTM loaded<?php echo wc_help_tip('Google Tag Manager was loaded correctly for that event'); ?></h5>
			<span><?php echo $format($orderWrapper->gtmEnabled()); ?></span>

			<h5 style="margin-bottom: .1em;">AdBlock detected<?php echo wc_help_tip('AdBlock plugin could prevented this conversion from being fully tracked.'); ?></h5>
			<span><?php echo $format($orderWrapper->adblockEnabled()); ?></span>

			<h5 style="margin-bottom: .1em;">ITP detected<?php echo wc_help_tip("Apple's Intelligent Tracking Prevention could impact attribution data for this conversion."); ?></h5>
			<span><?php echo $format($orderWrapper->itpEnabled()); ?></span>

			<h5 style="margin-bottom: .1em;">Consent for analytics<?php echo wc_help_tip('Analytical consent is required for events to show up in GA4 reporting.'); ?></h5>
			<span><?php echo $format($orderWrapper->analyticsConsentGranted()); ?></span>

			<h5 style="margin-bottom: .1em;">Consent for ads<?php echo wc_help_tip('Ad consent is required to conversions being reported to advertisting platforms.'); ?></h5>
			<span><?php echo $format($orderWrapper->adConsentGranted()); ?></span>

			<p><a href="https://docs.tagpilot.io/article/76-conversion-tracking-monitoring" target="_blank">Documentation</a></p>
		<?php
		else :
			?>
		Conversion tracking monitoring was disabled at the time of this purchase. <br/>
		Enable it <a href="<?php echo admin_url('options-general.php?page=' . $this->wpSettingsUtil->getSpineCaseNamespace()); ?>">here</a>.
		<?php
		endif;
	}

	public function hideMetaData( $protected, $meta_key) {
		return in_array($meta_key, [
			self::ORDER_META_KEY_ORDER_MONITOR_CHECK,
			self::ORDER_META_KEY_ORDER_MONITOR_ADBLOCK,
			self::ORDER_META_KEY_ORDER_MONITOR_ITP,
			self::ORDER_META_KEY_ORDER_MONITOR_ANALYTICS_STORAGE,
			self::ORDER_META_KEY_ORDER_MONITOR_AD_STORAGE,
			self::ORDER_META_KEY_ORDER_MONITOR_THANK_YOU_PAGE_VISITED,
			self::ORDER_META_KEY_ORDER_MONITOR_GTM,
			self::ORDER_META_KEY_PURCHASE_EVENT_TRACKED_ON_ORDER_FORM,
			self::ORDER_META_KEY_PURCHASE_SERVER_EVENT_TRACKED,
			self::ORDER_META_KEY_PURCHASE_EVENT_TRACKED
		]) ?: $protected;
	}
}
