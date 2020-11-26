<?php

namespace GtmEcommerceWoo\Lib\EventStrategy;

class AddToCartStrategy extends AbstractEventStrategy {

    protected $itemsByProductId;

    public function defineActions() {
        return [
            'woocommerce_after_add_to_cart_button' => [$this, 'afterAddToCartButton'],
            'woocommerce_loop_add_to_cart_link' => [$this, 'loopAddToCartLink'],
            'woocommerce_before_shop_loop' => [$this, 'beforeShopLoop'],
            'woocommerce_after_shop_loop' => [$this, 'afterShopLoop'],
        ];
    }

    public function beforeShopLoop() {
        $this->itemsByProductId = [];
    }

    function loopAddToCartLink( $add_to_cart_html ) {
        global $product;
        $item = $this->wcTransformer->getItemFromProduct($product);
        $this->itemsByProductId[$product->get_id()] = $item;
        return $add_to_cart_html;
    }

    public function afterShopLoop() {
        $this->onCartLinkClick($this->itemsByProductId);
    }

    public function afterAddToCartButton() {
        global $product;
        $item = $this->wcTransformer->getItemFromProduct($product);
        $this->onCartSubmitScript($item);
    }

    public function onCartSubmitScript($item) {
        $this->wcOutput->globalVariable('gtm_ecommerce_woo_item', $item);
        $this->wcOutput->script(<<<EOD
jQuery('.cart').submit(function(ev) {
    var quantity = jQuery('[name="quantity"]', ev.currentTarget).val();
    var product_id = jQuery('[name="add-to-cart"]', ev.currentTarget).val();
    var item = window.gtm_ecommerce_woo_item;
    item.quantity = quantity;
    dataLayer.push({
      'event': 'add_to_cart',
      'ecommerce': {
        'items': [item]
      }
    });
});
EOD);

    }

    public function onCartLinkClick($items) {
        $this->wcOutput->globalVariable('gtm_ecommerce_woo_items_by_product_id', $items);
        $this->wcOutput->script(<<<EOD
jQuery('.ajax_add_to_cart').click(function(ev) {
    var quantity = jQuery(ev.currentTarget).attr('data-quantity');
    var product_id = jQuery(ev.currentTarget).attr('data-product_id');
    var item = gtm_ecommerce_woo_items_by_product_id[product_id];
    item.quantity = quantity;
    dataLayer.push({
      'event': 'add_to_cart',
      'ecommerce': {
        'items': [item]
      }
    });
});
EOD);
    }
}