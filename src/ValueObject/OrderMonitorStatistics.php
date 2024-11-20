<?php

namespace GtmEcommerceWoo\Lib\ValueObject;

use GtmEcommerceWoo\Lib\EventStrategy\PurchaseStrategy;
use GtmEcommerceWoo\Lib\Service\OrderMonitorService;

class OrderMonitorStatistics {

	const IMPORTANT_KEYS = [
		OrderMonitorService::ORDER_META_KEY_ORDER_MONITOR_CHECK,
		OrderMonitorService::ORDER_META_KEY_ORDER_MONITOR_GTM,
		OrderMonitorService::ORDER_META_KEY_ORDER_MONITOR_ANALYTICS_STORAGE,
		OrderMonitorService::ORDER_META_KEY_ORDER_MONITOR_AD_STORAGE,
		OrderMonitorService::ORDER_META_KEY_ORDER_MONITOR_THANK_YOU_PAGE_VISITED,
		OrderMonitorService::ORDER_META_KEY_ORDER_MONITOR_ADBLOCK,
		OrderMonitorService::ORDER_META_KEY_ORDER_MONITOR_ITP,
		PurchaseStrategy::ORDER_META_KEY_PURCHASE_EVENT_TRACKED,
		self::ORDER_META_KEY_PURCHASE_SERVER_EVENT_TRACKED
	];

	const ORDER_META_KEY_PURCHASE_SERVER_EVENT_TRACKED = 'gtm_ecommerce_woo_purchase_server_event_tracked';

	private $data;

	public function __construct( array $data) {
		$this->data = $data;
	}

	public function getTotal() {
		return $this->formatResult($this->data);
	}

	public function getBlocked( $totalCount) {
		return $this->formatResult(
			array_filter($this->data, function ( $item) {
				return 'true' !== $item[OrderMonitorService::ORDER_META_KEY_ORDER_MONITOR_GTM];
			}),
			$totalCount
		);
	}

	public function getAnalyticsDenied( $totalCount) {
		return $this->formatResult(
			array_filter($this->data, function ( $item) {
				return 'granted' !== $item[OrderMonitorService::ORDER_META_KEY_ORDER_MONITOR_ANALYTICS_STORAGE];
			}),
			$totalCount
		);
	}

	public function getAdDenied( $totalCount) {
		return $this->formatResult(
			array_filter($this->data, function ( $item) {
				return 'granted' !== $item[OrderMonitorService::ORDER_META_KEY_ORDER_MONITOR_AD_STORAGE];
			}),
			$totalCount
		);
	}

	public function getNoThankYouPage( $totalCount) {
		return $this->formatResult(
			array_filter($this->data, function ( $item) {
				return 0 > (int) $item[OrderMonitorService::ORDER_META_KEY_ORDER_MONITOR_THANK_YOU_PAGE_VISITED];
			}),
			$totalCount
		);
	}

	public function getTracked( $totalCount) {
		return $this->formatResult(
			array_filter($this->data, function ( $item) {
				return true === $this->gtmEnabled($item)
					&& true === $this->purchaseTracked($item)
					&& false === $this->blockersEnabled($item)
					&& true === $this->consentsGranted($item);
			}),
			$totalCount
		);
	}

	public function getTrackedWithWarnings( $totalCount) {
		return $this->formatResult(
			array_filter($this->data, function ( $item) {
				return true === $this->gtmEnabled($item)
					&& true === $this->purchaseTracked($item)
					&& ( true === $this->blockersEnabled($item)
					|| false === $this->consentsGranted($item) );
			}),
			$totalCount
		);
	}

	public function getNotTracked( $totalCount) {
		return $this->formatResult(
			array_filter($this->data, function ( $item) {
				return false === $this->gtmEnabled($item)
					|| false === $this->purchaseTracked($item);
			}),
			$totalCount
		);
	}

	private function formatResult( array $data, int $countPercentage = 0) {
		$result = array_reduce($data, function ( $acc, $item) {
			$acc['count']++;
			$acc['value'] += $item['value'];

			return $acc;
		}, ['count' => 0, 'value' => 0.0, 'count_percentage' => false]);

		if (0 < $countPercentage) {
			$result['count_percentage'] = round(( $result['count']/$countPercentage ) * 100);
		}

		return $result;
	}

	private function purchaseTracked( array $item) {
		return '1' === $item[self::ORDER_META_KEY_PURCHASE_SERVER_EVENT_TRACKED]
			|| ( '1' === $item[PurchaseStrategy::ORDER_META_KEY_PURCHASE_EVENT_TRACKED]
			&& 0 < (int) $item[OrderMonitorService::ORDER_META_KEY_ORDER_MONITOR_THANK_YOU_PAGE_VISITED] );
	}

	private function blockersEnabled( array $item) {
		return 'true' === $item[OrderMonitorService::ORDER_META_KEY_ORDER_MONITOR_ITP]
			|| 'true' === $item[OrderMonitorService::ORDER_META_KEY_ORDER_MONITOR_ADBLOCK];
	}

	private function gtmEnabled( array $item) {
		return 'true' === $item[OrderMonitorService::ORDER_META_KEY_ORDER_MONITOR_GTM];
	}

	private function consentsGranted( array $item) {
		return 'granted' === $item[OrderMonitorService::ORDER_META_KEY_ORDER_MONITOR_ANALYTICS_STORAGE]
			&& 'granted' === $item[OrderMonitorService::ORDER_META_KEY_ORDER_MONITOR_AD_STORAGE];
	}
}
