<?php
/**
 * Plugin Name: Tag Pilot FREE - Google Tag Manager Integration for WooCommerce
 * Plugin URI:  https://wordpress.org/plugins/gtm-ecommerce-woo
 * Description: Complete Google Tag Manager plugin for WooCommerce, Consent Mode v2 and Server-Side GTM ready. Ready GTM configuration for GA4 and Facebook Pixel. Built-in product feed for Google Merchant Center.
 * Version:     1.11.0
 * Author:      Tag Concierge
 * Author URI:  https://tagconcierge.com/
 * License:     GPLv2 or later
 * License URI: http://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: gtm-ecommerce-woo
 * Domain Path: /languages
 *
 * WC requires at least: 4.0
 * WC tested up to: 9.3.3
 */

namespace GtmEcommerceWoo;

require __DIR__ . '/vendor/autoload.php';

use Automattic\WooCommerce\Utilities\FeaturesUtil;
use GtmEcommerceWoo\Lib\Container;

$pluginData = get_file_data(__FILE__, array('Version' => 'Version'), false);
$pluginVersion = $pluginData['Version'];

add_action( 'before_woocommerce_init', function() {
	if ( class_exists( FeaturesUtil::class ) ) {
		FeaturesUtil::declare_compatibility( 'custom_order_tables', __FILE__, true );
	}
});

$container = new Container($pluginVersion);

$container->getSettingsService()->initialize();
$container->getGtmSnippetService()->initialize();
$container->getEventStrategiesService()->initialize();
$container->getEventInspectorService()->initialize();
$container->getProductFeedService()->initialize();

$pluginService = $container->getPluginService();
$pluginService->initialize();

register_activation_hook( __FILE__, [$pluginService, 'activationHook'] );
