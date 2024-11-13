<?php

namespace GtmEcommerceWoo\Lib\Service;

use GtmEcommerceWoo\Lib\Util\SanitizationUtil;
use GtmEcommerceWoo\Lib\Util\WcOutputUtil;
use GtmEcommerceWoo\Lib\Util\WpSettingsUtil;

/**
 * Logic to handle general plugin hooks.
 */
class PluginService {
	/** @var WpSettingsUtil */
	protected $wpSettingsUtil;

	/** @var WcOutputUtil */
	protected $wcOutputUtil;

	/** @var string */
	protected $spineCaseNamespace;

	/** @var string */
	protected $pluginVersion;

	/** @var string */
	protected $feedbackUrl = 'https://wordpress.org/plugins/gtm-ecommerce-woo/#reviews';

	/** @var int */
	protected $feedbackDays = 7;

	/** @var bool */
	protected $serviceNotice = true;

	public function __construct( string $spineCaseNamespace, WpSettingsUtil $wpSettingsUtil, WcOutputUtil $wcOutputUtil, string $pluginVersion ) {
		$this->spineCaseNamespace = $spineCaseNamespace;
		$this->wpSettingsUtil = $wpSettingsUtil;
		$this->wcOutputUtil = $wcOutputUtil;
		$this->pluginVersion = $pluginVersion;
	}

	public function initialize() {
		add_action( 'admin_notices', [$this, 'activationNoticeSuccess'] );

		if (false !== $this->serviceNotice && !$this->wpSettingsUtil->getOption('service_prompt_at')) {
			add_action( 'admin_notices', [$this, 'serviceNotice'] );
			add_action( 'admin_enqueue_scripts', [$this, 'enqueueScripts'] );
			add_action( 'wp_ajax_gtm_ecommerce_woo_dismiss_feedback', [$this, 'dismissServiceFeedback'] );
		}

		if ($this->wpSettingsUtil->getOption('earliest_active_at') && !$this->wpSettingsUtil->getOption('feedback_prompt_at')) {

			$earliest = new \DateTime($this->wpSettingsUtil->getOption('earliest_active_at'));

			$numberOfDays = $earliest->diff(new \DateTime())->format('%a');

			if ($numberOfDays >= $this->feedbackDays) {
				add_action( 'admin_notices', [$this, 'satisfactionNotice'] );
				add_action( 'admin_enqueue_scripts', [$this, 'enqueueScripts'] );
				add_action( 'wp_ajax_gtm_ecommerce_woo_dismiss_feedback', [$this, 'dismissFeedback'] );
			}
		}

		if (!$this->wpSettingsUtil->getOption( 'earliest_active_at' )) {
			$this->wpSettingsUtil->updateOption( 'earliest_active_at', ( new \DateTime() )->format('Y-m-d H:i:s') );
		}
	}

	public function enqueueScripts( $hook) {
		wp_enqueue_script( 'gtm-ecommerce-woo-admin-feedback', plugin_dir_url( __DIR__ . '/../../../' ) . 'assets/admin-feedback.js', [], $this->pluginVersion );
	}

	public function activationHook() {
		/**
		 * Activation hook.
		 *
		 * @since 1.0.0
		 */
		if (in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins')),
			true
		)) {
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
			  <p>
			  <?php
				  echo sprintf(
					  '<strong>Google Tag Manager for WooCommerce</strong> activated succesfully 🎉  If you already have GTM implemented in your shop, the plugin will start to send eCommerce data right away, if not navigate to <a href="%s">settings</a>.',
						esc_url($url)
					  )
				?>
			  </p>
		  </div>
			<?php
			/* Delete transient, only display this notice once. */
			delete_transient( $this->spineCaseNamespace . '\activation-transient' );
		}
	}

	public function dismissFeedback() {
		$this->wpSettingsUtil->updateOption('feedback_prompt_at', ( new \DateTime() )->format('Y-m-d H:i:s'));
		wp_send_json(['status' => true]);
		wp_die();
	}

	public function dismissServiceFeedback() {
		$this->wpSettingsUtil->updateOption('service_prompt_at', ( new \DateTime() )->format('Y-m-d H:i:s'));
		wp_send_json(['status' => true]);
		wp_die();
	}

	public function satisfactionNotice() {
		?>
		<div class="notice notice-success is-dismissible" data-gtm-ecommerce-woo-feedback>
			<p>
				<?php
					echo sprintf(
						'Are you happy using <strong>Google Tag Manager for WooCommerce</strong>? <span data-section="questions"><a href="#" data-target="answer-yes">Yes!</a> <a href="#" data-target="answer-no">Not really...</a></span> <span style="display: none" data-section="answer-yes">That\'s great! We humbly ask you to consider <a href="%s" target="_blank">giving us a review</a>. That will allow us to extend support for the plugin.</span> <span style="display: none" data-section="answer-no">We are sorry to hear that. <a href="https://tagconcierge.com/contact" target="_blank">Contact us</a> and we may be able to help!</span>',
						esc_url($this->feedbackUrl)
					);
				?>
			</p>
		</div>
		<?php
	}

	public function serviceNotice() {
		?>
		<div class="notice notice-success is-dismissible" data-gtm-ecommerce-woo-feedback>
			<p>
				Need help with the tracking setup? Explore our <a href="https://tagconcierge.com/services" target="_blank">Services</a> to ensure data issues are not hindering your business growth.
			</p>
		</div>
		<?php
	}
}
