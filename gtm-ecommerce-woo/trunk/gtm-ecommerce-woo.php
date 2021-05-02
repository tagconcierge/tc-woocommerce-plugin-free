<?php
/**
 * Plugin Name: Google Tag Manager for WooCommerce FREE
 * Plugin URI:  https://wordpress.org/plugins/gtm-ecommerce-woo
 * Description: Push WooCommerce eCommerce (GA4 and UA compatible) information to GTM DataLayer. Use any GTM integration to measure your customers' activites.
 * Version:     1.6.0
 * Author:      Handcraft Byte
 * Author URI:  https://handcraftbyte.com/
 * License:     GPLv2 or later
 * License URI: http://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: gtm-ecommerce-woo
 * Domain Path: /languages
 *
 * WC requires at least: 4.0
 * WC tested up to: 4.9
 */

namespace GtmEcommerceWoo;

require __DIR__ . '/vendor/autoload.php';

use GtmEcommerceWoo\Lib\Container;

define('MAIN_FILE', __FILE__);
define('MAIN_DIR', __DIR__);

$container = new Container();

$container->getSettingsService()->initialize();
$container->getGtmSnippetService()->initialize();
$container->getEventStrategiesService()->initialize();
$container->getThemeValidatorService()->initialize();
$container->getEventInspectorService()->initialize();

$debuggerService = $container->getDebuggerService();
$debuggerService->initialize();

$pluginService = $container->getPluginService();
$pluginService->initialize();

register_activation_hook( __FILE__, [$pluginService, 'activationHook'] );
register_deactivation_hook( __FILE__, [$debuggerService, 'deactivationHook'] );

