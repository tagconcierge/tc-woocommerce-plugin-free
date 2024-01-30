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

	public function headSnippet() {
		echo filter_var($this->wpSettingsUtil->getOption('gtm_snippet_head'), FILTER_FLAG_STRIP_BACKTICK) . "\n";
	}

	public function bodySnippet() {
		echo filter_var($this->wpSettingsUtil->getOption('gtm_snippet_body'), FILTER_FLAG_STRIP_BACKTICK) . "\n";
	}
}
