<?php

namespace GtmEcommerceWoo\Lib\Util;

use GtmEcommerceWoo\Lib\Service\OrderMonitorService;

trait OrderMonitorTrait {

	public function purchaseTracked() {
		return '1' === $this->metaData[OrderMonitorService::ORDER_META_KEY_PURCHASE_SERVER_EVENT_TRACKED]
			|| ( '1' === $this->metaData[OrderMonitorService::ORDER_META_KEY_PURCHASE_EVENT_TRACKED]
				&& 0 < (int) $this->metaData[OrderMonitorService::ORDER_META_KEY_ORDER_MONITOR_THANK_YOU_PAGE_VISITED] )
			|| ( '1' === $this->metaData[OrderMonitorService::ORDER_META_KEY_PURCHASE_EVENT_TRACKED]
				&& '1' === $this->metaData[OrderMonitorService::ORDER_META_KEY_PURCHASE_EVENT_TRACKED_ON_ORDER_FORM] );
	}

	protected function blockersEnabled( array $metaData) {
		return 'true' === $metaData[OrderMonitorService::ORDER_META_KEY_ORDER_MONITOR_ITP]
			|| 'true' === $metaData[OrderMonitorService::ORDER_META_KEY_ORDER_MONITOR_ADBLOCK];
	}

	public function gtmEnabled() {
		return 'true' === $this->metaData[OrderMonitorService::ORDER_META_KEY_ORDER_MONITOR_GTM];
	}

	protected function consentsGranted( array $metaData) {
		return 'granted' === $metaData[OrderMonitorService::ORDER_META_KEY_ORDER_MONITOR_ANALYTICS_STORAGE]
			&& 'granted' === $metaData[OrderMonitorService::ORDER_META_KEY_ORDER_MONITOR_AD_STORAGE];
	}

	public function adConsentGranted() {
		return 'granted' === $this->metaData[OrderMonitorService::ORDER_META_KEY_ORDER_MONITOR_AD_STORAGE];
	}

	public function analyticsConsentGranted() {
		return 'granted' === $this->metaData[OrderMonitorService::ORDER_META_KEY_ORDER_MONITOR_ANALYTICS_STORAGE];
	}

	public function adblockEnabled() {
		return 'true' === $this->metaData[OrderMonitorService::ORDER_META_KEY_ORDER_MONITOR_ADBLOCK];
	}

	public function itpEnabled() {
		return 'true' === $this->metaData[OrderMonitorService::ORDER_META_KEY_ORDER_MONITOR_ITP];
	}
}
