=== GTM Ecommerce for WooCommerce ===
Contributors: Handcraft Byte
Tags: google tag manager, woocommerce, ga4, Google Analytics, universal analytics, data layer, enhanced ecommerce
Requires at least: 5.1
Tested up to: 5.5
Requires PHP: 7.2
Stable tag: trunk
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Push WooCommerce Ecommerce (**Enhanced Ecommerce and GA4**) information to GTM DataLayer. Use any GTM integration to measure your customers' activites.

== Description ==

Do you own **WooCommerce shop** and you want to track and analyze your customers' activites?

This plugin push standard Ecommerce information to GTM Data Layer.
Once this information is available in your GTM workspace you can plug in and use any tool available. Even if you are unsure yet which tool you would need to use.

The most basics use cases are following:

1. Measure ecommerce behaviors in Google Analytics (both Universal Analytics and GA4)
2. Track conversions from Facebook and/or Instagram campaigns
3. Track conversions from Google Ads campaigns

Without Enhanced Ecommerce GTM for WooCommerce plugin you would need a separate plugin for each of those integrations. And each additional plugin may make your Wordpress setup more complex.
With Enhance Ecommerce GTM for WooCommece just intall this plugin and then configure everything else in GTM.

If you are already using Google Analytics with GTM other plugins integrating WooCommerce directly with GA may cause duplicated tracking. Using DataLayer is a standard way to ensure your tracking information stays consistent.


== Installation ==

1. Upload or install GTM Ecommerce for WooCommerce plugin from WordPress plugins directory.
2. Activate the plugin through the 'Plugins' menu in WordPress.
3. That's it! Your Ecommerce will be pushed to GTM DataLayer.
4. Go to your Google Tag Manager workspace and define what you want to do with the tracked data.

== Frequently Asked Questions ==

= How to inject GTM tracking snippet? =

By default this plugin push Ecommerce information to GTM DataLayer object that can be installed by other plugins or directly in the theme code.
It can also embed GTM snippets, go to settings to configure it.


= How to setup my GTM tags and triggers now? =

It sounds you need some help in setting up your GTM container. Reach out to us using [this form](https://michal159509.typeform.com/to/IKbbSUXQ) and let's see what can be done.

= What Enhanced Ecommerce events and properties are supported? =

This version of the plugin supports just `purchase` and `addToCart` events. If you need more let us know [here](https://michal159509.typeform.com/to/Epux8hoP).

== Screenshots ==

1. **GTM Ecommerce for WooCommerce** plugin sucesfully installed!
2. `addToCart` event captured in GTM debugger
3. `purchase` event captured in GTM debugger


== Changelog ==

= 1.0.0 =

* Initial version
