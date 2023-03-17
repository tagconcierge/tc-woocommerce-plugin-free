<?php

namespace GtmEcommerceWoo\Lib\Service;

use GtmEcommerceWoo\Lib\EventStrategy\AbstractEventStrategy;
use GtmEcommerceWoo\Lib\Util\WcOutputUtil;
use GtmEcommerceWoo\Lib\Util\WpSettingsUtil;

/**
 * General Logic of the plugin for loading and running each eCommerce event
 */
class EventStrategiesService {

	/** @var AbstractEventStrategy[] */
	protected $eventStrategies = [];

	/** @var WpSettingsUtil */
	protected $wpSettingsUtil;

	/** @var WcOutputUtil */
	protected $wcOutputUtil;

	/**
	 * @param WpSettingsUtil $wpSettingsUtil
	 * @param AbstractEventStrategy[] $eventStrategies
	 */
	public function __construct( WpSettingsUtil $wpSettingsUtil, WcOutputUtil $wcOutputUtil, array $eventStrategies) {
		$this->eventStrategies = $eventStrategies;
		$this->wpSettingsUtil = $wpSettingsUtil;
		$this->wcOutputUtil = $wcOutputUtil;
	}

	public function initialize() {
		if ($this->wpSettingsUtil->getOption('disabled') === '1') {
			return;
		}
		foreach ($this->eventStrategies as $eventStrategy) {
			$eventName = $eventStrategy->getEventName();

			if ('' === $this->wpSettingsUtil->getOption('event_server_' . $eventName) && 'server' === $eventStrategy->getEventType()) {
				continue;
			}

			if ('' === $this->wpSettingsUtil->getOption('event_' . $eventName) && 'server' !== $eventStrategy->getEventType()) {
				continue;
			}
			foreach ($eventStrategy->getActions() as $hook => $action) {
				if (is_array($action) && is_array($action[0]) && is_numeric($action[1])) {
					add_action( $hook, $action[0], $action[1], isset($action[2]) ? $action[2] : 1 );
				} else {
					add_action( $hook, $action );
				}
			}
		}
	}

}
