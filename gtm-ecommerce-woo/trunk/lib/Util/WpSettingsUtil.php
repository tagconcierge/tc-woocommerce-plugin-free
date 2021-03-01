<?php

namespace GtmEcommerceWoo\Lib\Util;

/**
 * Utility to work with settings and options Wordpress API.
 */
class WpSettingsUtil {
    protected $snakeCaseNamespace;
    protected $spineCaseNamespace;

    public function __construct($snakeCaseNamespace, $spineCaseNamespace) {
        $this->snakeCaseNamespace = $snakeCaseNamespace;
        $this->spineCaseNamespace = $spineCaseNamespace;
    }

    public function getOption($optionName) {
        return get_option($this->snakeCaseNamespace . '_' . $optionName);
    }

    public function deleteOption($optionName) {
        return delete_option($this->snakeCaseNamespace . '_' . $optionName);
    }

    public function updateOption($optionName, $optioValue) {
        return update_option($this->snakeCaseNamespace . '_' . $optionName, $optioValue);
    }

    public function registerSetting($settingName) {
        register_setting( $this->snakeCaseNamespace, $this->snakeCaseNamespace . '_' . $settingName );
    }

    public function addSettingsSection($sectionName, $sectionTitle, $description) {
        $spineCaseNamespace = $this->spineCaseNamespace;
        add_settings_section(
            $this->snakeCaseNamespace . '_' . $sectionName,
            __( $sectionTitle, $this->spineCaseNamespace ),
            function($args) use ($spineCaseNamespace, $description) {
                ?>
              <p id="<?php echo esc_attr( $args['id'] ); ?>"><?php echo $description ?></p>
                <?php
            },
            $this->snakeCaseNamespace
        );
    }

    public function addSettingsField($fieldName, $fieldTitle, $fieldCallback, $fieldSection, $fieldDescription, $extraAttrs = []) {
        $attrs = array_merge([
            'label_for'   => $this->snakeCaseNamespace . '_' . $fieldName,
            'description' => $fieldDescription,
        ], $extraAttrs);
        add_settings_field(
            $this->snakeCaseNamespace . '_' . $fieldName, // As of WP 4.6 this value is used only internally.
            // Use $args' label_for to populate the id inside the callback.
            __( $fieldTitle, $this->spineCaseNamespace ),
            $fieldCallback,
            $this->snakeCaseNamespace,
            $this->snakeCaseNamespace . '_' . $fieldSection,
            $attrs
        );
    }

    public function addSubmenuPage($options, $title1, $title2, $capabilities) {
        $snakeCaseNamespace = $this->snakeCaseNamespace;
        $spineCaseNamespace = $this->spineCaseNamespace;
        add_submenu_page(
            $options,
            $title1,
            $title2,
            $capabilities,
            $this->spineCaseNamespace,
            function() use ($capabilities, $snakeCaseNamespace, $spineCaseNamespace) {
                // check user capabilities
                if ( ! current_user_can( $capabilities ) ) {
                    return;
                }

                // show error/update messages
                settings_errors( $snakeCaseNamespace . '_messages' );
                ?>
              <div class="wrap">
                <h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
                <form action="options.php" method="post">
                  <?php
                    // output security fields for the registered setting "wporg_options"
                    settings_fields( $snakeCaseNamespace );
                    // output setting sections and their fields
                    // (sections are registered for "wporg", each field is registered to a specific section)
                    do_settings_sections( $snakeCaseNamespace );
                    // output save settings button
                    submit_button( __( 'Save Settings', $spineCaseNamespace ) );
                    ?>
                </form>
              </div>
                <?php
            }
        );
    }
}