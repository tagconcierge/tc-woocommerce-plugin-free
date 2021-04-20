<?php

namespace GtmEcommerceWoo\Lib\Service;

class ThemeValidatorService {

	protected $wcOutputUtil;
	protected $wpSettingsUtil;
	protected $snakeCaseNamespace;
	protected $spineCaseNamespace;
	protected $wcTransformerUtil;
	protected $tests;

	public function __construct($snakeCaseNamespace, $spineCaseNamespace, $wcTransformerUtil, $wpSettingsUtil, $wcOutputUtil) {
		$this->snakeCaseNamespace = $snakeCaseNamespace;
		$this->spineCaseNamespace = $spineCaseNamespace;
		$this->wcTransformerUtil = $wcTransformerUtil;
		$this->wpSettingsUtil = $wpSettingsUtil;
		$this->wcOutputUtil = $wcOutputUtil;

		$this->tests = [
			'homepage',
			'product_category',
			'product',
		];
	}

	public function initialize() {
		add_action( 'wp_ajax_gtm_ecommerce_woo_post_validate_theme', [$this, 'ajaxPostValidateTheme'] );

		if ($this->wpSettingsUtil->getOption('theme_validator_enabled') !== '1') {
			return;
		}
		if (@$_GET['gtm-ecommerce-woo-validator'] !== md5($this->wpSettingsUtil->getOption('uuid'))) {
			return;
		}
		add_action( 'wp', [$this, 'wp'] );
		add_action( 'wp_head', [$this, 'wpHead'] );
		add_action( 'the_post', [$this, 'thePost'] );
		add_action( 'woocommerce_thankyou', [$this, 'woocommerceThankyou'] );
		add_action( 'woocommerce_before_checkout_form', [$this, 'woocommerceBeforeCheckoutForm'] );
		add_action( 'wp_footer', [$this, 'wpFooter'] );
		add_action( 'the_widget', [$this, 'theWidget'] );
		add_filter( 'render_block', [$this, 'renderBlock'], 10, 2 );
	}

	public function ajaxPostValidateTheme() {
		// get a product
		// get an order
		$query = new \WC_Order_Query( array(
			'orderby' => 'date',
			'order' => 'DESC',
			'limit' => 1,
			'status' => ['on-hold', 'completed']
		) );
		$orders = $query->get_orders();
		if (count($orders) === 0) {
			$thankYou = null;
		} else {
			$thankYou = $orders[0]->get_checkout_order_received_url();
		}

		$query = new \WC_Product_Query( array(
			'orderby' => 'date',
			'order' => 'DESC',
			'limit' => 1,
			'status' => ['publish']
		) );
		$products = $query->get_products();
		if (count($products) === 0) {
			$productUrl = null;
		} else {
			$productUrl = $products[0]->get_permalink();
		}

		$categories = get_terms( ['taxonomy' => 'product_cat'] );
		if (count($categories) === 0) {
			$productCatUrl = null;
		} else {
			$productCatUrl = get_term_link($categories[0]);
		}

		$payload = [
			'platform' => 'woocommerce',
			'uuid_hash' => md5($this->wpSettingsUtil->getOption('uuid')),
			'urls' => [
				'product_category' => $productCatUrl,
				'product' => $productUrl,
				'cart' => wc_get_cart_url(),
				'checkout' => wc_get_checkout_url(),
				'home' => get_home_url(),
				'thank_you' => $thankYou,
				// 'thank_you' =>    $return_url = $order->get_checkout_order_received_url();
				//     } else {
				//         $return_url = wc_get_endpoint_url( 'order-received', '', wc_get_checkout_url() );
			]
		];
		$args = [
			'body' => json_encode($payload),
			'headers' => [
				'content-type' => 'application/json'
			],
			'data_format' => 'body',
		];
		var_dump(json_encode($payload));
		// $response = wp_remote_post( 'https://api.tagconcierge.com/v2/validate-theme', $args );
		$response = wp_remote_post( 'http://api-concierge/v2/validate-theme', $args );
		$body     = wp_remote_retrieve_body( $response );
		wp_send_json(json_decode($body));
		wp_die();
	}

	public function wp() {

		$theme = wp_get_theme();
		$parent = $theme->parent();
		$parentName = null;
		$parentVersion = null;
		if ($parent) {
			$parentName = $parent->get("Name");
			$parentVersion = $parent->get("Version");
		}

		$params = [
			'theme_name' => $theme->get("Name"),
			'theme_version' => $theme->get("Version"),
			'theme_parent_name' => $parentName,
			'theme_parent_version' => $parentVersion,
			'is_woocommerce' => is_woocommerce(),
			'is_shop' => is_shop(),
			'is_product_category' => is_product_category(),
			'is_product' => is_product(),
			'is_cart' => is_cart(),
			'is_checkout' => is_checkout(),
			'is_front_page' => is_front_page(),
			'is_home' => is_home(),
			'post_type' => get_post_type()
		];

		$string = array_reduce(array_keys($params), function($agg, $key) use ($params) {
			$value = $params[$key];
			if (is_bool($value)) {
				$value = ($value === true) ? "true" : "false";
			}
			$agg .= "$key: $value; ";
			return $agg;
		}, '');
		echo "<!--[if !IE]><!-- gtm-ecommerce-woo: wp; $string--><![endif]-->\n";
	}

	public function wpHead() {
		echo "<!-- gtm-ecommerce-woo: wp_head -->\n";
	}

	public function thePost($post) {
		$id = null;
		if ($post) {
			$id = $post->ID;
		}
		echo "<!-- gtm-ecommerce-woo: the_post; id: $id; -->\n";
	}

	public function wpFooter() {
		echo "<!-- gtm-ecommerce-woo: wp_footer -->\n";
	}

	public function woocommerceThankyou() {
		echo "<!-- gtm-ecommerce-woo: woocommerce_thankyou -->\n";
	}

	public function woocommerceBeforeCheckoutForm() {
		echo "<!-- gtm-ecommerce-woo: woocommerce_before_checkout_form -->\n";
	}

	public function theWidget($widget) {
		echo "<!-- gtm-ecommerce-woo: the_widget; widget: $widget -->\n";
	}

	public function renderBlock($blockContent, $block) {
		echo "<!-- gtm-ecommerce-woo: render_block; block_name: ${block['blockName']} -->\n";
		return $blockContent;
	}

}
