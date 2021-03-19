<?php

namespace GtmEcommerceWoo\Lib\EventStrategy;

/**
 * AddToCart event
 */
class AddToCartStrategy extends AbstractEventStrategy {

    protected $itemsByProductId;
    protected $firstPost;

    public function defineActions() {
        return [
            'the_post' => [[$this, 'thePost'], 11],
            'wp_footer' => [$this, 'afterShopLoop'],
        ];
    }

    public function initialize() {
        $this->itemsByProductId = [];
        $this->firstPost = false;
    }

    public function thePost() {
        $this->productLoop();
        $this->singleProduct();
    }

    function productLoop() {
        global $product;
        if (is_a($product, 'WC_Product')) {
            $item = $this->wcTransformer->getItemFromProduct($product);
            $this->itemsByProductId[$product->get_id()] = $item;
        }
    }

    public function afterShopLoop() {
        if (is_array($this->itemsByProductId) && count($this->itemsByProductId) > 0) {
            $this->onCartLinkClick($this->itemsByProductId);
        }
    }

    /**
     * we are on the single product page
     */
    public function singleProduct() {
        global $product;
        if (is_product() && $this->firstPost === false) {
            $item = $this->wcTransformer->getItemFromProduct($product);
            $this->onCartSubmitScript($item);
            $this->firstPost = true;
        }
    }

    /**
     * supports the button that is supposed to live in a form object
     */
    public function onCartSubmitScript($item) {
        $this->wcOutput->globalVariable('gtm_ecommerce_woo_item', $item);
        $this->wcOutput->script(<<<EOD
jQuery(document).on('submit', '.cart', function(ev) {
    var quantity = jQuery('[name="quantity"]', ev.currentTarget).val();
    var product_id = jQuery('[name="add-to-cart"]', ev.currentTarget).val();

    var item = gtm_ecommerce_woo_item;
    item.quantity = quantity;
    dataLayer.push({
      'event': 'add_to_cart',
      'ecommerce': {
        'items': [item]
      }
    });
});
EOD
);

    }

    /**
     * supports a single link that's present on product lists
     */
    public function onCartLinkClick($items) {
        $this->wcOutput->globalVariable('gtm_ecommerce_woo_items_by_product_id', $items);
        $this->wcOutput->script(<<<EOD
jQuery(document).on('click', '.ajax_add_to_cart', function(ev) {
    var quantity = jQuery(ev.currentTarget).data('quantity');
    var product_id = jQuery(ev.currentTarget).data('product_id');
    var item = gtm_ecommerce_woo_items_by_product_id[product_id];
    item.quantity = quantity;
    dataLayer.push({
      'event': 'add_to_cart',
      'ecommerce': {
        'items': [item]
      }
    });
});
EOD
);
    }
}
