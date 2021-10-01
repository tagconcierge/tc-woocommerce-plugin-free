<?php

namespace GtmEcommerceWoo\Lib\Service;

/**
 * Logic related to working with settings and options
 */
class SettingsService {

	public function __construct($wpSettingsUtil, $events, $proEvents) {
		$this->wpSettingsUtil = $wpSettingsUtil;
		$this->events = $events;
		$this->proEvents = $proEvents;
		$this->uuidPrefix = 'gtm-ecommerce-woo-basic';
	}

	public function initialize() {
		$this->wpSettingsUtil->addTab(
			'settings',
			"Settings"
		);

		$this->wpSettingsUtil->addTab(
			'gtm_presets',
			"GTM Presets",
			false
		);

		$this->wpSettingsUtil->addTab(
			'tools',
			"Tools"
		);

		$this->wpSettingsUtil->addTab(
			'tag_concierge',
			'Tag Concierge <pre style="display: inline; text-transform: uppercase;">beta</pre>'
		);

		$this->wpSettingsUtil->addTab(
			'support',
			"Support",
			false
		);

		add_action( 'admin_init', [$this, 'settingsInit'] );
		add_action( 'admin_menu', [$this, 'optionsPage'] );
		add_action( 'admin_enqueue_scripts', [$this, 'enqueueScripts'] );
		add_action( 'wp_ajax_gtm_ecommerce_woo_get_presets', [$this, 'ajaxGetPresets'] );
		add_action( 'wp_ajax_gtm_ecommerce_woo_post_preset', [$this, 'ajaxPostPresets'] );
	}

	function ajaxGetPresets() {
		$uuid = $this->wpSettingsUtil->getOption('uuid');
		$response = wp_remote_get( 'https://api.tagconcierge.com/v2/presets?uuid=' . $uuid );
		$body     = wp_remote_retrieve_body( $response );
		wp_send_json(json_decode($body));
		wp_die();
	}

	function ajaxPostPresets() {
		$uuid = $this->wpSettingsUtil->getOption('uuid');
		$disabled = $this->wpSettingsUtil->getOption('disabled');
		$gtmSnippetHead = $this->wpSettingsUtil->getOption('gtm_snippet_head');
		$gtmSnippetBody = $this->wpSettingsUtil->getOption('gtm_snippet_body');
		$presetName = str_replace('presets/', '', $_GET['preset']) . '.json';
		$args = [
			'body' => json_encode([
				'preset' => $_GET['preset'],
				'uuid' => $uuid,
				'disabled' => $disabled,
				'gtm_snippet_head' => sha1($gtmSnippetHead),
				'gtm_snippet_body' => sha1($gtmSnippetBody)
			]),
			'headers' => [
				'content-type' => 'application/json'
			],
			'data_format' => 'body',
		];
		$response = wp_remote_post( 'https://api.tagconcierge.com/v2/preset', $args );
		$body     = wp_remote_retrieve_body( $response );
		header("Cache-Control: public");
		header("Content-Description: File Transfer");
		header("Content-Disposition: attachment; filename=".$presetName);
		header("Content-Transfer-Encoding: binary");
		wp_send_json(json_decode($body));
		wp_die();
	}

	function enqueueScripts($hook) {
		if ( 'settings_page_gtm-ecommerce-woo' != $hook ) {
			return;
		}
		wp_enqueue_script( 'wp-pointer' );
        wp_enqueue_style( 'wp-pointer' );
		wp_enqueue_script( 'gtm-ecommerce-woo-admin', plugin_dir_url( __DIR__ . '/../../../' ) . 'js/admin.js', [], '1.0' );
	}

