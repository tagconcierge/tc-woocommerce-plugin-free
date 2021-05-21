<?php

namespace GtmEcommerceWoo\Lib\EventStrategy;

class PurchaseStrategy extends AbstractEventStrategy {

	protected $eventName = 'purchase';

	public function defineActions() {
		return [
			'woocommerce_thankyou' => [$this, 'thankyou'],
		];
	}


	function thankyou( $orderId ) {
		$event = $this->wcTransformer->getPurchaseFromOrderId($orderId);

		$this->wcOutput->dataLayerPush($event);

		update_post_meta( $orderId, 'gtm_ecommerce_woo_purchase_event_tracked', "1" );
	}
}
