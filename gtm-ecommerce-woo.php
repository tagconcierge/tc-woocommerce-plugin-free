<?php
/**
 * Plugin Name: GTM for WooCommerce FREE - Google Tag Manager Integration
 * Plugin URI:  https://wordpress.org/plugins/gtm-ecommerce-woo
 * Description: Enable new growth channels for WooCommerce with GA4, Facebook Pixel and Consent Mode ready GTM integration. Use packaged GTM presets for quick installation.
 * Version:     1.10.34
 * Author:      Tag Concierge
 * Author URI:  https://tagconcierge.com/
 * License:     GPLv2 or later
 * License URI: http://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: gtm-ecommerce-woo
 * Domain Path: /languages
 *
 * WC requires at least: 4.0
 * WC tested up to: 8.9.1
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

$pluginService = $container->getPluginService();
$pluginService->initialize();

register_activation_hook( __FILE__, [$pluginService, 'activationHook'] );