	function settingsInit() {
		$this->wpSettingsUtil->registerSetting('uuid');

		$this->wpSettingsUtil->addSettingsSection(
			"basic",
			"Basic Settings",
			'This plugin push eCommerce events from WooCommerce shop to Google Tag Manager instance. After enabling, add tags and triggers to your GTM container in order to use and analyze captured data. For quick start use one of the GTM presets available below.',
			'settings'
		);

		$this->wpSettingsUtil->addSettingsSection(
			"gtm_snippet",
			"Google Tag Manager snippet",
			'Paste two snippets provided by GTM. To find those snippets navigate to `Admin` tab in GTM console and click `Install Google Tag Manager`. If you already implemented GTM snippets in your page, paste them below, but select appropriate `Prevent loading GTM Snippet` option.',
			'settings'
		);

		$this->wpSettingsUtil->addSettingsSection(
			"events",
			"Events",
			'Select which events should be tracked:',
			'settings'
		);

		$this->wpSettingsUtil->addSettingsSection(
			"tag_concierge",
			"Tag Concierge",
			'Want to learn more? <a href="https://tagconcierge.com/platform/" target="_blank">See overview here</a>',
			'tag_concierge'
		);

		$this->wpSettingsUtil->addSettingsSection(
			"gtm_container_jsons",
			"Google Tag Manager presets",
			'It\'s time to define what to do with tracked eCommerce events. We know that settings up GTM workspace may be cumbersome. That\'s why the plugin comes with a set of presets you can import to your GTM workspace to create all required Tags, Triggers and Variables. Select a preset in dropdown below, download the JSON file and import it in Admin panel in your GTM workspace, see plugin <a href="https://handcraftbyte.com/gtm-ecommerce-for-woocommerce/#documentation" target="_blank">Documentation</a> for details):<br /><br /><div id="gtm-ecommerce-woo-presets-loader" style="text-align: center;"><span class="spinner is-active" style="float: none;"></span></div><div class="metabox-holder"><div id="gtm-ecommerce-woo-presets-grid" class="postbox-container" style="float: none;"><div id="gtm-ecommerce-woo-preset-tmpl" style="display: none;"><div style="display: inline-block;
    margin-left: 4%; width: 45%" class="postbox"><h3 class="name">Google Analytics 4</h3><div class="inside"><p class="description">Description</p><p><b>Supported events:</b> <span class="events-count">2</span> <span class="events-list dashicons dashicons-info-outline" style="cursor: pointer;"></span></p><p><a class="download button button-primary" href="#">Download</a></p></div></div></div></div></div><br /><div id="gtm-ecommerce-woo-presets-upgrade" style="text-align: center"><a style="display: none;" class="button button-primary" href="https://go.tagconcierge.com/MSm8e" target="_blank">Upgrade to PRO</a></div>',
			'gtm_presets'
		);

		$this->wpSettingsUtil->addSettingsSection(
			"support",
			"Support",
			'<a class="button button-primary" href="https://handcraftbyte.com/gtm-ecommerce-for-woocommerce/#documentation" target="_blank">Documentation</a><br /><br /><a class="button button-primary" href="mailto:support@handcraftbyte.com">Contact Support</a>',
			'support'
		);

		$this->wpSettingsUtil->addSettingsSection(
			"event_inspector",
			"Event Inspector",
			'Events Inspector provide basic way of confirming that events are being tracked. Depending on the setting below it will show a small window at the bottom of every page with all eCommerce events captured during a given session.',
			'tools'
		);

		$this->wpSettingsUtil->addSettingsSection(
			"theme_validator",
			"Theme Validator",
			'Theme Validator allows to assess if all events supported by this plugin can be tracked on your current theme: <strong>' . (wp_get_theme())->get('Name') . '</strong>. Your WordPress site must be publicly available to perform this test. It is a semi-manual operation and we usually can repond with initial analysis within 2 business days, but it can get longer depending on current queue size. Clicking the button below will send your email address and URL of this WordPress site to our servers to perform a remote static analysis. This static analysis will ensure all WordPress/WooCommerce internal hooks/actions and correct HTML elements are present in order to track all supported events, but it cannot detect issues with dynamic scripts and elements. For full testing the Event Inspector can be used.<br />
			<div style="text-align: center" id="gtm-ecommerce-woo-validator-section"><input id="gtm-ecommerce-woo-theme-validator-email" type="text" name="email" placeholder="email" /><button id="gtm-ecommerce-woo-theme-validator" class="button">Request Theme Validation</button></div>
			<div style="text-align: center; display: none" id="gtm-ecommerce-woo-validator-sent">Your Theme Validation request was sent, you will hear from us within 2 business days.</div>',
			'tools'
		);

		$this->wpSettingsUtil->addSettingsField(
			'disabled',
			'Disable?',
			[$this, "checkboxField"],
			'basic',
			'When checked the plugin won\'t load anything in the page.'
		);

		$this->wpSettingsUtil->addSettingsField(
			'theme_validator_enabled',
			'Enable Theme Validator?',
			[$this, "checkboxField"],
			'basic',
			'Allow the plugin and the support team to validate theme by issuing a special HTTP request. Provide them with following information: `uuid_hash:'
			.md5($this->wpSettingsUtil->getOption('uuid')).'`.'
		);

		$this->wpSettingsUtil->addSettingsField(
			'event_inspector_enabled',
			'Enable Event Inspector?',
			[$this, "selectField"],
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
			[$this, "selectField"],
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
			[$this, "textareaField"],
			'gtm_snippet',
			'Paste the first snippet provided by GTM. It will be loaded in the <head> of the page.',
			['rows'        => 9]
		);


		$this->wpSettingsUtil->addSettingsField(
			'gtm_snippet_body',
			'GTM Snippet body',
			[$this, "textareaField"],
			'gtm_snippet',
			'Paste the second snippet provided by GTM. It will be load after opening <body> tag.',
			['rows'        => 6]
		);


		$this->wpSettingsUtil->addSettingsField(
			'monitor_enabled',
			'Enable Tag Concierge Monitor?',
			[$this, "checkboxField"],
			'tag_concierge',
			'Enable sending some of the eCommerce events to Tag Concierge Monitor for active tracking monitoring. <br /><a href="https://app.tagconcierge.com/?uuid='.$this->wpSettingsUtil->getOption('uuid').'" target="_blank">Open Tag Concierge App</a>'
		);


		foreach ($this->events as $eventName) {
			$this->wpSettingsUtil->addSettingsField(
				'event_' . $eventName,
				$eventName,
				[$this, "checkboxField"],
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
				[$this, "checkboxField"],
				'events',
				'<a style="font-size: 0.7em" href="https://go.tagconcierge.com/MSm8e" target="_blank">Upgrade to PRO</a>',
				['disabled' => true, "title" => "Upgrade to PRO version above."]
			);
		}


		$uuid = $this->wpSettingsUtil->getOption('uuid');
		if (empty($uuid) || strlen($uuid) === 13) {
			$this->wpSettingsUtil->updateOption('uuid', $this->uuidPrefix . '_' . bin2hex(random_bytes(20)));
		}

		if ($this->wpSettingsUtil->getOption('theme_validator_enabled') === false) {
			$this->wpSettingsUtil->updateOption('theme_validator_enabled', 1);
		}
	}

