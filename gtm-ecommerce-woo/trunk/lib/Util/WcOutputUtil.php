<?php

namespace GtmEcommerceWoo\Lib\Util;

class WcOutputUtil {

    protected $scripts = [];

    protected $scriptFiles = [];

    public function __construct() {
        add_action( 'wp_footer', [$this, 'wpFooter'], 11 );
        add_action( 'wp_enqueue_scripts', [$this, 'wpEnqueueScripts'] );
    }

    public function wpFooter() {
        echo '<script type="text/javascript">';
        echo 'window.dataLayer = window.dataLayer || [];';
        echo "(function(dataLayer, jQuery) {\n";
        foreach ($this->scripts as $script) {
            echo $script . "\n";
        }
        echo '})(dataLayer, jQuery);';
        echo "</script>\n";
    }

    public function dataLayerPush($event) {
        $stringifiedEvent = json_encode($event);
        $scriptString = 'dataLayer.push('.$stringifiedEvent.');';
        $this->scripts[] = $scriptString;
    }

    public function globalVariable($name, $value) {
        $stringifiedValue = json_encode($value);
        $scriptString = 'var ' . $name.' = '.$stringifiedValue.';';
        $this->scripts[] = $scriptString;
    }

    public function script($script) {
        $this->scripts[] = $script;
    }

    public function scriptFile($scriptFileName, $scriptFileDeps = [], $scriptFileFooter = false) {
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
                plugins_url( 'js/' . $scriptFile['name'] . '.js', MAIN_FILE ),
                $scriptFile['deps'],
                '1.0.0',
                $scriptFile['in_footer']
            );
        }
    }
}