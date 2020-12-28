<?php

namespace GtmEcommerceWoo\Lib\Util;

class WcOutputUtil {

    protected $scripts = [];

    public function __construct() {
        add_action( 'wp_footer', [$this, 'wpFooter'] );
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
}