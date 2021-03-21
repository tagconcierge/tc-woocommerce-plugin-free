<?php

namespace GtmEcommerceWoo\Lib\Service;

/**
 * Logic related to working with settings and options
 */
class SettingsService {

	public function __construct($wpSettingsUtil) {
		$this->wpSettingsUtil = $wpSettingsUtil;
		$this->uuidPrefix = 'gtm-ecommerce-woo-basic';
	}

	public function initialize() {
		add_action( 'admin_init', [$this, 'settingsInit'] );
		add_action( 'admin_menu', [$this, 'optionsPage'] );
		add_action( 'admin_enqueue_scripts', [$this, 'enqueueScripts'] );
		add_action( 'wp_ajax_gtm_ecommerce_woo_get_presets', [$this, 'ajaxGetPresets'] );
		add_action( 'wp_ajax_gtm_ecommerce_woo_post_preset', [$this, 'ajaxPostPresets'] );
	}

	function ajaxGetPresets() {
		$uuid = $this->wpSettingsUtil->getOption('uuid');
		$response = wp_remote_get( 'https://api.gtmconcierge.com/v2/presets?uuid=' . $uuid );
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
		$response = wp_remote_post( 'https://api.gtmconcierge.com/v2/preset', $args );
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
		wp_enqueue_script( 'gtm-ecommerce-woo-admin', plugin_dir_url( __DIR__ . '/../../../' ) . 'js/admin.js', [], '1.0' );
	}

	function settingsInit() {
		$this->wpSettingsUtil->registerSetting('uuid');
		$this->wpSettingsUtil->registerSetting('disabled');
		$this->wpSettingsUtil->registerSetting('debugger_enabled');
		$this->wpSettingsUtil->registerSetting('theme_validator_enabled');
		$this->wpSettingsUtil->registerSetting('gtm_snippet_prevent_load');
		$this->wpSettingsUtil->registerSetting('gtm_snippet_head');
		$this->wpSettingsUtil->registerSetting('gtm_snippet_body');

		$uuid = $this->wpSettingsUtil->getOption('uuid');
		if (empty($uuid) || strlen($uuid) === 13) {
			$this->wpSettingsUtil->updateOption('uuid', $this->uuidPrefix . '_' . bin2hex(random_bytes(20)));
		}

		if ($this->wpSettingsUtil->getOption('theme_validator_enabled') === false) {
			$this->wpSettingsUtil->updateOption('theme_validator_enabled', 1);
		}

		$this->wpSettingsUtil->addTab(
			'settings',
			"Settings"
		);

		$this->wpSettingsUtil->addTab(
			'theme_validator',
			"Theme Validator"
		);

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
			"gtm_container_jsons",
			"Google Tag Manager presets",
			'It\'s time to define what to do with tracked eCommerce events. We know that settings up GTM workspace may be cumbersome. That\'s why the plugin comes with a set of presets you can import to your GTM workspace to create all required Tags, Triggers and Variables. Select a preset in dropdown below, download the JSON file and import it in Admin panel in your GTM workspace, see plugin <a href="https://handcraftbyte.com/gtm-ecommerce-for-woocommerce/#documentation" target="_blank">Documentation</a> for details):<br /><br /><select id="gtm-ecommerce-woo-select-preset"></select><button id="gtm-ecommerce-woo-download-preset" class="button">Download Preset</button>',
			'settings'
		);

		$this->wpSettingsUtil->addSettingsSection(
			"theme_validator_settings",
			"Theme Validator",
			'This plugin push eCommerce events from WooCommerce shop to Google Tag Manager instance. After enabling, add tags and triggers to your GTM container in order to use and analyze captured data. For quick start use one of the GTM presets available below.',
			'theme_validator'
		);

		// $this->wpSettingsUtil->addSettingsSection(
		// 	"theme_validator_status",
		// 	"Status",
		// 	'<div class="metabox-holder"><div class="postbox"><h3>Home page</h3><div class="inside"></div></div></div>',
		// 	'theme_validator'
		// );

		$this->wpSettingsUtil->addSettingsField(
			'disabled',
			'Disable?',
			[$this, "checkboxField"],
			'basic',
			'When checked the plugin won\'t load anything in the page.'
		);

		// $this->wpSettingsUtil->addSettingsField(
		//     'debugger_enabled',
		//     'Enable Debugger?',
		//     [$this, "checkboxField"],
		//     'basic',
		//     'Enable to help support team debug issues with tracking. Provide them with following information: `uuid_hash:'
		//     	.md5($this->wpSettingsUtil->getOption('uuid')).'`.'
		// );

		$this->wpSettingsUtil->addSettingsField(
			'theme_validator_enabled',
			'Enable Theme Validator?',
			[$this, "checkboxField"],
			'theme_validator_settings',
			'Allow the plugin and the support team to validate theme by issuing a special HTTP request. Provide them with following information: `uuid_hash:'
			.md5($this->wpSettingsUtil->getOption('uuid')).'`.'
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
			'GTM Ecommerce for WooCommerce',
			'GTM Ecommerce',
			'manage_options'
		);
	}


}
