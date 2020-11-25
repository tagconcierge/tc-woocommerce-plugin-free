<?php

namespace GtmEcommerceWoo\Lib\Util;

use GtmEcommerceWoo\Lib\GaEcommerceEntity\Event;
use GtmEcommerceWoo\Lib\GaEcommerceEntity\Item;

/**
 * Logic to transform WooCommerce datatypes into GA Ecommerce Events types
 */
class WcTransformerUtil {
    public function getItem($product): Item {
        $item = new Item($product->get_title());
        $item->setItemName($product->get_title());
        $item->setItemId($product->get_id());
        $item->setPrice($product->get_price());
        $item->setItemBrand('');
        $item->setItemCategory(wc_get_product_category_list( $product->get_id() ));
        return $item;
    }

    public function getPurchaseFromOrderId($orderId): Event {
        $order = wc_get_order( $orderId );
        $event = new Event('purchase');
        $event->setCurrency($order->get_currency());
        $event->setTransationId($order->get_order_number());
        $event->setAffiliation(get_bloginfo( 'name' ));
        $event->setValue(number_format( $order->get_subtotal() - $order->get_total_discount(), 2, '.', '' ));
        $event->setTax(number_format( $order->get_total_tax(), 2, '.', '' ));
        $event->setShipping(number_format( $order->get_total_shipping(), 2, '.', '' ));
        if ( $order->get_coupon_codes() ) {
            $event->setCoupon(implode( ',', $order->get_coupon_codes() ) );
        }
        foreach ( $order->get_items() as $key => $wcItem ) {
            $product      = $wcItem->get_product();
            $variant_name = ( $wcItem['variation_id'] ) ? wc_get_product( $wcItem['variation_id'] ) : '';
            $item = $this->getItem($product);
            $item->setQuantity($wcItem['qty']);
            $event->addItem($item);
        }
        return $event;
    }
}