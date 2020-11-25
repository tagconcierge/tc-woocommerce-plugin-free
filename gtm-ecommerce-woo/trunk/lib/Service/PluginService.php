<?php

namespace GtmEcommerceWoo\Lib\Service;

/**
 * Logic to handle embedding Gtm Snippet
 */
class PluginService {
    protected $spineCaseNamespace;

    public function __construct($spineCaseNamespace) {
        $this->spineCaseNamespace = $spineCaseNamespace;
    }

    public function initialize() {
        add_action( 'admin_notices', [$this, 'activationNoticeSuccess'] );

        if ( ! in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {

            add_action( 'admin_notices', [$this, 'inactiveWooCommerceNoticeError'] );
        }
    }

    function activationHook() {
        if ( in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {
            set_transient( $this->spineCaseNamespace.'\activation-transient', true, 5 );
        }
    }


    function activationNoticeSuccess() {

        if ( get_transient( $this->spineCaseNamespace . '\activation-transient' ) ) {
            // Build and escape the URL.
            $url = esc_url(
                add_query_arg(
                    'page',
                    $this->spineCaseNamespace,
                    get_admin_url() . 'options-general.php'
                )
            );
            // Create the link.
            ?>
          <div class="notice notice-success is-dismissible">
              <p><?php _e( '<strong>GTM Ecommerce for WooCommerce</strong> activated succesfully 🎉  If you already have GTM implemented in your shop, the plugin will start to send Ecommerce data right away, if not navigate to <a href="' . $url . '">settings</a>.', $this->spineCaseNamespace ); ?></p>
          </div>
            <?php
            /* Delete transient, only display this notice once. */
            delete_transient( $this->spineCaseNamespace . '\activation-transient' );
        }
    }


    function inactiveWooCommerceNoticeError() {
        $class   = 'notice notice-error';
        $message = __( 'GTM Ecommerce for WooCommerce: it seems WooCommerce is not installed or activated in this WordPress installation. GTM Ecommerce won\'t work without WooCommerce.', $this->spineCaseNamespace );

        printf( '<div class="%1$s"><p>%2$s</p></div>', esc_attr( $class ), esc_html( $message ) );
    }

}
