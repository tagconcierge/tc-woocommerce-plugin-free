<?php
/**
 * Plugin Name: Google Tag Manager for WooCommerce FREE
 * Plugin URI:  https://wordpress.org/plugins/gtm-ecommerce-woo
 * Description: Push WooCommerce eCommerce (GA4 and UA compatible) information to GTM DataLayer. Use any GTM integration to measure your customers' activities.
 * Version:     1.10.8
 * Author:      Handcraft Byte
 * Author URI:  https://handcraftbyte.com/
 * License:     GPLv2 or later
 * License URI: http://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: gtm-ecommerce-woo
 * Domain Path: /languages
 *
 * WC requires at least: 4.0
 * WC tested up to: 7.5.0
 */

namespace GtmEcommerceWoo;

require __DIR__ . '/vendor/autoload.php';

use GtmEcommerceWoo\Lib\Container;

define('MAIN_FILE', __FILE__);
define('MAIN_DIR', __DIR__);

$pluginData = get_file_data(__FILE__, array('Version' => 'Version'), false);
$pluginVersion = $pluginData['Version'];

$container = new Container($pluginVersion);

$container->getSettingsService()->initialize();
$container->getGtmSnippetService()->initialize();
$container->getEventStrategiesService()->initialize();
$container->getThemeValidatorService()->initialize();
$container->getEventInspectorService()->initialize();

$monitorService = $container->getMonitorService();
$monitorService->initialize();

$pluginService = $container->getPluginService();
$pluginService->initialize();

register_activation_hook( __FILE__, [$pluginService, 'activationHook'] );
register_deactivation_hook( __FILE__, [$monitorService, 'deactivationHook'] );
