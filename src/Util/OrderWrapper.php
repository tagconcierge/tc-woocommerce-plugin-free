<?php

namespace GtmEcommerceWoo\Lib\Util;

use GtmEcommerceWoo\Lib\Service\OrderMonitorService;
use GtmEcommerceWoo\Lib\ValueObject\OrderMonitorStatistics;
use WC_Meta_Data;
use WC_Order;

class OrderWrapper {

	use OrderMonitorTrait;

	private $order;

	private $metaData;
	public function __construct( WC_Order $order) {
		$this->order = $order;

		$baseAcc = array_map(function ( $item) {
			return null;
		}, array_flip(OrderMonitorStatistics::IMPORTANT_KEYS));

		$this->metaData = array_reduce($this->order->get_meta_data(), function ( $acc, WC_Meta_Data $item) {
			$data = $item->get_data();

			$acc[$data['key']] = $data['value'];

			return $acc;
		}, $baseAcc);
	}

	public function isTrackedSuccessfully() {
		return true === $this->gtmEnabled($this->metaData)
			&& true === $this->purchaseTracked($this->metaData)
			&& false === $this->blockersEnabled($this->metaData)
			&& true === $this->consentsGranted($this->metaData);
	}

	public function isTrackedWithWarnings() {
		return true === $this->gtmEnabled($this->metaData)
			&& true === $this->purchaseTracked($this->metaData)
			&& ( true === $this->blockersEnabled($this->metaData)
			|| false === $this->consentsGranted($this->metaData) );
	}

	public function isNotTracked() {
		return false === $this->gtmEnabled($this->metaData)
			|| false === $this->purchaseTracked($this->metaData);
	}

	public function isMonitoringAvailable() {
		return true === isset($this->metaData[OrderMonitorService::ORDER_META_KEY_ORDER_MONITOR_CHECK])
			&& null !== $this->metaData[OrderMonitorService::ORDER_META_KEY_ORDER_MONITOR_CHECK];
	}
}
