<?php

namespace GtmEcommerceWoo\Lib\EventStrategy;

/**
 * AddToCart event
 */
class AddToCartStrategy extends AbstractEventStrategy {

	protected $eventName = 'add_to_cart';
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

	public function productLoop() {
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
	 * We are on the single product page
	 */
	public function singleProduct() {
		global $product;
		// if product is null then this must be other WP post
		if (is_null($product)) {
			return false;
		}
		if (is_product() && false === $this->firstPost) {
			$item = $this->wcTransformer->getItemFromProduct($product);
			$this->onCartSubmitScript($item);
			$this->firstPost = true;
		}
	}

	/**
	 * Supports the button that is supposed to live in a form object
	 */
	public function onCartSubmitScript( $item) {
		$bypassUnquote = <<<'EOD'
var $form = jQuery(ev.currentTarget).parents('form.cart');
var quantity = jQuery('[name="quantity"]', $form).val();
var product_id = jQuery('[name="add-to-cart"]', $form).val();
EOD;

		$jsonItem = json_encode($item);
		$this->wcOutput->script(<<<EOD
jQuery(document).on('click', '.cart .single_add_to_cart_button', function(ev) {
	${bypassUnquote}

	var item = ${jsonItem};
	item.quantity = parseInt(quantity);
	dataLayer.push({
	  'event': 'add_to_cart',
	  'ecommerce': {
		'value': (item.price * quantity),
		'items': [item]
	  }
	});
});
EOD
);

	}

	/**
	 * Supports a single link that's present on product lists
	 */
	public function onCartLinkClick( $items) {
		$this->wcOutput->globalVariable('gtm_ecommerce_woo_items_by_product_id', $items);
		$this->wcOutput->script(<<<'EOD'
jQuery(document).on('click', '.ajax_add_to_cart', function(ev) {
    var targetElement = jQuery(ev.currentTarget);

    if (0 === targetElement.length) {
        return;
    }

    var product_id = targetElement.data('product_id');

    if (undefined === product_id) {
        return;
    }

	var quantity = targetElement.data('quantity') ?? 1;
	var item = gtm_ecommerce_woo_items_by_product_id[product_id];
	item.quantity =  parseInt(quantity);
	dataLayer.push({
	  'event': 'add_to_cart',
	  'ecommerce': {
		'value': (item.price * quantity),
		'items': [item]
	  }
	});
});
EOD
);
	}
}