	function checkboxField( $args ) {
		// Get the value of the setting we've registered with register_setting()
		$value = get_option( $args['label_for'] );
		?>
	  <input
		type="checkbox"
		id="<?php echo esc_attr( $args['label_for'] ); ?>"
		name="<?php echo esc_attr( $args['label_for'] ); ?>"
		<?php if (@$args['disabled'] === true): ?>
		disabled="disabled"
		<?php endif; ?>
		<?php if (@$args['title']): ?>
		title="<?php echo $args['title'] ?>"
		<?php endif; ?>
		value="1"
		<?php checked( $value, 1 ); ?> />
	  <p class="description">
		<?php echo $args['description']; ?>
	  </p>
		<?php
	}

	function selectField( $args ) {
		// Get the value of the setting we've registered with register_setting()
		$selectedValue = get_option( $args['label_for'] );
		?>
	  <select
		type="checkbox"
		id="<?php echo esc_attr( $args['label_for'] ); ?>"
		name="<?php echo esc_attr( $args['label_for'] ); ?>"
		<?php if (@$args['disabled'] === true): ?>
		disabled="disabled"
		<?php endif; ?>
		>
		<?php foreach ($args['options'] as $value => $label): ?>
			<option value="<?php echo esc_attr($value) ?>"
				<?php if ($selectedValue == $value): ?>
				selected
				<?php endif; ?>
				><?php echo esc_html($label) ?></option>
		<?php endforeach ?>
		</select>
	  <p class="description">
		<?php echo $args['description']; ?>
	  </p>
		<?php
	}


	function textareaField( $args ) {
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

	function optionsPage() {
		$this->wpSettingsUtil->addSubmenuPage(
			'options-general.php',
			'Google Tag Manager for WooCommerce FREE',
			'Google Tag Manager',
			'manage_options'
		);
	}


}
