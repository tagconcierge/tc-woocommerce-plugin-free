<?php

namespace GtmEcommerceWoo\Lib\EventStrategy;

abstract class AbstractEventStrategy {

	protected $eventName;
	protected $eventType;
	protected $wcTransformer;
	protected $wcOutput;
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
