<?php

namespace GtmEcommerceWoo\Lib\EventStrategy;

use GtmEcommerceWoo\Lib\Util\WcOutputUtil;
use GtmEcommerceWoo\Lib\Util\WcTransformerUtil;

abstract class AbstractEventStrategy {

	/** @var string */
	protected $eventName;

	/** @var string */
	protected $eventType;

	/** @var WcTransformerUtil */
	protected $wcTransformer;

	/** @var WcOutputUtil */
	protected $wcOutput;

	/** @var array */
	protected $actions;

	public function __construct( $wcTransformer, $wcOutput ) {
		$this->wcTransformer = $wcTransformer;
		$this->wcOutput = $wcOutput;

		$this->actions = $this->defineActions();
		$this->initialize();
	}

	public function getActions() {
		return $this->actions;
	}

	public function getEventName() {
		return $this->eventName;
	}

	public function getEventType() {
		return $this->eventType;
	}

	abstract protected function defineActions();

	public function initialize() {}

}
