<?php

namespace GtmEcommerceWoo\Lib\Service;

use GtmEcommerceWoo\Lib\Util\SanitizationUtil;
use GtmEcommerceWoo\Lib\Util\WpSettingsUtil;
use WC_Order;

/**
 * Logic related to working with settings and options
 */
class SettingsService {
	/** @var WpSettingsUtil */
	protected $wpSettingsUtil;

	/** @var OrderMonitorService */
	protected $orderMonitorService;

	/** @var array */
	protected $events;

	/** @var array */
	protected $proEvents;

	/** @var array */
	protected $serverEvents;

	/** @var string */
	protected $uuidPrefix = 'gtm-ecommerce-woo-basic';

	/** @var string */
	protected $tagConciergeApiUrl;

	/** @var string */
	protected $pluginVersion;

	/** @var false */
	protected $allowServerTracking = false;

	/** @var false */
	protected $isPro = false;

	/** @var string */
	protected $filter = 'basic';

	/** @var array */
	protected $eventsConfig = [];

	public function __construct( WpSettingsUtil $wpSettingsUtil, OrderMonitorService $orderMonitorService, array $events, array $proEvents, array $serverEvents, string $tagConciergeApiUrl, string $pluginVersion) {
		$this->wpSettingsUtil = $wpSettingsUtil;
		$this->orderMonitorService = $orderMonitorService;
		$this->events = $events;
		$this->proEvents = $proEvents;
		$this->serverEvents = $serverEvents;
		$this->tagConciergeApiUrl = $tagConciergeApiUrl;
		$this->pluginVersion = $pluginVersion;
	}

	public function initialize() {
		$this->wpSettingsUtil->addTab(
			'settings',
			'Settings'
		);

		$this->wpSettingsUtil->addTab(
			'gtm_presets',
			'GTM Presets',
			false
		);

		$this->wpSettingsUtil->addTab(
			'tools',
			'Tools'
		);

		$this->wpSettingsUtil->addTab(
			'consent_mode',
			'Consent Mode'
		);

		$this->wpSettingsUtil->addTab(
			'gtm_server',
			'GTM Server-Side'
		);

		$this->wpSettingsUtil->addTab(
			'gtm_server_presets',
			'GTM Server Presets' . ( $this->isPro ? '' : ' PRO' ),
			false,
			!$this->isPro
		);

		$this->wpSettingsUtil->addTab(
			'monitoring',
			'Monitoring',
			false
		);

		$this->wpSettingsUtil->addTab(
			'support',
			'Support',
			false
		);

		add_action( 'admin_init', [$this, 'settingsInit'] );
		add_action( 'admin_menu', [$this, 'optionsPage'] );
		add_action( 'admin_enqueue_scripts', [$this, 'enqueueScripts'] );
		add_action( 'wp_ajax_gtm_ecommerce_woo_post_preset', [$this, 'ajaxPostPresets'] );
	}

	public function ajaxPostPresets() {
		$sanitizedPreset = esc_url_raw($_GET['preset'] ?? '');

		// bypassing sanitization...
		$preset = str_replace('http://', '', $sanitizedPreset);

		$uuid = $this->wpSettingsUtil->getOption('uuid');
		$presetName = str_replace('presets/', '', $preset) . '.json';
		$args = [
			'body' => json_encode([
				'preset' => $preset,
				'uuid' => $uuid,
				'version' => $this->pluginVersion,
			]),
			'headers' => [
				'content-type' => 'application/json'
			],
			'data_format' => 'body',
		];
		$response = wp_remote_post( $this->tagConciergeApiUrl . '/v2/preset', $args );
		$body     = wp_remote_retrieve_body( $response );
		header('Cache-Control: public');
		header('Content-Description: File Transfer');
		header('Content-Disposition: attachment; filename=' . $presetName);
		header('Content-Transfer-Encoding: binary');
		wp_send_json(json_decode($body));
		wp_die();
	}

	public function enqueueScripts( $hook) {
		if ( 'settings_page_gtm-ecommerce-woo' != $hook ) {
			return;
		}
		wp_enqueue_script( 'wp-pointer' );
		wp_enqueue_style( 'wp-pointer' );
		wp_enqueue_script( 'gtm-ecommerce-woo-admin', plugin_dir_url( __DIR__ . '/../../../' ) . 'assets/admin.js', [], $this->pluginVersion );
		wp_add_inline_script( 'gtm-ecommerce-woo-admin', 'var params = '
		. json_encode([
			'filter' => $this->filter,
			'uuid' => $this->wpSettingsUtil->getOption( 'uuid' )
		]), 'before');
	}

