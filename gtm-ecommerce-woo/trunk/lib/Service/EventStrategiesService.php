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
        if ($this->wpSettingsUtil->getOption("disabled") === '1') {
            return;
        }
        foreach ($this->eventStrategies as $eventStrategy) {
            foreach ($eventStrategy->getActions() as $hook => $action) {
                if (is_array($action) && is_array($action[0]) && is_numeric($action[1])) {
                    add_action( $hook, $action[0], $action[1] );
                } else {
                    add_action( $hook, $action );
                }
            }
        }
    }

}