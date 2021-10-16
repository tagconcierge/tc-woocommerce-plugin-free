<?php

namespace GtmEcommerceWoo\Lib\EventStrategy;

abstract class AbstractEventStrategy {

	protected $eventName;
	protected $wcTransformer;
	protected $wcOutput;
	protected $actions;

	public function __construct( $wcTransformer, $wcOutput) {
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


	abstract protected function defineActions();

	public function initialize() {}

}
