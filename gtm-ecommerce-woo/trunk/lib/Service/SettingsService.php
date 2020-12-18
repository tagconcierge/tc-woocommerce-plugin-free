<?php

namespace GtmEcommerceWoo\Lib\Service;

/**
 * Logic related to working with settings and options
 */
class SettingsService {

    public function __construct($wpSettingsUtil) {
        $this->wpSettingsUtil = $wpSettingsUtil;
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
        $response = wp_remote_get( 'https://api.gtmconcierge.com/v1/presets?uuid=' . $uuid );
        $body     = wp_remote_retrieve_body( $response );
        wp_send_json(json_decode($body));
        wp_die();
    }

    function ajaxPostPresets() {
        $uuid = $this->wpSettingsUtil->getOption('uuid');
        $args = [
            'body' => json_encode([
                'preset' => $_GET['preset'],
                'uuid' => $uuid
            ]),
            'headers' => [
                'content-type' => 'application/json'
            ],
            'data_format' => 'body',
        ];
        $response = wp_remote_post( 'https://api.gtmconcierge.com/v1/preset', $args );
        $body     = wp_remote_retrieve_body( $response );
        header("Cache-Control: public");
        header("Content-Description: File Transfer");
        header("Content-Disposition: attachment; filename=preset.json");
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
        $this->wpSettingsUtil->registerSetting('ua_compatibility');
        $this->wpSettingsUtil->registerSetting('gtm_snippet_head');
        $this->wpSettingsUtil->registerSetting('gtm_snippet_body');

        $this->wpSettingsUtil->addSettingsSection(
            "basic",
            "Basic Settings",
            'This plugin push basic Enhanced Ecommerce events from WooCommerce shop to Google Tag Manager instance. After enabling add tags and triggers to your GTM container in order to use and analyze captured data. It just work and does not require any additional configuration.'
        );
        $this->wpSettingsUtil->addSettingsSection(
            "gtm_snippet",
            "Google Tag Manager snippet",
            'Enhanced Ecommerce GTM for WooCommerce can work with any GTM implementation in the page. If you already implemented GTM using other plugin or directly in the theme code leave the settings below empty. If you want to implement GTM using this plugin paste in two snippets provided by GTM. To find those snippets navigate to `Admin` tab in GTM console and click `Install Google Tag Manager`.'
        );

        $this->wpSettingsUtil->addSettingsSection(
            "gtm_container_jsons",
            "Google Tag Manager presets",
            'It\'s time to define what to do with tracked Ecommerce events. We know that settings up GTM workspace may be cumbersome. That\'s why the plugin comes with a JSON file you can import to your GTM workspace to create all required Tags, Triggers and Variables. Select a preset in dropdown below, download the JSON file and import it in Admin panel in your GTM workspace, see plugin <a href="https://wordpress.org/plugins/gtm-ecommerce-woo/#installation" target="_blank">Installation Documentation</a> for details):<br /><br /><select id="gtm-ecommerce-woo-select-preset"></select><button id="gtm-ecommerce-woo-download-preset" class="button">Download Preset</button>'
        );

        // Register a new field in the "wporg_section_developers" section, inside the "wporg" page.
        $this->wpSettingsUtil->addSettingsField(
            'disabled',
            'Disable?',
            [$this, "checkboxField"],
            'basic',
            'When checked plugin won\'t load anything in the page.'
        );

        $this->wpSettingsUtil->addSettingsField(
            'ua_compatibility',
            'Universal Analytics compatibility',
            [$this, "checkboxField"],
            'basic',
            'When checked plugin will emit events compatible with legacy Enhanced Ecommerce format for Universal Analytics GA properties. Check it only if you use UA property. If you plan using UA and GA4 properties at the same time adjust your GTM tags and variables to use legacy format. <strong>This function is coming soon. Do you need UA compatiblity? <a href="https://michal159509.typeform.com/to/VNbZrezV" target="_blank">Fill in this survey to help us prioritize it!</a></strong>',
            ['disabled' => true]
        );

        $this->wpSettingsUtil->addSettingsField(
            'gtm_snippet_head',
            'GTM Snippet head',
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

        $uuid = $this->wpSettingsUtil->getOption('uuid');
        if ($uuid === false) {
            $this->wpSettingsUtil->updateOption('uuid', uniqid());
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
        value="1"
        <?php checked( $value, 1 ); ?> />
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