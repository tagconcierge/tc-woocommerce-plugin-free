=== GTM for WooCommerce FREE ===
Contributors: Handcraft Byte
Tags: google tag manager, GA4, ecommerce events, Google Analytics, Facebook Pixel, shopping behavior
Requires at least: 5.1.0
Tested up to: 6.2
Requires PHP: 7.0
Stable tag: 1.10.10
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Push WooCommerce eCommerce information (**GA4 Ecommerce and UA Enhanced Ecommerce compatible**) to GTM DataLayer. Use any Google Tag Manager integration to measure your customers' activities.

== Description ==

Do you own **WooCommerce shop** and you want to track and analyze your customers' activities?

This plugin push standard eCommerce information to GTM Data Layer.
Once this information is available in your GTM workspace you can plugin and use any tool available. Even if you are unsure yet which tool you would need or like to use.

Check [live demo here](https://demo-woocommerce.tagconcierge.com/) (perform typical shop activities - add to cart, purchase - to see how events are triggered). This demo showcase all PRO plugin features - FREE version is limited to 2 events listed below.

More information and documentation can be found [here](https://docs.tagconcierge.com/).

## Example scenarios

1. Measure eCommerce behaviors in Google Analytics (**GA4 and legacy UA properties are supported**)
2. Track conversions from Facebook and/or Instagram campaigns
3. Track conversions from Google Ads campaigns (requires PRO version)


## Supported events

After the plugin is installed it automatically tracks the following events:

- Add To Cart
- Purchase

Which are a great base for **conversion measurements** and building **sales funnels** related to cart behavior.

**For full eCommerce events coverage, improved handling and professional support [buy PRO version](https://tagconcierge.com/google-tag-manager-for-woocommerce/).**

## Advantage over alternative solutions

### Only single plugin needed

Without GTM for WooCommerce plugin, you would need a separate plugin for each of those integrations. And each additional plugin may make your Wordpress setup more complex.
With GTM for WooCommerce, all data is sent in standardized Google format to GTM and everything else is configured there.

### Reporting consistency

A common problem when trying to use other GTM and Google Analytics plugins is that data can be sent twice corrupting analytics reporting. Using DataLayer is a standard way to ensure your tracking information stays consistent.

### Cookies opt-in

Using GTM and dataLayer allows to build more complex scenarios, such as only processing the data after obtaining user consent. Other plugin directly pipe events into target system no matter if required permission was given or not. This is a more advanced scenario that requires manual changes in Google Tag Manager, but out-of-box support may be added in this plugin in future.


== Installation ==

1. Upload or install GTM for WooCommerce plugin from WordPress plugins directory.
2. Activate the plugin through the `Plugins` menu in WordPress.
3. That's it! If GTM is already implemented in your WordPress your eCommerce data will be pushed to GTM DataLayer. If not head to `Settings > Google Tag Manager` and paste in GTM snippets.
4. Go to your Google Tag Manager workspace and define what you want to do with the tracked data. We know that settings up the GTM workspace may be cumbersome. That's why the plugin comes with a GTM container presets that you can import to your GTM workspace to create all required Tags, Triggers, and Variables in few simple clicks.

### How to use JSON file with GTM container?

In order to perform such import go to `Settings > Google Tag Manager` and scroll down. There is a link to the JSON file with the GTM container. Save it as a JSON file on your computer.

Then in GTM go to the `Admin` panel of your workspace. Click `Import Container`. Choose the container file you have just downloaded. Decide which workspace it should go to. Then select `Merge` and `Overwrite conflicting tags, triggers and variables.` as import options.
Hit `Confirm` to create tags, variables, and triggers for pushing Ecommerce events to the GA4 property.

The last step is to define `GA4 Measurement ID`. Go to `Variables` find a variable with the `GA4 Measurement ID` name, click to edit it. Then paste your GA4 Measurement ID in the Value field.

If you are importing `UA Compatible` preset you can find the tracking ID in the `UA Settings` variable.

If you are importing `Facebook Pixel` preset you can find the tracking ID in `FBP Pixel ID` variable.

Save and submit all the changes to make it live.

You can find more detailed guides [here](https://docs.tagconcierge.com/).

== Frequently Asked Questions ==

= How to inject GTM tracking snippet? =

By default this plugin push eCommerce information to the GTM DataLayer object that can be installed by other plugins or directly in the theme code.
It can also embed GTM snippets, go to settings to configure it.

= How to setup my GTM tags and triggers now? =

We know that settings up the GTM workspace may be cumbersome. That's why the plugin comes with GTM container presets that you can import to your GTM workspace to create all required Tags, Triggers, and Variables.

See the Installation tab above or [our guides](https://tagconcierge.com/google-tag-manager-for-woocommerce/) for details.

= What eCommerce events are supported? =

This version of the plugin supports just `purchase` and `add_to_cart` events.
Our PRO version available [here](https://tagconcierge.com/google-tag-manager-for-woocommerce/) supports more.

= Is GA4 and Universal Analytics supported? =

Yes! You can use GA4 and UA in any combination. Just use the appropriate preset available in the settings screen of the plugin. You can use both at the same time as well.

The plugin always emits events in GA4 compatible format, but the UA preset contains a compatibility tag that additionally emits UA compatible events.

== Screenshots ==

1. **GTM for WooCommerce** settings and GTM snippets
2. eCommerce results in GA4 property
3. eCommerce results in Universal Analytics property
4. `add_to_cart` event captured in GTM debugger
5. `purchase` event captured in GTM debugger
6. How to import the provided GTM container?
7. GTM workspace tags after importer provided JSON file



== Changelog ==

= 1.10.11 =

* tested against the latest version of WooCommerce

= 1.10.10 =

* tested against the latest version of WooCommerce

= 1.10.9 =

* declare compatibility with WooCommerce High-Performance Order Storage (HPOS)
* tested against latest versions of WordPress and WooCommerce

= 1.10.8 =

* fix issue with preset download

= 1.10.7 =

* adjust the name to WordPress directory requirements
* tested against latest versions of WordPress and WooCommerce
* adjust required PHP version

= 1.10.6 =

* tested against latest versions of WordPress and WooCommerce

= 1.10.5 =

* simplify loading the GTM Presets gallery
* removed missing WooCommerce notification
* removed 'data-cfasync' attribute as it was causing issues in certain scenarios

= 1.10.4 =

* enhanced add_to_cart event to work on dynamic product pages
* upgraded dependencies
* added 'data-cfasync' attribute
* ask for feedback to enhance user support

= 1.10.3 =

* fix picking up the quantity in add_to_cart event

= 1.10.2 =

* fix casting issue PHP 8.0

= 1.10.1 =

* updated documentation
* tested against latest versions of WordPress and WooCommerce

= 1.10.0 =

* add `value` property to all events
* introduce `gtm_ecommerce_woo_event` filter and extra properties
* improve handling product categories
* fix handling of add_to_cart
* test against latest WordPress and WooCommerce

= 1.9.4 =

* remove testing code
* test against latest WooCommerce and WordPress versions
* introduce secured endpoint for obtaining debugging info

= 1.9.3 =

* switch the monitoring API to edge endpoints for performance gains

= 1.9.2 =

* improve add_to_cart JS not to be blocked by other scripts (click vs submit)
* link to the new version of the theme validator

= 1.9.1 =

* show presets versions
* WordPress code styling applied

= 1.9.0 =

* tested with most recent versions of WordPress and WooCommerce
* moved purchase data into top level ecommerce property
* added Tag Concierge Monitoring integration

= 1.8.1 =

* tested with most recent versions of WordPress and WooCommerce

= 1.8.0 =

* fix handling taxes
* introduce filters to customize events and properties
* Theme Validator enhancements

= 1.7.0 =

* move injecting dataLayer down in wp_footer hook to allow loading jQuery in the footer
* safeguard add_to_cart event when non-product is loaded in the page
* tools for verifying tracking: Event Inspector & Theme Validator

= 1.6.0 =

* rework settings with tabs
* renamed the plugin to match PRO version
* presets in a grid for easier discovery and download
* supported events settings

= 1.5.8 =

* remove settings tabs

= 1.5.7 =

* added missing file

= 1.5.6 =

* release correct version

= 1.5.5 =

* fixed critical bug

= 1.5.4 =

* introduce Theme Validator that allows for remote tracking debugging
* improve jQuery callbacks

= 1.5.3 =

* Rely on core WordPress and WooCommerce hooks to cover more themes

= 1.5.2 =

* Update documentation

= 1.5.1 =

* Update documentation
* **Facebook Pixel preset!**
* Remove depratacted UA setting
* Update internal UUID for downloading presets

= 1.5.0 =

* Allow more complex GTM presets, **UA compatible preset available!**
* Fix order items without categories

= 1.4.4 =

* Force initializing dataLayer variable
* Fix products without categories

= 1.4.3 =

* Fix PHP 7.2 compatibility issue
* Fix add_to_cart on single page

= 1.4.2 =

* Fix embedding JS for DOM Ready triggers

= 1.4.1 =

* Fix missing JS file

= 1.4.0 =

* Replace JSON file with external GTM preset generator

= 1.3.1 =

* Fix missing GTM container JSON file

= 1.3.0 =

* Fixed settings sections
* Provide a GTM container to import in a workspace

= 1.2.0 =

* Document possible UA compatibility feature

= 1.1.0 =

* Fix disabling plugin

= 1.0.0 =

* Initial version
