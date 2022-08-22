<?php

namespace GtmEcommerceWoo\Lib\Service;

/**
 * Logic related to working with settings and options
 */
class SettingsService {

	public function __construct( $wpSettingsUtil, $events, $proEvents, $serverEvents, $tagConciergeApiUrl, $pluginVersion) {
		$this->wpSettingsUtil = $wpSettingsUtil;
		$this->events = $events;
		$this->proEvents = $proEvents;
		$this->serverEvents = $serverEvents;
		$this->uuidPrefix = 'gtm-ecommerce-woo-basic';
		$this->tagConciergeApiUrl = $tagConciergeApiUrl;
		$this->tagConciergeMonitorPreset = 'presets/tag-concierge-monitor-basic';
		$this->pluginVersion = $pluginVersion;
		$this->allowServerTracking = false;
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
			'GTM Server-side <pre style="display: inline; text-transform: uppercase;">beta</pre>'
		);

		$this->wpSettingsUtil->addTab(
			'tag_concierge',
			'Tag Concierge <pre style="display: inline; text-transform: uppercase;">beta</pre>'
		);

		$this->wpSettingsUtil->addTab(
			'support',
			'Support',
			false
		);

		add_action( 'admin_init', [$this, 'settingsInit'] );
		add_action( 'admin_menu', [$this, 'optionsPage'] );
		add_action( 'admin_enqueue_scripts', [$this, 'enqueueScripts'] );
		add_action( 'wp_ajax_gtm_ecommerce_woo_get_presets', [$this, 'ajaxGetPresets'] );
		add_action( 'wp_ajax_gtm_ecommerce_woo_post_preset', [$this, 'ajaxPostPresets'] );
	}

	public function ajaxGetPresets() {
		$uuid = $this->wpSettingsUtil->getOption('uuid');
		$response = wp_remote_get( $this->tagConciergeApiUrl . '/v2/presets?uuid=' . $uuid );
		$body     = wp_remote_retrieve_body( $response );
		wp_send_json(json_decode($body));
		wp_die();
	}

	public function ajaxPostPresets() {
		$uuid = $this->wpSettingsUtil->getOption('uuid');
		$disabled = $this->wpSettingsUtil->getOption('disabled');
		$gtmSnippetHead = $this->wpSettingsUtil->getOption('gtm_snippet_head');
		$gtmSnippetBody = $this->wpSettingsUtil->getOption('gtm_snippet_body');
		$presetName = str_replace('presets/', '', $_GET['preset']) . '.json';
		$args = [
			'body' => json_encode([
				'preset' => $_GET['preset'],
				'uuid' => $uuid,
				'version' => $this->pluginVersion,
				'disabled' => $disabled,
				'gtm_snippet_head' => sha1($gtmSnippetHead),
				'gtm_snippet_body' => sha1($gtmSnippetBody)
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
		wp_enqueue_script( 'gtm-ecommerce-woo-admin', plugin_dir_url( __DIR__ . '/../../../' ) . 'js/admin.js', [], $this->pluginVersion );
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

		if ($this->wpSettingsUtil->getOption('theme_validator_enabled') === false) {
			$this->wpSettingsUtil->updateOption('theme_validator_enabled', 1);
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
			'Specify details of your GTM Server-side container to enable Server Side Tracking. This is a `BETA` feature and currently only purchase event is available. When enabling a server-side tracking for an event disable a web based event to avoid duplicates. This features requires storing `client_id` parameter in details of WooCommerce order to link web and server events. Ensure that your privacy policy and GTM server container supports this.',
			'gtm_server'
		);

		$this->wpSettingsUtil->addSettingsSection(
			'events_server',
			'Events (Server)',
			'Select which server-side events should be tracked (disable the same web based event in the main settings to avoid duplicates):',
			'gtm_server'
		);

		$this->wpSettingsUtil->addSettingsSection(
			'tag_concierge',
			'Tag Concierge',
			'Want to learn more? <a href="https://tagconcierge.com/platform/" target="_blank">See overview here</a>',
			'tag_concierge'
		);

		$this->wpSettingsUtil->addSettingsSection(
			'gtm_container_jsons',
			'Google Tag Manager presets',
			'It\'s time to define what to do with tracked eCommerce events. We know that settings up GTM workspace may be cumbersome. That\'s why the plugin comes with a set of presets you can import to your GTM workspace to create all required Tags, Triggers and Variables. Select a preset in dropdown below, download the JSON file and import it in Admin panel in your GTM workspace, see plugin <a href="https://docs.tagconcierge.com/" target="_blank">Documentation</a> for details):<br /><br />
				<div id="gtm-ecommerce-woo-presets-loader" style="text-align: center;"><span class="spinner is-active" style="float: none;"></span></div><div class="metabox-holder"><div id="gtm-ecommerce-woo-presets-grid" class="postbox-container" style="float: none;"><div id="gtm-ecommerce-woo-preset-tmpl" style="display: none;"><div style="display: inline-block;
    margin-left: 4%; width: 45%" class="postbox"><h3 class="name">Google Analytics 4</h3><div class="inside"><p class="description">Description</p><p><b>Supported events:</b> <span class="events-count">2</span> <span class="events-list dashicons dashicons-info-outline" style="cursor: pointer;"></span></p><p><a class="download button button-primary" href="#">Download</a></p><p>Version: <span class="version">N/A</span></p></div></div></div></div></div><br /><div id="gtm-ecommerce-woo-presets-upgrade" style="text-align: center; display: none;"><a class="button button-primary" href="https://go.tagconcierge.com/MSm8e" target="_blank">Upgrade to PRO</a></div>',
			'gtm_presets'
		);

		$this->wpSettingsUtil->addSettingsSection(
			'support',
			'Support',
			'<a class="button button-primary" href="https://docs.tagconcierge.com/" target="_blank">Documentation</a><br /><br /><a class="button button-primary" target="_blank" href="https://tagconcierge.com/contact">Contact Support</a>',
			'support'
		);

		$this->wpSettingsUtil->addSettingsSection(
			'event_inspector',
			'Event Inspector',
			'Events Inspector provide basic way of confirming that events are being tracked. Depending on the setting below it will show a small window at the bottom of every page with all eCommerce events captured during a given session.',
			'tools'
		);

		$this->wpSettingsUtil->addSettingsSection(
			'theme_validator',
			'Theme Validator',
			'Theme Validator allows to assess if all events supported by this plugin can be tracked on your current theme: <strong>' . ( wp_get_theme() )->get('Name') . '</strong>. Your WordPress site must be publicly available to perform this test. Clicking the button below will send URL of this WordPress site to our servers to perform a remote static analysis. It will ensure all WordPress/WooCommerce internal hooks/actions and correct HTML elements are present in order to track all supported events. It cannot detect issues with dynamic scripts and elements, for full testing the Event Inspector available above can be used. It is mostly automated service, but processing times can get up to few hours. <br />
			<div style="text-align: center" id="gtm-ecommerce-woo-validator-section"><button id="gtm-ecommerce-woo-theme-validator" class="button">Request Theme Validation</button></div>
			<div style="text-align: center; display: none" id="gtm-ecommerce-woo-validator-sent">Your Theme Validation request was sent, please check link below if results are ready.</div><br /><div style="text-align: center;"><a href="https://app.tagconcierge.com/theme-validator?uuid=' . $uuid . '" target="_blank">See results in Tag Concierge</a></div>',
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
			'theme_validator_enabled',
			'Enable Theme Validator?',
			[$this, 'checkboxField'],
			'theme_validator',
			'Allow the plugin and the support team to validate theme by issuing a special HTTP request. Provide them with following information: `uuid_hash:'
			. md5($this->wpSettingsUtil->getOption('uuid')) . '`.'
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
			'monitor_enabled',
			'Enable Tag Concierge Monitor?',
			[$this, 'checkboxField'],
			'tag_concierge',
			'Enable sending the eCommerce events to Tag Concierge Monitor for active tracking monitoring. <br />Make sure that you have downloaded and installed <a class="download" href="#" data-id="' . $this->tagConciergeMonitorPreset . '">Monitoring GTM preset</a> too.<br />Then <a href="https://app.tagconcierge.com/?uuid=' . $this->wpSettingsUtil->getOption('uuid') . '" target="_blank">Open Tag Concierge App</a>'
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
			'GA4 Client Activation path as defined in GTM Client',
			['type'        => 'text', 'placeholder' => '/mp', 'disabled' => !$this->allowServerTracking]
		);

		foreach ($this->events as $eventName) {
			$this->wpSettingsUtil->addSettingsField(
				'event_' . $eventName,
				$eventName,
				[$this, 'checkboxField'],
				'events'
			);
			if ($this->wpSettingsUtil->getOption('event_' . $eventName) === false) {
				$this->wpSettingsUtil->updateOption('event_' . $eventName, 1);
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
		title="<?php echo $args['title']; ?>"
		<?php endif; ?>
		value="1"
		<?php checked( $value, 1 ); ?> />
	  <p class="description">
		<?php echo $args['description']; ?>
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
		<?php echo $args['description']; ?>
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
		name="<?php echo esc_attr( $args['label_for'] ); ?>"><?php echo $value; ?></textarea>
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
		value="<?php echo $value; ?>"
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
			'Google Tag Manager for WooCommerce FREE',
			'Google Tag Manager',
			'manage_options'
		);
	}


}
