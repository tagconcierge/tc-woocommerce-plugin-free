<?php

namespace GtmEcommerceWoo\Lib\Service;

use GtmEcommerceWoo\Lib\Util\SanitizationUtil;
use GtmEcommerceWoo\Lib\Util\WpSettingsUtil;

/**
 * Logic related to working with settings and options
 */
class SettingsService {
	/** @var WpSettingsUtil */
	protected $wpSettingsUtil;

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

	/** @var string */
	protected $filter = 'basic';

	/** @var array */
	protected $eventsConfig = [];

	public function __construct( WpSettingsUtil $wpSettingsUtil, array $events, array $proEvents, array $serverEvents, string $tagConciergeApiUrl, string $pluginVersion) {
		$this->wpSettingsUtil = $wpSettingsUtil;
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
			'gtm_server',
			'GTM Server-Side'
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
			'<a class="button button-primary" href="https://docs.tagconcierge.com/" target="_blank">Documentation</a><br /><br /><a class="button button-primary" target="_blank" href="https://tagconcierge.com/contact">Contact Support</a><br /><br /><a class="button button-primary" target="_blank" href="https://tagconcierge.com/services">WooCommerce Services</a>',
			'support'
		);

		$this->wpSettingsUtil->addSettingsSection(
			'event_inspector',
			'Event Inspector',
			'Events Inspector provide basic way of confirming that events are being tracked. Depending on the setting below it will show a small window at the bottom of every page with all eCommerce events captured during a given session.',
			'tools'
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
			$this->allowServerTracking ? 'When checked the plugin will send logged client id to dataLayer.' : '<a style="font-size: 0.7em" href="https://go.tagconcierge.com/MSm8e" target="_blank">Upgrade to PRO to track user id.</a>',
			['disabled' => !$this->allowServerTracking, 'title' => $this->allowServerTracking ? '' : 'Upgrade to PRO to use user tracking']
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
			['type'        => 'text', 'placeholder' => 'https://measure.example.com', 'disabled' => !$this->allowServerTracking]
		);


		$this->wpSettingsUtil->addSettingsField(
			'gtm_server_ga4_client_activation_path',
			'GA4 Client Activation Path',
			[$this, 'inputField'],
			'gtm_server_container',
			'GA4 Client Activation path as defined in GTM Client. If you are using our Presets use default value of `/mp`.',
			['type'        => 'text', 'placeholder' => '/mp', 'disabled' => !$this->allowServerTracking]
		);

		$this->wpSettingsUtil->addSettingsField(
			'gtm_server_preview_header',
			'X-Gtm-Server-Preview HTTP header',
			[$this, 'inputField'],
			'gtm_server_container',
			'In order to use GTM Preview feature, paste the HTTP header from GTM Preview tool. The value will change over time.',
			['type'        => 'text', 'placeholder' => 'header value', 'disabled' => !$this->allowServerTracking]
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
		placeholder="<?php echo esc_html( $args['placeholder'] ); ?>"
		name="<?php echo esc_attr( $args['label_for'] ); ?>" />
	  <p class="description">
		<?php echo esc_html( $args['description'] ); ?>
	  </p>
		<?php
	}

	public function optionsPage() {
		$this->wpSettingsUtil->addSubmenuPage(
			'options-general.php',
			$this->allowServerTracking ? 'Google Tag Manager for WooCommerce PRO' : 'Google Tag Manager for WooCommerce FREE',
			'Google Tag Manager',
			'manage_options'
		);
	}


}