	public function settingsInit() {
		$this->wpSettingsUtil->registerSetting('uuid');


		$uuid = $this->wpSettingsUtil->getOption('uuid');
		if (empty($uuid) || strlen($uuid) === 13) {
			$this->wpSettingsUtil->updateOption('uuid', $this->uuidPrefix . '_' . bin2hex(random_bytes(20)));
		}

		// if we have different uuidPrefix then we upgrade uuid
		if (substr($uuid, 0, -41) !== $this->uuidPrefix) {
			$previousUuids = is_array($this->wpSettingsUtil->getOption('previous_uuids')) ?
				$this->wpSettingsUtil->getOption('previous_uuids')
				: [];
			$previousUuids[] = $uuid;
			$this->wpSettingsUtil->updateOption('previous_uuids', $previousUuids);
			$this->wpSettingsUtil->updateOption('uuid', $this->uuidPrefix . '_' . bin2hex(random_bytes(20)));
		}

		$this->wpSettingsUtil->addSettingsSection(
			'basic',
			'Basic Settings',
			'This plugin push eCommerce events from WooCommerce shop to Google Tag Manager instance. After enabling, add tags and triggers to your GTM container in order to use and analyze captured data. For quick start use one of the GTM presets available below.',
			'settings'
		);

		$this->wpSettingsUtil->addSettingsSection(
			'gtm_snippet',
			'Google Tag Manager snippet',
			'Paste two snippets provided by GTM. To find those snippets navigate to `Admin` tab in GTM console and click `Install Google Tag Manager`. If you already implemented GTM snippets in your page, paste them below, but select appropriate `Prevent loading GTM Snippet` option.',
			'settings'
		);

		$this->wpSettingsUtil->addSettingsSection(
			'events',
			'Events (Web)',
			'Select which web events should be tracked:',
			'settings'
		);

		$this->wpSettingsUtil->addSettingsSection(
			'gtm_server_container',
			'GTM Server Container',
			'Specify details of your GTM Server-Side container to enable Server Side Tracking. This features requires storing `client_id` parameter in details of WooCommerce order to link web and server events. Ensure that your privacy policy supports this.',
			'gtm_server'
		);

		$this->wpSettingsUtil->addSettingsSection(
			'events_server',
			'Events/Webhooks',
			'Select which server-side events should be tracked server-to-server. Ensure your GTM web and server containers are configured correctly if you are tracking the same event (purchase) both from the browser and server. If you are using our presets this is already covered:',
			'gtm_server'
		);

		$this->wpSettingsUtil->addSettingsSection(
			'gtm_container_jsons',
			'Google Tag Manager presets',
			'It\'s time to define what to do with tracked eCommerce events. We know that settings up GTM workspace may be cumbersome. That\'s why the plugin comes with a set of presets you can import to your GTM workspace to create all required Tags, Triggers and Variables. Select a preset in dropdown below, download the JSON file and import it in Admin panel in your GTM workspace, see plugin <a href="https://docs.tagconcierge.com/" target="_blank">Documentation</a> for details):<br /><br />
				<div id="gtm-ecommerce-woo-presets-loader" style="text-align: center;"><span class="spinner is-active" style="float: none;"></span></div><div class="metabox-holder"><div id="gtm-ecommerce-woo-presets-grid" class="postbox-container" style="float: none;"><div id="gtm-ecommerce-woo-preset-tmpl" style="display: none;"><div style="display: inline-block;
	margin-left: 4%; width: 45%" class="postbox"><h3 class="name">Google Analytics 4</h3><div class="inside"><p class="description">Description</p><p><b>Supported events:</b> <span class="events-count">2</span> <span class="events-list dashicons dashicons-info-outline" style="cursor: pointer;"></span></p><p><a class="download button button-primary" href="#">Download</a><a class="documentation button" style="margin-left: 5px; display: none;" target="_blank" href="#">Documentation</a></p><p>Version: <span class="version">N/A</span></p></div></div></div></div></div><br /><div id="gtm-ecommerce-woo-presets-upgrade" style="text-align: center; display: none;"><a class="button button-primary" href="https://go.tagconcierge.com/MSm8e" target="_blank">Upgrade to PRO</a></div>',
			'gtm_presets'
		);

		$this->wpSettingsUtil->addSettingsSection(
			'support',
			'Support',
			'<a class="button button-primary" href="https://docs.tagconcierge.com/" target="_blank">Documentation</a><br /><br /><a class="button button-primary" target="_blank" href="https://tagconcierge.com/contact">Contact Support</a><br /><br /><a class="button button-primary" target="_blank" href="https://tagconcierge.com/services">Custom Services</a>',
			'support'
		);

		$this->wpSettingsUtil->addSettingsSection(
			'event_inspector',
			'Event Inspector',
			'Events Inspector provide basic way of confirming that events are being tracked. Depending on the setting below it will show a small window at the bottom of every page with all eCommerce events captured during a given session.',
			'tools',
			[ 'grid' => 'start' ]
		);

		$this->wpSettingsUtil->addSettingsSection(
			'cart_data_gads',
			'Google Ads Cart Data',
			'Pass additional Cart Data parameters to your Google Ads Conversions. This allows to use Dynamic Remarketing campaigns or track conversion value based on COGS information provided in your Google Merchant Center Account. <a href="https://support.google.com/google-ads/answer/9028254?hl=en" target="_blank">Learn more</a>.',
			'tools',
			[ 'grid' => 'item', 'badge' =>  $this->isPro ? null : 'PRO' ]
		);

		$this->wpSettingsUtil->addSettingsSection(
			'product_feed_google',
			'Google Product Feed',
			'Generates a public CSV file with all product data (published and visible products and their variants) that can be loaded to Google Merchant Center to populate product catalog. The file URL will be available shortly after enabling.<br /><br />URL:' . ( $this->wpSettingsUtil->getOption('product_feed_google_file_url') ? '<input style="margin-left: 10px;width: 85%;" type="text" value="' . $this->wpSettingsUtil->getOption('product_feed_google_file_url') . '" />' : ' Pending' ),
			'tools',
			[ 'grid' => 'end' ]
		);

		$this->wpSettingsUtil->addSettingsSection(
			'consent_mode_default',
			'Default Consent Mode',
			'Correct Google Consent Mode v2 requires setting default consent state before GTM installation snippet. No all Consent Management Platforms can achieve that, use the settings below to get it loaded before gtm.js.',
			'consent_mode',
			[ 'grid' => 'start' ]
		);

		$this->wpSettingsUtil->addSettingsSection(
			'consent_mode_integration_complianz',
			'Complianz Integration',
			'Integrated Complianz Cookies Banner and Consent Management Platform with GTM Consent Mode v2.',
			'consent_mode',
			[ 'grid' => 'item', 'badge' =>  $this->isPro ? null : 'PRO' ]
		);



		$this->wpSettingsUtil->addSettingsSection(
			'event_deferral',
			'Event Deferral',
			'Defer events pushed to data layer until "DOM ready" event. Useful when using asynchronous Consent Management Platform to ensure consent state is provided before firing any events.',
			'consent_mode',
			[ 'grid' => 'end', 'badge' =>  $this->isPro ? null : 'PRO' ]
		);

		$this->wpSettingsUtil->addSettingsSection(
			'server_side_gtmjs',
			'Load gtm.js from sGTM',
			'Modify the gtm.js installation snippet to be loaded from 1st party domain of your sGTM container.',
			'gtm_server',
			[ 'grid' => 'start' ]
		);

		$this->wpSettingsUtil->addSettingsSection(
			'server_side_webhooks',
			'Server-to-server Webhook',
			'Send selected events server-to-server to improve tracking coverage.',
			'gtm_server',
			[ 'grid' => 'item', 'badge' =>  $this->isPro ? null : 'PRO' ]
		);

		$this->wpSettingsUtil->addSettingsSection(
			'cookies_storage',
			'Cookies Storage',
			'When using server-side webhooks having access to ad cookies is required.',
			'gtm_server',
			[ 'grid' => 'item', 'badge' =>  $this->isPro ? null : 'PRO' ]
		);

		$this->wpSettingsUtil->addSettingsSection(
			'server_side_endpoint_poas',
			'sGTM POAS Endpoint',
			'When using server-side GTM you can make additional transformation before data is sent to the 3rd party platform. This endpoint allows replacing default revenue based conversion values with profit information stored in WooCommerce using COGS plugin.',
			'gtm_server',
			[ 'grid' => 'end', 'badge' =>  $this->isPro ? null : 'PRO' ]
		);

		$statistics = null;
		$description = 'The report below shows number of transactions in the past 7 days affected by different tracking issues. Learn more about each issue in the <a href="https://docs.tagpilot.io/article/76-conversion-tracking-monitoring" target="_blank">documentation</a>';
		if ($this->wpSettingsUtil->isTab('monitoring')) {
			$statistics = $this->orderMonitorService->getStatistics();
		}

		if ($statistics) {

			$total = $statistics->getTotal();

			$stats = [
				'total' => $total,
				'tracked' => $statistics->getTracked($total['count']),
				'tracked_with_warnings' => $statistics->getTrackedWithWarnings($total['count']),
				'not_tracked' => $statistics->getNotTracked($total['count']),
				'blocked' => $statistics->getBlocked($total['count']),
				'analytics_denied' => $statistics->getAnalyticsDenied($total['count']),
				'ad_denied' => $statistics->getAdDenied($total['count']),
				'no_thank_you_page' => $statistics->getNoThankYouPage($total['count'])
			];
			$description .= sprintf('<br /><br />
					<div class="metabox-holder"><div class="postbox-container" style="float: none; display: flex; flex-wrap:wrap;"><div style="margin-left: 3%%; width: 30%%" class="postbox"><div class="inside"><h3>Total</h3><p>Total trackable transactions. Orders are trackable when monitoring is enabled and they were placed directly on the page, not via API or other integrations.<br /></p>transactions: %d<br />value: %.2f</div></div><div style="margin-left: 3%%; width: 30%%" class="postbox"><div class="inside"><h3>Tracked with warnings</h3><p>Events were correctly generated for Google Tag Manager but we adblock was detected or user privacy consent was denied.</p>transactions: %d (%d%%)<br />value: %.2f</div></div><div style="margin-left: 3%%; width: 30%%" class="postbox"><div class="inside"><h3>Not tracked</h3><p>Events weren\'t correctly tracked by Google Tag Manager. This indicates technical issue that prevents being generated.</p>transactions: %d (%d%%)<br />value: %.2f</div></div> </div></div><br />',
				$stats['total']['count'],
				$stats['total']['value'],
				$stats['tracked_with_warnings']['count'],
				$stats['tracked_with_warnings']['count_percentage'],
				$stats['tracked_with_warnings']['value'],
				$stats['not_tracked']['count'],
				$stats['not_tracked']['count_percentage'],
				$stats['not_tracked']['value']
			);

			$description .= sprintf('<br /><br />
					<div class="metabox-holder"><div class="postbox-container" style="float: none; display: flex; flex-wrap:wrap;"><div style="margin-left: 3%%; width: 21.75%%" class="postbox"><div class="inside"><h3>Blocked</h3><p>Transactions affected by browsers and ad blocking extensions.</p>transactions: %d (%d%%)<br />value: %.2f</div></div><div style="margin-left: 3%%; width: 21.75%%" class="postbox"><div class="inside"><h3>Analytics Denied</h3><p>Transactions with analytical cookies denied by the user. Those orders won\'t show up in the GA4 reporting.</p>transactions: %d (%d%%)<br />value: %.2f</div></div><div style="margin-left: 3%%; width: 21.75%%" class="postbox"><div class="inside"><h3>Ad Denied</h3><p>Transactions with ad cookies denied by the user. Those orders won\'t show up in Google Ads reporting.</p>transactions: %d (%d%%)<br />value: %.2f</div></div><div style="margin-left: 3%%; width: 21.75%%" class="postbox"><div class="inside"><h3>No `thank you` page visit</h3><p>Orders where customers didn\'t reach the order confirmation page (thank you page), where fallback tracking takes place.<br /></p>transactions: %d (%d%%)<br />value: %.2f</div></div></div></div><br />',
				$stats['blocked']['count'],
				$stats['blocked']['count_percentage'],
				$stats['blocked']['value'],
				$stats['analytics_denied']['count'],
				$stats['analytics_denied']['count_percentage'],
				$stats['analytics_denied']['value'],
				$stats['ad_denied']['count'],
				$stats['ad_denied']['count_percentage'],
				$stats['ad_denied']['value'],
				$stats['no_thank_you_page']['count'],
				$stats['no_thank_you_page']['count_percentage'],
				$stats['no_thank_you_page']['value']
			);
		}

		$this->wpSettingsUtil->addSettingsSection(
			'monitoring',
			'Tracking Monitoring',
			$description,
			'monitoring'
		);

		$this->wpSettingsUtil->addSettingsField(
			'disabled',
			'Disable?',
			[$this, 'checkboxField'],
			'basic',
			'When checked the plugin won\'t load anything in the page.'
		);

		$this->wpSettingsUtil->addSettingsField(
			'track_user_id',
			'Track user id?',
			[$this, 'checkboxField'],
			'basic',
			$this->isPro ? 'When checked the plugin will send logged client id to dataLayer.' : '<a style="font-size: 0.7em" href="https://go.tagconcierge.com/MSm8e" target="_blank">Upgrade to PRO to track user id.</a>',
			['disabled' => !$this->isPro, 'title' => $this->isPro ? '' : 'Upgrade to PRO to use user tracking']
		);

		$this->wpSettingsUtil->addSettingsField(
			'monitor_disabled',
			'Disable monitor?',
			[$this, 'checkboxField'],
			'basic',
			'When checked the plugin won\'t diagnose orders context like information about adblock or denied user consents. Learn more in <a href="https://docs.tagpilot.io/article/76-conversion-tracking-monitoring" target="_blank">documentation</a>.'
		);

		$this->wpSettingsUtil->addSettingsField(
			'event_inspector_enabled',
			'Enable Event Inspector?',
			[$this, 'selectField'],
			'event_inspector',
			'Decide if and how to enable the Event Inspector. When querystring option is selected "gtm-inspector=1" needs to be added to url to show Inspector.',
			[
				'options' => [
					'no' => 'Disabled',
					'yes-querystring' => 'Enabled, with querystring',
					'yes-admin' => 'Enabled, for admins',
					'yes-demo' => 'Enabled, for everybody - DEMO MODE',
				]
			]
		);

		$this->wpSettingsUtil->addSettingsField(
			'defer_events',
			'Defer events?',
			[$this, 'checkboxField'],
			'event_deferral',
			'When clicked Ecommerce events will be pushed to DataLayer after DOM Ready event.',
			['disabled' => !$this->isPro, 'title' => 'Upgrade to PRO version above.']
		);

		$this->wpSettingsUtil->addSettingsField(
			'defer_events_event_name',
			'Event name to wait for',
			[$this, 'inputField'],
			'event_deferral',
			'When specified all eCommerce events will be pushed to dataLayer after the event. Defaults to DOM ready.',
			['disabled' => !$this->isPro, 'title' => 'Upgrade to PRO version above.', 'placeholder' => 'gtm.load', 'type' => 'text']
		);

		$this->wpSettingsUtil->addSettingsField(
			'defer_events_timeout',
			'Deferral timeout',
			[$this, 'inputField'],
			'event_deferral',
			'Number of milliseconds after which defered eCommerce events will be pushed',
			['disabled' => !$this->isPro, 'title' => 'Upgrade to PRO version above.', 'type' => 'number']
		);

		$this->wpSettingsUtil->addSettingsField(
			'consent_mode_default_enabled',
			'Enable',
			[$this, 'checkboxField'],
			'consent_mode_default',
			'When clicked default consent state will be loaded before gtm.js'
		);

		$this->wpSettingsUtil->addSettingsField(
			'consent_mode_default_ad_storage',
			'ad_storage',
			[$this, 'selectField'],
			'consent_mode_default',
			'Default state for `ad_storage` consent type.',
			[
				'options' => [
					'denied' => 'denied',
					'granted' => 'granted'
				]
			]
		);

		$this->wpSettingsUtil->addSettingsField(
			'consent_mode_default_ad_user_data',
			'ad_user_data',
			[$this, 'selectField'],
			'consent_mode_default',
			'Default state for `ad_user_data` consent type.',
			[
				'options' => [
					'denied' => 'denied',
					'granted' => 'granted'
				]
			]
		);

		$this->wpSettingsUtil->addSettingsField(
			'consent_mode_default_ad_personalization',
			'ad_personalization',
			[$this, 'selectField'],
			'consent_mode_default',
			'Default state for `ad_personalization` consent type.',
			[
				'options' => [
					'denied' => 'denied',
					'granted' => 'granted'
				]
			]
		);

		$this->wpSettingsUtil->addSettingsField(
			'consent_mode_default_analytics_storage',
			'analytics_storage',
			[$this, 'selectField'],
			'consent_mode_default',
			'Default state for `analytics_storage` consent type.',
			[
				'options' => [
					'denied' => 'denied',
					'granted' => 'granted'
				]
			]
		);

		$this->wpSettingsUtil->addSettingsField(
			'consent_mode_default_wait_for_update',
			'wait_for_update',
			[$this, 'inputField'],
			'consent_mode_default',
			'Number of ms to wait for asynchronous Consent Management Platform (banner) to load, e.g. 500',
			[
				'type' => 'number'
			]
		);

		$this->wpSettingsUtil->addSettingsField(
			'consent_mode_default_region',
			'region',
			[$this, 'inputField'],
			'consent_mode_default',
			'Comma separated list of country codes, e.g. `US, FR, IT`.',
			[
				'type' => 'text'
			]
		);

		$this->wpSettingsUtil->addSettingsField(
			'consent_mode_default_url_passthrough',
			'url_passthrough',
			[$this, 'checkboxField'],
			'consent_mode_default',
			'Pass through ad click, client ID, and session ID information in URLs'
		);

		$this->wpSettingsUtil->addSettingsField(
			'consent_mode_default_ads_data_redaction',
			'ads_data_redaction',
			[$this, 'checkboxField'],
			'consent_mode_default',
			'Redact ads data'
		);

		$this->wpSettingsUtil->addSettingsField(
			'consent_mode_integration_complianz_enabled',
			'Enable',
			[$this, 'checkboxField'],
			'consent_mode_integration_complianz',
			'When clicked consent mode update will be generated based on Complianz JS API.'
		);


		$this->wpSettingsUtil->addSettingsField(
			'google_business_vertical',
			'Google Business Vertical',
			[$this, 'selectField'],
			'cart_data_gads',
			'Select required parameter <a href="https://support.google.com/google-ads/answer/7305793?hl=en" target="_blank">learn more here</a>.',
			[
				'options' => [
					'no' => 'None',
					'retail' => 'Retail',
					'education' => 'Education',
					'hotel_rental' => 'Hotels and rentals',
					'jobs' => 'Jobs',
					'local' => 'Local deals',
					'real_estate' => 'Real estate',
					'custom' => 'Custom'
				],
				'disabled' => !$this->isPro,
			]
		);

		$this->wpSettingsUtil->addSettingsField(
			'dynamic_remarketing_google_id_pattern',
			'Item ID Pattern',
			[$this, 'inputField'],
			'cart_data_gads',
			'Product ID pattern to match your product feed in Google Merchant Center, e.g. `woocommerce_gpf_{{sku}}`. Use {{sku}} or {{id}} and any prefix or suffix.',
			[
				'type' => 'text',
				'disabled' => !$this->isPro
			]
		);

		$this->wpSettingsUtil->addSettingsField(
			'cart_data_google_merchant_id',
			'AW Merchant ID',
			[$this, 'inputField'],
			'cart_data_gads',
			'The Merchant Center ID. Provide this parameter if you submit an item in several Merchant Center accounts and you want to control from which Merchant Center the item’s data, for example, its COGS, should be read.',
			[
				'type' => 'text',
				'disabled' => !$this->isPro
			]
		);

		$this->wpSettingsUtil->addSettingsField(
			'cart_data_google_feed_country',
			'AW Feed Country',
			[$this, 'inputField'],
			'cart_data_gads',
			'The country code of the product feed to use with the integration, e.g. `US`.',
			[
				'type' => 'text',
				'disabled' => !$this->isPro
			]
		);

		$this->wpSettingsUtil->addSettingsField(
			'cart_data_google_feed_lanuage',
			'AW Feed Language',
			[$this, 'inputField'],
			'cart_data_gads',
			'The lanugage code of the product feed to use with the integration, e.g. `EN`.',
			[
				'type' => 'text',
				'disabled' => !$this->isPro
			]
		);

		$this->wpSettingsUtil->addSettingsField(
			'server_side_endpoint_poas_enabled',
			'Enable POAS Endpoint',
			[$this, 'checkboxField'],
			'server_side_endpoint_poas',
			'Enable serving Profit (POAS) information for sGTM container.',
			[
				'disabled' => !$this->isPro
			]
		);

		$this->wpSettingsUtil->addSettingsField(
			'product_feed_google_enabled',
			'Enable Product Feed',
			[$this, 'checkboxField'],
			'product_feed_google',
			'When enabled the public file will be created and updated every day.'
		);

		$this->wpSettingsUtil->addSettingsField(
			'product_feed_google_id_pattern',
			'Product ID Pattern',
			[$this, 'inputField'],
			'product_feed_google',
			'How Product ID should be generated. Use {{sku}} or {{id}} and any prefix/suffix.',
			[
				'type' => 'text'
			]
		);

		$this->wpSettingsUtil->addSettingsField(
			'gtm_snippet_prevent_load',
			'Prevent loading GTM Snippet?',
			[$this, 'selectField'],
			'gtm_snippet',
			'Select if GTM snippet is already implemented in your store or if the plugin should inject snippets provided below.',
			[
				'options' => [
					'no' => 'No, use the GTM Ecommerce snippets below',
					'yes-consent' => 'Yes, I use a consent plugin',
					'yes-theme' => 'Yes, GTM is implemented directly in the theme',
					'yes-other' => 'Yes, I inject GTM snippets differently'
				]
			]
		);

		$this->wpSettingsUtil->addSettingsField(
			'gtm_snippet_head',
			'GTM Snippet Head',
			[$this, 'textareaField'],
			'gtm_snippet',
			'Paste the first snippet provided by GTM. It will be loaded in the <head> of the page.',
			['rows'        => 9]
		);


		$this->wpSettingsUtil->addSettingsField(
			'gtm_snippet_body',
			'GTM Snippet body',
			[$this, 'textareaField'],
			'gtm_snippet',
			'Paste the second snippet provided by GTM. It will be load after opening <body> tag.',
			['rows'        => 6]
		);

		$this->wpSettingsUtil->addSettingsField(
			'gtm_server_container_url',
			'GTM Server Container URL',
			[$this, 'inputField'],
			'gtm_server_container',
			'The full url of you GTM Server Container.',
			['type'        => 'text', 'placeholder' => 'https://measure.example.com']
		);


		$this->wpSettingsUtil->addSettingsField(
			'gtm_server_ga4_client_activation_path',
			'GA4 Client Activation Path',
			[$this, 'inputField'],
			'server_side_webhooks',
			'GA4 Client Activation path as defined in GTM Client. If you are using our Presets use default value of `/mp`.',
			['type'        => 'text', 'placeholder' => '/mp', 'disabled' => !$this->allowServerTracking]
		);

		$this->wpSettingsUtil->addSettingsField(
			'gtm_server_preview_header',
			'X-Gtm-Server-Preview HTTP header',
			[$this, 'inputField'],
			'server_side_webhooks',
			'In order to use GTM Preview feature, paste the HTTP header from GTM Preview tool. The value will change over time.',
			['type'        => 'text', 'placeholder' => 'header value', 'disabled' => !$this->allowServerTracking]
		);

		$this->wpSettingsUtil->addSettingsField(
			'server_side_gtmjs_enable',
			'Enable',
			[$this, 'checkboxField'],
			'server_side_gtmjs',
			'Will change public GTM domain to sGTM custom domain provided in the settings above.'
		);

		$this->wpSettingsUtil->addSettingsField(
			'cookies_storage',
			'Cookies Storage',
			[$this, 'checkboxField'],
			'cookies_storage',
			'In order to connect web events with server events identifiers from cookies can be stored on the server and added to server events.',
			['disabled' => !$this->isPro, 'title' => 'Checking will start storing cookies']
		);

		foreach ($this->events as $eventName) {
			$this->wpSettingsUtil->addSettingsField(
				'event_' . $eventName,
				$eventName,
				[$this, 'checkboxField'],
				'events',
				isset($this->eventsConfig[$eventName]['description']) ? $this->eventsConfig[$eventName]['description'] : ''
			);
			if ($this->wpSettingsUtil->getOption('event_' . $eventName) === false) {
				$this->wpSettingsUtil->updateOption('event_' . $eventName, isset($this->eventsConfig[$eventName]['default_disabled']) ? 0 : 1);
			}
		}

		foreach ($this->proEvents as $eventName) {
			$this->wpSettingsUtil->addSettingsField(
				'event_' . $eventName,
				$eventName,
				[$this, 'checkboxField'],
				'events',
				'<a style="font-size: 0.7em" href="https://go.tagconcierge.com/MSm8e" target="_blank">Upgrade to PRO</a>',
				['disabled' => true, 'title' => 'Upgrade to PRO version above.']
			);
		}

		foreach ($this->serverEvents as $eventName) {
			$this->wpSettingsUtil->addSettingsField(
				'event_server_' . $eventName,
				$eventName,
				[$this, 'checkboxField'],
				'events_server',
				$this->allowServerTracking ? '' : '<a style="font-size: 0.7em" href="https://go.tagconcierge.com/MSm8e" target="_blank">Upgrade to PRO</a>',
				['disabled' => !$this->allowServerTracking, 'title' => $this->allowServerTracking ? '' : 'Upgrade to PRO to use the beta of server-side tracking']
			);
		}

		if ($this->wpSettingsUtil->getOption('product_feed_google_id_pattern') === false) {
			$this->wpSettingsUtil->updateOption('product_feed_google_id_pattern', '{{sku}}');
		}
	}

