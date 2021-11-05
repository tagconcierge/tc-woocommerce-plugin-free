<?php

namespace GtmEcommerceWoo\Lib\Service;

/**
 * Tag Concierge Monitoring tool. This is responsible for sending out backend events.
 */
class MonitorService {

	protected $wcOutputUtil;
	protected $wpSettingsUtil;
	protected $snakeCaseNamespace;
	protected $spineCaseNamespace;
	protected $wcTransformerUtil;
	protected $tagConciergeApiUrl;
	protected $tagConciergeEdgeUrl;

	public function __construct( $snakeCaseNamespace, $spineCaseNamespace, $wcTransformerUtil, $wpSettingsUtil, $wcOutputUtil, $tagConciergeApiUrl, $tagConciergeEdgeUrl) {
		$this->snakeCaseNamespace = $snakeCaseNamespace;
		$this->spineCaseNamespace = $spineCaseNamespace;
		$this->wcTransformerUtil = $wcTransformerUtil;
		$this->wpSettingsUtil = $wpSettingsUtil;
		$this->wcOutputUtil = $wcOutputUtil;
		$this->tagConciergeApiUrl = $tagConciergeApiUrl;
		$this->tagConciergeEdgeUrl = $tagConciergeEdgeUrl;
	}

	public function initialize() {
		$cronName = $this->snakeCaseNamespace . '_cron_monitor';
		if ($this->wpSettingsUtil->getOption('monitor_enabled') !== '1') {
			$timestamp = wp_next_scheduled( $cronName );
			wp_unschedule_event( $timestamp, $cronName );
			return;
		}

		add_action( $cronName, [$this, 'cronJob'] );
		if ( ! wp_next_scheduled( $cronName ) ) {
			wp_schedule_event( time(), 'hourly', $cronName );
		}

		// add_action( 'rest_api_init', function () {
		//   register_rest_route( 'gtm-ecommerce-woo/v1', '/track', array(
		//     'methods' => 'POST',
		//     'callback' => [$this, 'trackEvents'],
		//   ) );
		// } );

		add_action( 'wp_head', [$this, 'uuidHash'] );

		add_action( 'woocommerce_add_to_cart', [$this, 'addToCart'], 10, 6 );
		add_action( 'woocommerce_thankyou', [$this, 'purchase'] );

		add_action( 'woocommerce_order_status_changed', [$this, 'orderStatusChanged']);
	}

	public function deactivationHook() {
		$cronName = $this->snakeCaseNamespace . '_cron_debugger';
		$timestamp = wp_next_scheduled( $cronName );
		wp_unschedule_event( $timestamp, $cronName );
	}

	// function

	public function uuidHash() {
		$uuid = $this->wpSettingsUtil->getOption('uuid');
		echo '<script type="text/javascript">';
		echo 'window.dataLayer = window.dataLayer || [];';
		echo "(function(dataLayer) {\n";
		echo "dataLayer.push({ uuid_hash: '" . $this->hash($uuid) . "' });";
		echo "dataLayer.push({ monitor_url: '" . $this->tagConciergeEdgeUrl . "' });";
		echo '})(dataLayer);';
		echo "</script>\n";
	}


	// switch to save_post_shop_order hook
	public function cronJob() {
		$lastRun = get_transient( $this->snakeCaseNamespace . '_monitor_last_run' );
		if (false === $lastRun) {
			$lastRun = time() - HOUR_IN_SECONDS * 24;
		}

		set_transient( $this->snakeCaseNamespace . '_monitor_last_run', time() );
	}

	public function orderStatusChanged( $orderId) {
		$order = wc_get_order( $orderId );
		$items = $order->get_items();
		$parsedItems = array_map(function( $item) {
			return $this->wcTransformerUtil->getItemFromOrderItem($item);
		}, $items);
		$eventTracked = get_post_meta( $order->get_id(), 'gtm_ecommerce_woo_purchase_event_tracked', true );

		$confirmationPageFragments = parse_url($order->get_checkout_order_received_url());
		$transaction = [
			'transaction_id' => '***' . substr($order->get_id(), -1),
			'transaction_id_hash' => $this->hash($order->get_id()),
			'transaction_timestamp' => (string) $order->get_date_created(),
			'transaction_status' => $order->get_status(),
			'transaction_value' => $order->get_total() * 100,
			'transaction_value_refunded' => $order->get_total_refunded() * 100,
			'transaction_currency' => $order->get_currency(),
			'transaction_payment_method' => $order->get_payment_method(),
			'transaction_items' => $parsedItems,
			'transaction_purchase_event_tracked' => $eventTracked,
			'transaction_confirmation_page' => trim($confirmationPageFragments['path'] . '?' . $confirmationPageFragments['query'], '?')
		];
		$uuid = $this->wpSettingsUtil->getOption('uuid');
		$args = [
			'body' => json_encode([
				'uuid_hash' => $this->hash($uuid),
				'transactions' => [$transaction]
			]),
			'headers' => [
				'content-type' => 'application/json',
			],
			'data_format' => 'body',
		];

		try {
			$response = wp_remote_post( $this->tagConciergeEdgeUrl . '/v2/monitor/transactions', $args );
		} catch (Exception $err) {
			error_log( 'Tag Concierge Monitor add_to_cart failed' );
		}
	}

