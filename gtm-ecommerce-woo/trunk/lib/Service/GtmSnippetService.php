<?php

namespace GtmEcommerceWoo\Lib\Service;

/**
 * Logic to handle embedding GTM Snippet
 */
class GtmSnippetService {
	protected $wpSettingsUtil;

	public function __construct( $wpSettingsUtil) {
		$this->wpSettingsUtil = $wpSettingsUtil;
	}

	public function initialize() {
		if ($this->wpSettingsUtil->getOption('disabled') === '1') {
			return;
		}

		if (substr($this->wpSettingsUtil->getOption('gtm_snippet_prevent_load'), 0, 3) === 'yes') {
			return;
		}

		if ($this->wpSettingsUtil->getOption('gtm_snippet_head') !== false) {
			add_action( 'wp_head', [$this, 'headSnippet'], 0 );
		}

		if ($this->wpSettingsUtil->getOption('gtm_snippet_body') !== false) {
			add_action( 'wp_body_open', [$this, 'bodySnippet'], 0 );
		}
	}

	public function headSnippet() {
		echo $this->wpSettingsUtil->getOption('gtm_snippet_head') . "\n";
	}


	public function bodySnippet() {
		echo $this->wpSettingsUtil->getOption('gtm_snippet_body') . "\n";
	}


}
