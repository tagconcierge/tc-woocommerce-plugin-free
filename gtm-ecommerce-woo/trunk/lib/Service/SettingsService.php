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
    }

    function settingsInit() {

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

        $jsonFile = plugin_dir_url( realpath(__DIR__ . '/..') ) . "/gtm-containers/ga4.json";

        $this->wpSettingsUtil->addSettingsSection(
            "gtm_container_jsons",
            "Google Tag Manager containers ",
            'It\'s time to define what to do with tracked Ecommerce events. We know that settings up GTM workspace may be cumbersome. That\'s why the plugin comes with a JSON file you can import to your GTM workspace to create all required Tags, Triggers and Variables. Here is a list of currently provided containers (save as json file and import in Admin panel in your GTM workspace):<br /><br /><a href="'.$jsonFile.'">GA4 container (Add To Cart, Purchase events)</a>'
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