	public function addToCart( $cart_item_key, $product_id, $quantity, $variation_id, $variation, $cart_item_data) {
		$product = $variation_id ? wc_get_product( $variation_id ) : wc_get_product( $product_id );
		$item = $this->wcTransformerUtil->getItemFromProduct($product);
		$item->quantity = $quantity;
		$event = [
			'event_uuid' => $this->uuid(),
			'event_name' => 'add_to_cart',
			'event_timestamp' => ( new \Datetime('now') )->format('Y-m-d H:i:s'),
			'event_items' => [$item],
			'event_location' => parse_url($_SERVER['HTTP_REFERER'], PHP_URL_PATH)
		];
		$uuid = $this->wpSettingsUtil->getOption('uuid');
		$args = [
			'body' => json_encode([
				'uuid_hash' => $this->hash($uuid),
				'origin' => 'server',
				'events' => [$event]
			]),
			'headers' => [
				'content-type' => 'application/json'
			],
			'data_format' => 'body',
		];

		try {
			$response = wp_remote_post( $this->tagConciergeEdgeUrl . '/v2/monitor/events', $args );
		} catch (Exception $err) {
			error_log( 'Tag Concierge Monitor add_to_cart failed' );
		}
	}

	public function purchase( $orderId) {
		global $wp;
		$event = $this->wcTransformerUtil->getPurchaseFromOrderId($orderId);
		$finalEvent = [
			'event_uuid' => $this->uuid(),
			'event_name' => 'purchase',
			'event_timestamp' => ( new \Datetime('now') )->format('Y-m-d H:i:s'),
			'event_items' => $event->items,
			'event_location' => $wp->request,
			'event_data' => [
				'transaction_id_hash' => $this->hash($event->transationId),
				'affiliation' => $event->affiliation,
				'value' => $event->value,
				'tax' => $event->tax,
				'shipping' => $event->shipping,
				'currency' => $event->currency,
				'coupon' => @$event->coupon
			]
		];
		$uuid = $this->wpSettingsUtil->getOption('uuid');
		$args = [
			'body' => json_encode([
				'uuid_hash' => $this->hash($uuid),
				'origin' => 'server',
				'events' => [$finalEvent]
			]),
			'headers' => [
				'content-type' => 'application/json'
			],
			'data_format' => 'body',
		];
		try {
			$response = wp_remote_post( $this->tagConciergeEdgeUrl . '/v2/monitor/events', $args );
		} catch (Exception $err) {
			error_log( 'Tag Concierge Monitor purchase failed' );
		}
		$this->orderStatusChanged($orderId);
	}

	public function hash( $value) {
		return md5($value);
	}

	public function present( $value) {
		switch (gettype($value)) {
			case 'string':
				if ('' !== $value) {
					return true;
				}
				break;
			case 'NULL':
				return false;
		}
	  return true;
	}

	public function serializeItem( $item) {
		$jsonItem = [
			'item_name_present' => $this->present($item->itemName),
			'item_id_hash' => $this->hash($item->itemId),
			'price_present' => $this->present($item->price),
			'item_brand_present' => $this->present(@$item->itemBrand),
			'item_coupon_present' => $this->present(@$item->itemCoupon),
			'item_variant_present' => $this->present(@$item->itemVariant),
			'item_list_name_present' => $this->present(@$item->itemListName),
			'item_list_id_present' => $this->present(@$item->itemListId),
			'index_present' => $this->present(@$item->index),
			'quantity_present' => $this->present(@$item->quantity),
		];

		foreach ($item->itemCategories as $index => $category) {
			$categoryParam = 'item_category';
			if ($index > 0) {
				$categoryParam .= '_' . ( $index + 1 );
			}
			$jsonItem[$categoryParam . '_present'] = $this->present($category);
		}

		return array_filter($jsonItem, function( $value) {
			return !is_null($value) && '' !== $value;
		});
	}

	public function serializeEvent( $event) {
		if ('purchase' === $event->name) {
			$jsonEvent = [
				'event' => 'purchase',
				// 'event_timestamp' => $event->timestamp,
				'ecommerce_order' => $even->eCommerceOrder,
				'ecommerce' => [
					'purchase' => [
						'transaction_id_hash' => $this->hash($event->transationId),
						'affiliation_present' => $this->present($event->affiliation),
						'value_present' => $this->present($event->value),
						'tax_present' => $this->present($event->tax),
						'shipping_present' => $this->present($event->shipping),
						'currency_present' => $this->present($event->currency),
						'coupon_present' => $this->present(@$event->coupon),
						'payment_method' => @$event->paymentMethod,
					],
					'items' => array_map([$this, 'serializeItem'], $event->items)
				]
			];
		} else {
			$jsonEvent = [
				'event' => $event->name,
				// 'event_timestamp' => $event->timestamp,
				'ecommerce' => [
					'items' => array_map([$this, 'serializeItem'], $event->items)
				]
			];
		}

		return array_filter($jsonEvent, function( $value) {
			return !is_null($value) && '' !== $value;
		});
	}

	public function uuid() {
		return sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
			mt_rand(0, 0xffff), mt_rand(0, 0xffff),
			mt_rand(0, 0xffff),
			mt_rand(0, 0x0fff) | 0x4000,
			mt_rand(0, 0x3fff) | 0x8000,
			mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
		);
	}
}
