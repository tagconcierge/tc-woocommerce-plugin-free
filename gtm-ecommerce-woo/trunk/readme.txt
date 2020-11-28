=== GTM Ecommerce for WooCommerce ===
Contributors: Handcraft Byte
Tags: google tag manager, google analytics, data layer, ecommerce events
Requires at least: 5.1
Tested up to: 5.5
Requires PHP: 7.2
Stable tag: trunk
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Push WooCommerce Ecommerce (**GA4 Ecommerce compatible**) information to GTM DataLayer. Use any GTM integration to measure your customers' activites.

== Description ==

Do you own **WooCommerce shop** and you want to track and analyze your customers' activites?

This plugin push standard Ecommerce information to GTM Data Layer.
Once this information is available in your GTM workspace you can plug in and use any tool available. Even if you are unsure yet which tool you would need or like to use.

## Example scenarios

1. Measure ecommerce behaviors in Google Analytics (**GA4 properties are supported**)
2. Track conversions from Facebook and/or Instagram campaigns
3. Track conversions from Google Ads campaigns


## Supported events

After plugin is installed it automatically tracks following events:

- Add To Cart
- Purchase

Which are great base for **conversion measurements** and building **sales funnels** related to cart behavior.

## Advantage over alternative solutions

Without GTM Ecommerce for WooCommerce plugin you would need a separate plugin for each of those integrations. And each additional plugin may make your Wordpress setup more complex.
With GTM Ecommerce for WooCommerce everything is sent in standarized Google format to GTM and everything else is configure there.

Common problem when trying to use other GTM and Google Analytics plugins is that data can be sent twice corrupting analytics reporting. Using DataLayer is a standard way to ensure your tracking information stays consistent.

== Installation ==

1. Upload or install GTM Ecommerce for WooCommerce plugin from WordPress plugins directory.
2. Activate the plugin through the 'Plugins' menu in WordPress.
3. That's it! If GTM is already implemented in you WordPress your Ecommerce data will be pushed to GTM DataLayer. If not head to `Settings > GTM Ecommerce` and paste in GTM snippets.
4. Go to your Google Tag Manager workspace and define what you want to do with the tracked data. We know that settings up GTM workspace may be cumbersome. That's why the plugin comes with a JSON file you can import to your GTM workspace to create all required Tags, Triggers and Variables.

== Frequently Asked Questions ==

= How to inject GTM tracking snippet? =

By default this plugin push Ecommerce information to GTM DataLayer object that can be installed by other plugins or directly in the theme code.
It can also embed GTM snippets, go to settings to configure it.

= How to setup my GTM tags and triggers now? =

We know that settings up GTM workspace may be cumbersome. That's why the plugin comes with a JSON file you can import to your GTM workspace to create all required Tags, Triggers and Variables.


= What Ecommerce events are supported? =

This version of the plugin supports just `purchase` and `add_to_cart` events. If you need more let us know [here](https://michal159509.typeform.com/to/Epux8hoP).

= Is GA4 and Universal Analytics supported? =

Currently, the plugin supports latest GA4 format and won't work with legacy Universal Analytics properties. <strong>We plan introducing a compatibility layer. If you need UA compatiblity, fill in <a href="https://michal159509.typeform.com/to/VNbZrezV" target="_blank">this survey</a> to help us prioritize it!</strong>

== Screenshots ==

1. **GTM Ecommerce for WooCommerce** plugin sucesfully installed!
2. `add_to_cart` event captured in GTM debugger
3. `purchase` event captured in GTM debugger
4. How to import provided GTM container?
5. GTM workspace tags after importer provided JSON file
6. `add_to_cart` event pushed to GA4 property with captured variables
7. `purchase` event pushed to GA4 property with captured variables


== Changelog ==

= 1.3.0 =

* Fixed settings sections
* Provide a GTM container to import in workspace

= 1.2.0 =

* Document possible UA compatibility feature

= 1.1.0 =

* Fix disabling plugin

= 1.0.0 =

* Initial version
