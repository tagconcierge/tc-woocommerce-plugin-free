<?php

namespace GtmEcommerceWoo\Lib\Util;

class WcOutputUtil {
	protected $pluginDir = __DIR__;
	protected $pluginVersion = '';
	protected $scripts = [];
	protected $scriptFiles = [];

	public function __construct( $pluginVersion) {
		$this->pluginVersion = $pluginVersion;
		add_action( 'wp_footer', [$this, 'wpFooter'], 20 );
		add_action( 'wp_enqueue_scripts', [$this, 'wpEnqueueScripts'] );
		add_filter( 'safe_style_css', function( $styles ) {
			$styles[] = 'display';

			return $styles;
		} );
	}

	public function wpFooter() {
		if (count($this->scripts) === 0) {
			echo '<!-- gtm-ecommerce-woo no-scripts -->';
			return;
		}
		echo "<script type=\"text/javascript\" data-gtm-ecommerce-woo-scripts>\n";
		echo "window.dataLayer = window.dataLayer || [];\n";
		echo "(function(dataLayer, jQuery) {\n";
		foreach ($this->scripts as $script) {
			echo filter_var($script, FILTER_FLAG_STRIP_BACKTICK) . "\n";
		}
		echo '})(dataLayer, jQuery);';
		echo "</script>\n";
	}

	public function dataLayerPush( $event) {
		$stringifiedEvent = json_encode($event);
		$this->scripts[] = 'dataLayer.push({ ecommerce: null });';
		$scriptString = 'dataLayer.push(' . $stringifiedEvent . ');';
		$this->scripts[] = $scriptString;
	}

	public function globalVariable( $name, $value) {
		$stringifiedValue = json_encode($value);
		$scriptString = 'var ' . $name . ' = ' . $stringifiedValue . ';';
		$this->scripts[] = $scriptString;
	}

	public function script( $script) {
		$this->scripts[] = $script;
	}

	public function scriptFile( $scriptFileName, $scriptFileDeps = [], $scriptFileFooter = false) {
		$this->scriptFiles[] = [
			'name' => $scriptFileName,
			'deps' => $scriptFileDeps,
			'in_footer' => $scriptFileFooter,
		];
	}

	public function wpEnqueueScripts() {
		foreach ($this->scriptFiles as $scriptFile) {
			wp_enqueue_script(
				$scriptFile['name'],
				plugin_dir_url( dirname( $this->pluginDir ) ) . 'assets/' . $scriptFile['name'] . '.js',
				$scriptFile['deps'],
				$this->pluginVersion,
				$scriptFile['in_footer']
			);
		}
	}
}
