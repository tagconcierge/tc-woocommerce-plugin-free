<?php

namespace GtmEcommerceWoo\Lib\Util;

use GtmEcommerceWoo\Lib\GaEcommerceEntity\Event;
use GtmEcommerceWoo\Lib\GaEcommerceEntity\Item;

/**
 * Logic to transform WooCommerce datatypes into GA Ecommerce Events types
 */
class WcTransformerUtil {


    /**
     * https://woocommerce.github.io/code-reference/classes/WC-Order-Item.html
     * https://woocommerce.github.io/code-reference/classes/WC-Order-Item-Product.html
     */
    public function getItemFromOrderItem($orderItem): Item {
        $product      = $orderItem->get_product();
        $variantProduct = ( $orderItem->get_variation_id() ) ? wc_get_product( $orderItem->get_variation_id() ) : '';

        $item = new Item($orderItem->get_name());
        $item->setItemId($product->get_id());
        $item->setPrice($product->get_price());
        $item->setItemVariant($variantProduct);
        $item->setQuantity($orderItem->get_quantity());
        // $item->setItemBrand('');

        $itemCats = get_the_terms( $product->get_id(), 'product_cat' );
        if (is_array($itemCats)) {
            $categories = array_map(
                function($category) { return $category->name; },
                get_the_terms( $product->get_id(), 'product_cat' )
            );
            $item->setItemCategories($categories);
        }
        return $item;
    }

    /**
     * https://woocommerce.github.io/code-reference/classes/WC-Product.html
     * https://woocommerce.github.io/code-reference/classes/WC-Product-Simple.html
     */
    public function getItemFromProduct($product): Item {
        $item = new Item($product->get_name());
        $item->setItemId($product->get_id());
        $item->setPrice($product->get_price());
        // $item->setItemBrand('');
        $productCats = get_the_terms( $product->get_id(), 'product_cat' );
        if (is_array($productCats)) {
            $categories = array_map(
                function($category) { return $category->name; },
                $productCats
            );
            $item->setItemCategories($categories);
        }
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

        foreach ( $order->get_items() as $key => $orderItem ) {
            $item = $this->getItemFromOrderItem($orderItem);
            $event->addItem($item);
        }
        return $event;
    }
}
