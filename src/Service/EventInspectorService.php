<?php

namespace GtmEcommerceWoo\Lib\Service;

use GtmEcommerceWoo\Lib\Util\WcOutputUtil;
use GtmEcommerceWoo\Lib\Util\WpSettingsUtil;

/**
 * Service to inject dataLayer eCommerce events inspector which is a box
 * fixed to the bottom part of the browser.
 *
 * To enable special demo mode: wp option set gtm_ecommerce_woo_event_inspector_demo_mode 1
 */
class EventInspectorService {
	protected $wpSettingsUtil;
	protected $wcOutputUtil;
	protected $uuidPrefix;

	public function __construct( WpSettingsUtil $wpSettingsUtil, WcOutputUtil $wcOutputUtil) {
		$this->wpSettingsUtil = $wpSettingsUtil;
		$this->wcOutputUtil = $wcOutputUtil;
		$this->uuidPrefix = substr($this->wpSettingsUtil->getOption('uuid'), 0, -41);
	}

	public function initialize() {

		switch ($this->wpSettingsUtil->getOption('event_inspector_enabled')) {
			case false:
			case 'no':
				return;
			case 'yes-querystring':
				if (!isset($_GET['gtm-inspector']) || '1' !== $_GET['gtm-inspector']) {
					return;
				}
		}

		$this->wcOutputUtil->scriptFile('gtm-ecommerce-woo-event-inspector', ['jquery']);
		$this->wcOutputUtil->cssFile('gtm-ecommerce-woo-event-inspector');

		add_action( 'wp_enqueue_scripts', [$this, 'wpEnqueueScripts'] );
		add_action( 'wp_footer', [$this, 'footerHtml'], 0 );
	}

	public function wpEnqueueScripts() {
		wp_enqueue_script(
			'highlight.js',
			'https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.9.0/highlight.min.js'
		);

		wp_enqueue_script(
			'highlight.js-json',
			'https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.9.0/languages/json.min.js',
			['highlight.js']
		);

		wp_enqueue_style(
			'highlight.js',
			'https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.9.0/styles/default.min.css'
		);
	}

	public function isDisabled(): bool {
		if ($this->wpSettingsUtil->getOption('event_inspector_enabled') === 'yes-admin') {
			$user = \wp_get_current_user();
			if (!$user) {
				return true;
			}
			if (count(array_intersect( ['editor', 'administrator'], $user->roles )) === 0 ) {
				return true;
			}
		}
		return false;
	}

	public function footerHtml() {
		if ($this->isDisabled()) {
			return;
		}
		?>
<div id="gtm-ecommerce-woo-event-inspector" style="display: none;">
	<div class="header">
		<span>GTM Debug Tool</span>
		<div>
			<button class="clear-history">Clear History</button>
			<button class="toggle-size" aria-label="Toggle tool size">_</button>
		</div>
	</div>
	<?php
	/*<div class="tabs">
		<div class="tab active" data-tab="events">Events</div>
		<div class="tab" data-tab="consent">Consent</div>
		<div class="tab" data-tab="cookies">Cookies</div>
		<div class="tab" data-tab="http">HTTP Calls</div>
	</div>*/
		?>
	 <div class="content">
		<div class="tab-content active" id="events">
			<div id="gtm-ecommerce-woo-event-inspector-list-template" style="display: none;">
				<li style="cursor: pointer;list-style: none;color: black;font-weight: bold;padding-top: 10px;"><span>{{event}}</span><pre style="display:none;"><code class="language-json">{{json}}</code></pre></li>
			</div>
			<ul id="gtm-ecommerce-woo-event-inspector-list"></ul>
		</div>
		<div class="tab-content" id="consent"></div>
		<div class="tab-content" id="cookies"></div>
		<div class="tab-content" id="http"></div>
	</div>
</div>
<?php
	}
}