	public function checkboxField( $args ) {
		// Get the value of the setting we've registered with register_setting()
		$value = get_option( $args['label_for'] );
		?>
	  <input
		type="checkbox"
		id="<?php echo esc_attr( $args['label_for'] ); ?>"
		name="<?php echo esc_attr( $args['label_for'] ); ?>"
		<?php if (true === @$args['disabled']) : ?>
		disabled="disabled"
		<?php endif; ?>
		<?php if (@$args['title']) : ?>
		title="<?php echo esc_attr($args['title']); ?>"
		<?php endif; ?>
		value="1"
		<?php checked( $value, 1 ); ?> />
	  <p class="description">
		<?php echo wp_kses($args['description'], SanitizationUtil::WP_KSES_ALLOWED_HTML, SanitizationUtil::WP_KSES_ALLOWED_PROTOCOLS); ?>
	  </p>
		<?php
	}

	public function selectField( $args ) {
		// Get the value of the setting we've registered with register_setting()
		$selectedValue = get_option( $args['label_for'] );
		?>
	  <select
		type="checkbox"
		id="<?php echo esc_attr( $args['label_for'] ); ?>"
		name="<?php echo esc_attr( $args['label_for'] ); ?>"
		<?php if (true === @$args['disabled']) : ?>
		disabled="disabled"
		<?php endif; ?>
		>
		<?php foreach ($args['options'] as $value => $label) : ?>
			<option value="<?php echo esc_attr($value); ?>"
				<?php if ($selectedValue == $value) : ?>
				selected
				<?php endif; ?>
				><?php echo esc_html($label); ?></option>
		<?php endforeach ?>
		</select>
	  <p class="description">
		<?php echo wp_kses($args['description'], SanitizationUtil::WP_KSES_ALLOWED_HTML, SanitizationUtil::WP_KSES_ALLOWED_PROTOCOLS); ?>
	  </p>
		<?php
	}


