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

		$this->wcOutputUtil->scriptFile('gtm-event-inspector', [], true);
		add_action( 'wp_footer', [$this, 'footerHtml'], 0 );
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
		<div id="gtm-ecommerce-woo-event-inspector"></div>
<?php
	}
}
