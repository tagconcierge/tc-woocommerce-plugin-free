<?php

namespace GtmEcommerceWoo\Lib\Service;

class DebuggerService {

	protected $wcOutputUtil;
	protected $wpSettingsUtil;
	protected $snakeCaseNamespace;
	protected $spineCaseNamespace;
	protected $wcTransformerUtil;

	public function __construct($snakeCaseNamespace, $spineCaseNamespace, $wcTransformerUtil, $wpSettingsUtil, $wcOutputUtil) {
		$this->snakeCaseNamespace = $snakeCaseNamespace;
		$this->spineCaseNamespace = $spineCaseNamespace;
		$this->wcTransformerUtil = $wcTransformerUtil;
		$this->wpSettingsUtil = $wpSettingsUtil;
		$this->wcOutputUtil = $wcOutputUtil;
	}

	public function initialize() {
		$cronName = $this->snakeCaseNamespace.'_cron_debugger';
		if ($this->wpSettingsUtil->getOption("debugger_enabled") !== '1') {
			$timestamp = wp_next_scheduled( $cronName );
			wp_unschedule_event( $timestamp, $cronName );
			return;
		}
		add_action( 'wp_head', [$this, 'wpHead'] );
		add_action( $cronName, [$this, 'cron'] );
		if ( ! wp_next_scheduled( $cronName ) ) {
			wp_schedule_event( time(), 'hourly', $cronName );
		}
	}

	function deactivationHook() {
		$cronName = $this->snakeCaseNamespace.'_cron_debugger';
		$timestamp = wp_next_scheduled( $cronName );
		wp_unschedule_event( $timestamp, $cronName );
	}

	public function cron() {
		$lastRun = get_transient( $this->snakeCaseNamespace . '_debugger_last_run' );
		if (true || $lastRun === false) {
			$lastRun = time() - HOUR_IN_SECONDS * 24;
		}
		set_transient( $this->snakeCaseNamespace . '_debugger_last_run', time() );
		$query = new \WC_Order_Query( array(
			'orderby' => 'date',
			'order' => 'DESC',
			'date_created' => '>' . $lastRun,
		) );
		$orders = $query->get_orders();
		$events = array_map(function($order) {
			$event = $this->wcTransformerUtil->getPurchaseFromOrderId($order);
			$event->paymentMethod = $order->get_payment_method();
			return $this->serializeEvent($event);
		}, $orders);
		$uuid = $this->wpSettingsUtil->getOption('uuid');

		$args = [
			'body' => json_encode([
				'uuid_hash' => $this->hash($uuid),
				'events' => $events
			]),
			'headers' => [
				'content-type' => 'application/json'
			],
			'data_format' => 'body',
		];
		var_dump($args['body']);
		// $response = wp_remote_post( 'https://api.gtmconcierge.com/v2/track-server', $args );
	}

	public function hash($value) {
		return md5($value);
	}

	public function present($value) {
	  switch (gettype($value)) {
		case "string":
		  if ($value !== "") {
			return true;
		  }
		  break;
		case "NULL":
		  return false;
	  }
	  return true;
	}

	public function serializeItem($item) {
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
			$categoryParam = "item_category";
			if ($index > 0) {
				$categoryParam .= "_" . ($index + 1);
			}
			$jsonItem[$categoryParam.'_present'] = $this->present($category);
		}

		return array_filter($jsonItem, function($value) { return !is_null($value) && $value !== ''; });
	}

	public function serializeEvent($event) {
		if ($event->name === "purchase") {
			$jsonEvent = [
				'event' => 'purchase',
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
				'ecommerce' => [
					'items' => array_map([$this, 'serializeItem'], $event->items)
				]
			];
		}

		return array_filter($jsonEvent, function($value) { return !is_null($value) && $value !== ''; });
	}

	public function wpHead() {
		$uuid = $this->wpSettingsUtil->getOption('uuid');
		$this->wcOutputUtil->dataLayerPush([
			'uuid_hash' => md5($uuid)
		]);
	}

}
