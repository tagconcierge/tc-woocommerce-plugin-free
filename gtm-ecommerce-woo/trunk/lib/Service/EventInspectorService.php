<?php

namespace GtmEcommerceWoo\Lib\Service;

/**
 * Logic to handle embedding Gtm Snippet
 */
class EventInspectorService {
    protected $wpSettingsUtil;

    public function __construct($wpSettingsUtil) {
        $this->wpSettingsUtil = $wpSettingsUtil;
    }

    public function initialize() {
    	// var_dump($this->wpSettingsUtil->getOption("event_inspector_enabled"));exit;
        if ($this->wpSettingsUtil->getOption("event_inspector_enabled") === false
        	|| $this->wpSettingsUtil->getOption("event_inspector_enabled") === 'no') {
            return;
        }

        if ($this->wpSettingsUtil->getOption("event_inspector_enabled") === 'yes-querystring') {
            if (!isset($_GET['gtm-inspector']) || $_GET['gtm-inspector'] !== "1") {
				return;
			}
        }

        add_action( 'wp_enqueue_scripts', [$this, "enqueueScript"], 0 );
        add_action( 'wp_footer', [$this, "footerHtml"], 0 );
    }

    public function isDisabled() {
    	if ($this->wpSettingsUtil->getOption("event_inspector_enabled") === 'yes-admin') {
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

    public function enqueueScript() {
    	if ($this->isDisabled()) {
    		return;
    	}
    	wp_enqueue_script( 'gtm-ecommerce-woo-event-inspector', plugin_dir_url( __DIR__ . '/../../../' ) . 'js/gtm-ecommerce-woo-event-inspector.js', array ( 'jquery' ), '1.0.3', false);
    }


    public function footerHtml() {
    	if ($this->isDisabled()) {
    		return;
    	}
        ?>
<div id="gtm-ecommerce-woo-event-inspector" style="position: fixed; bottom: 0; right: 0; left: 0; background-color: white;padding: 10px;text-align: center;border-top: 1px solid gray; max-height: 30%; overflow-y: scroll;">
    <div>Start shopping (add to cart, purchase) to see eCommerce events below, click event to see details.<br />Those events can be forwarded to number of tools in GTM. See <a href="https://handcraftbyte.com/gtm-ecommerce-for-woocommerce/#documentation" target="_blank">documentation</a> for details.</div>
    <div id="gtm-ecommerce-woo-event-inspector-list-template" style="display: none;">
        <li style="cursor: pointer;list-style: none;color: black;font-weight: bold;padding-top: 10px;">{{event}}</li>
    </div>
    <ul id="gtm-ecommerce-woo-event-inspector-list"></ul>
</div>
<?php
    }
}
