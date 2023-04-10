<?php

namespace GtmEcommerceWoo\Lib\EventStrategy;

use WC_Order;

class PurchaseStrategy extends AbstractEventStrategy {

	const ORDER_META_KEY_PURCHASE_EVENT_TRACKED = 'gtm_ecommerce_woo_purchase_event_tracked';

	protected $eventName = 'purchase';

	public function defineActions() {
		return [
			'woocommerce_thankyou' => [$this, 'thankyou'],
		];
	}

	public function thankyou( $orderId ) {
		$order = wc_get_order( $orderId );

		if (false === $order instanceof WC_Order) {
			return;
		}

		$event = $this->wcTransformer->getPurchaseFromOrder($order);

		$this->wcOutput->dataLayerPush($event);

		$order->update_meta_data(self::ORDER_META_KEY_PURCHASE_EVENT_TRACKED, '1');
		$order->save();
	}
}
