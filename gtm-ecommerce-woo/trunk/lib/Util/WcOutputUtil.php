<?php

namespace GtmEcommerceWoo\Lib\Util;

class WcOutputUtil {

    public function dataLayerPush($event) {
        $stringifiedEvent = json_encode($event);
        $scriptString = 'dataLayer.push('.$stringifiedEvent.');';
        wc_enqueue_js( $scriptString );
    }

    public function globalVariable($name, $value) {
        $stringifiedValue = json_encode($value);
        $scriptString = 'window.' . $name.' = '.$stringifiedValue.';';
        wc_enqueue_js( $scriptString );
    }

    public function script($script) {
        wc_enqueue_js($script);
    }
}