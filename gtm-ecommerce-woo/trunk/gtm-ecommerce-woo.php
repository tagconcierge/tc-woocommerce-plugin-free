<?php
/**
 * Plugin Name: GTM Ecommerce for WooCommerce
 * Plugin URI:  https://wordpress.org/plugins/gtm-ecommerce-woo
 * Description: Push WooCommerce Ecommerce (GA4 compatible) information to GTM DataLayer. Use any GTM integration to measure your customers' activites.
 * Version:     1.4.3
 * Author:      Handcraft Byte
 * Author URI:  https://handcraftbyte.com/
 * License:     GPLv2 or later
 * License URI: http://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: gtm-ecommerce-woo
 * Domain Path: /languages
 */

namespace GtmEcommerceWoo;

require __DIR__ . '/vendor/autoload.php';

use GtmEcommerceWoo\Lib\Container;

$container = new Container();

$container->getSettingsService()->initialize();
$container->getGtmSnippetService()->initialize();
$container->getEventStrategiesService()->initialize();
$pluginService = $container->getPluginService();
$pluginService->initialize();

register_activation_hook( __FILE__, [$pluginService, 'activationHook'] );

