<?php

namespace GtmEcommerceWoo\Lib\Service;

/**
 * Logic to handle general plugin hooks.
 */
class PluginService {
	protected $spineCaseNamespace;
	protected $wpSettingsUtil;

	public function __construct( $spineCaseNamespace, $wpSettingsUtil ) {
		$this->spineCaseNamespace = $spineCaseNamespace;
		$this->wpSettingsUtil = $wpSettingsUtil;
	}

	public function initialize() {
		add_action( 'admin_notices', [$this, 'activationNoticeSuccess'] );

		if ($this->wpSettingsUtil->getOption('earliest_active_at') && !$this->wpSettingsUtil->getOption('feedback_prompt_at')) {

			$earliest = new \DateTime($this->wpSettingsUtil->getOption('earliest_active_at'));

			$numberOfDays = $earliest->diff(new \DateTime())->format('%a');

			if ($numberOfDays >= 7) {
				$this->wpSettingsUtil->updateOption('feedback_prompt_at', (new \DateTime())->format('Y-m-d H:i:s'));
				add_action( 'admin_notices', [$this, 'satisfactionNotice'] );
				add_action( 'admin_enqueue_scripts', [$this, 'enqueueScripts'] );
			}
		}

		if (!$this->wpSettingsUtil->getOption('earliest_active_at')) {
			$this->wpSettingsUtil->updateOption('earliest_active_at', (new \DateTime())->format('Y-m-d H:i:s'));
		}
		

		if (! in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {

			add_action( 'admin_notices', [$this, 'inactiveWooCommerceNoticeError'] );
		}
	}

	public function enqueueScripts( $hook) {
		wp_enqueue_script( 'gtm-ecommerce-woo-admin-feedback', plugin_dir_url( __DIR__ . '/../../../' ) . 'js/admin-feedback.js', [], $this->pluginVersion );
	}

	public function activationHook() {
		if ( in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {
			set_transient( $this->spineCaseNamespace . '\activation-transient', true, 5 );
		}
	}


	public function activationNoticeSuccess() {

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
			  <p><?php _e( '<strong>Google Tag Manager for WooCommerce</strong> activated succesfully 🎉  If you already have GTM implemented in your shop, the plugin will start to send eCommerce data right away, if not navigate to <a href="' . $url . '">settings</a>.', $this->spineCaseNamespace ); ?></p>
		  </div>
			<?php
			/* Delete transient, only display this notice once. */
			delete_transient( $this->spineCaseNamespace . '\activation-transient' );
		}
	}


	public function inactiveWooCommerceNoticeError() {
		?>
		<div class="notice notice-error is-dismissible">
			<p><?php _e( '<strong>Google Tag Manager for WooCommerce</strong>: it seems WooCommerce is not installed or activated in this WordPress installation. GTM for WooCommerce plugin won\'t work without WooCommerce. To resolve this problem either activate WooCommerce or deactivate GTM for WooCommerce plugin.', $this->spineCaseNamespace ); ?></p>
	  	</div>
		<?php
	}

	public function satisfactionNotice() {
		?>
		<div class="notice notice-success is-dismissible" data-gtm-ecommerce-woo-feedback>
			<p><?php _e( 'Are you happy using <strong>Google Tag Manager for WooCommerce</strong>? <span data-section="questions"><a href="#" data-target="answer-yes">Yes!</a> <a href="#" data-target="answer-no">Not really...</a></span> <span style="display: none" data-section="answer-yes">That\'s great! We humbly ask you to consider <a href="https://wordpress.org/plugins/gtm-ecommerce-woo/#reviews" target="_blank">giving us a review</a>. That will allow us to extend support for the plugin.</span> <span style="display: none" data-section="answer-no">We are sorry to hear that. <a href="https://tagconcierge.com/contact" target="_blank">Contact us</a> and we may be able to help!</span>', $this->spineCaseNamespace ); ?></p>
		</div>
		<?php
	}

}
