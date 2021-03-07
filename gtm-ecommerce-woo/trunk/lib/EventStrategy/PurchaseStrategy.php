<?php

namespace GtmEcommerceWoo\Lib\EventStrategy;

class PurchaseStrategy extends AbstractEventStrategy {

    public function defineActions() {
        return [
            'woocommerce_thankyou' => [$this, 'thankyou'],
        ];
    }


    function thankyou( $orderId ) {
        $event = $this->wcTransformer->getPurchaseFromOrderId($orderId);

        $this->wcOutput->dataLayerPush($event);
    }
}