	public function textareaField( $args ) {
		// Get the value of the setting we've registered with register_setting()
		$value = get_option( $args['label_for'] );
		?>
	  <textarea
		id="<?php echo esc_attr( $args['label_for'] ); ?>"
		class="large-text code"
		rows="<?php echo esc_html( $args['rows'] ); ?>"
		name="<?php echo esc_attr( $args['label_for'] ); ?>"><?php echo wp_kses($value, SanitizationUtil::WP_KSES_ALLOWED_HTML, SanitizationUtil::WP_KSES_ALLOWED_PROTOCOLS); ?></textarea>
	  <p class="description">
		<?php echo esc_html( $args['description'] ); ?>
	  </p>
		<?php
	}

	public function inputField( $args ) {
		// Get the value of the setting we've registered with register_setting()
		$value = get_option( $args['label_for'] );
		?>
	  <input
		id="<?php echo esc_attr( $args['label_for'] ); ?>"
		class="large-text code"
		type="<?php echo esc_html( $args['type'] ); ?>"
		<?php if (true === @$args['disabled']) : ?>
		disabled="disabled"
		<?php endif; ?>
		value="<?php echo esc_html($value); ?>"
		placeholder="<?php echo esc_html( @$args['placeholder'] ); ?>"
		name="<?php echo esc_attr( $args['label_for'] ); ?>" />
	  <p class="description">
		<?php echo esc_html( $args['description'] ); ?>
	  </p>
		<?php
	}

	public function optionsPage() {
		$this->wpSettingsUtil->addSubmenuPage(
			'options-general.php',
			$this->isPro ? 'Google Tag Manager for WooCommerce PRO' : 'Tag Pilot - Google Tag Manager for WooCommerce FREE',
			'Google Tag Manager',
			'manage_options'
		);
	}


}
