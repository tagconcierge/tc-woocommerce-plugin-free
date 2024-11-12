<?php

namespace GtmEcommerceWoo\Lib\Service;

use GtmEcommerceWoo\Lib\Util\WpSettingsUtil;

/**
 * Logic to handle embedding GTM Snippet
 */
class GtmSnippetService {
	const PRIORITY_BEFORE_GTM = 0;
	const PRIORITY_GTM = 1;
	const PRIORITY_AFTER_GTM = 2;

	protected $wpSettingsUtil;

	public function __construct( WpSettingsUtil $wpSettingsUtil) {
		$this->wpSettingsUtil = $wpSettingsUtil;
	}

	public function initialize() {
		if ($this->wpSettingsUtil->getOption('disabled') === '1') {
			return;
		}

		if (strpos($this->wpSettingsUtil->getOption('gtm_snippet_prevent_load'), 'yes') === 0) {
			return;
		}

		if ($this->wpSettingsUtil->getOption('gtm_snippet_head') !== false) {
			add_action( 'wp_head', [$this, 'headSnippet'], self::PRIORITY_GTM );
		}

		if ($this->wpSettingsUtil->getOption('gtm_snippet_body') !== false) {
			add_action( 'wp_body_open', [$this, 'bodySnippet'], self::PRIORITY_GTM );
		}
	}

	public function defaultConsentModeState() {

		$settings = array_reduce([
			'ad_storage',
			'ad_user_data',
			'ad_personalization',
			'analytics_storage',
			'wait_for_update',
			'region',
			'url_passthrough',
			'ads_data_redaction'
		], function( $agg, $settingName ) {
			$agg[$settingName] = $this->wpSettingsUtil->getOption('consent_mode_default_' . $settingName);
			return $agg;
		}, []);
		extract($settings);

		$consentJs = <<<END
<script>
window.dataLayer = window.dataLayer || [];
function gtag(){dataLayer.push(arguments);}
gtag('consent', 'default', {
  'ad_storage': '{$ad_storage}',
  'ad_user_data': '{$ad_user_data}',
  'ad_personalization': '{$ad_personalization}',
  'analytics_storage': '{$analytics_storage}'
});

END;

		if ($wait_for_update) {
			$consentJs .= "gtag('set', 'wait_for_update', $wait_for_update);\n";
		}

		if ($region) {
			$regions = explode(',', $region);
			$cleanedRegions = array_map(function( $r ) {
				return "'" . trim(str_replace("'", '', $r)) . "'";
			}, $regions);
			$regionsString = implode(',', $cleanedRegions);
			$consentJs .= "gtag('set', 'region', [$regionsString]);\n";
		}

		if ('1' === $url_passthrough) {
			$consentJs .= "gtag('set', 'url_passthrough', true);\n";
		}

		if ('1' === $ads_data_redaction) {
			$consentJs .= "gtag('set', 'ads_data_redaction', true);\n";
		}

		$consentJs .= "</script>\n";
		return $consentJs;
	}

	public function headSnippet() {

		if ('1' === $this->wpSettingsUtil->getOption('consent_mode_default_enabled')) {
			echo filter_var($this->defaultConsentModeState(), FILTER_FLAG_STRIP_BACKTICK);
		}

		$snippet = $this->wpSettingsUtil->getOption('gtm_snippet_head');

		if ('1' === $this->wpSettingsUtil->getOption('server_side_gtmjs_enable')) {

			$serverContainerUrl = $this->wpSettingsUtil->getOption('gtm_server_container_url');
			$domain = trim(str_replace('https://', '', $serverContainerUrl), '/');

			$snippet = str_replace('www.googletagmanager.com', $domain, $snippet);

		}

		echo filter_var($snippet, FILTER_FLAG_STRIP_BACKTICK) . "\n";
	}

	public function bodySnippet() {

		$snippet = $this->wpSettingsUtil->getOption('gtm_snippet_body');

		if ('1' === $this->wpSettingsUtil->getOption('server_side_gtmjs_enable')) {

			$serverContainerUrl = $this->wpSettingsUtil->getOption('gtm_server_container_url');
			$domain = trim(str_replace('https://', '', $serverContainerUrl), '/');

			$snippet = str_replace('www.googletagmanager.com', $domain, $snippet);

		}

		echo filter_var($snippet, FILTER_FLAG_STRIP_BACKTICK) . "\n";
	}
}
