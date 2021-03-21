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
		];
	}

	public function initialize() {
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

}
