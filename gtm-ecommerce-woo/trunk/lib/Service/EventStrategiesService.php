<?php

namespace GtmEcommerceWoo\Lib\Service;

/**
 * General Logic of the plugin
 */
class EventStrategiesService {

    protected $eventStrategies = [];
    protected $wpSettingsUtil;

    public function __construct($wpSettingsUtil, $eventStrategies) {
        $this->eventStrategies = $eventStrategies;
        $this->wpSettingsUtil = $wpSettingsUtil;
    }

    public function initialize() {
        foreach ($this->eventStrategies as $eventStrategy) {
            foreach ($eventStrategy->getActions() as $hook => $action) {
                add_action( $hook, $action );
            }
        }
    }

}