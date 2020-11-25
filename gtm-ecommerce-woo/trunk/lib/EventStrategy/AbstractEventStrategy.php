<?php

namespace GtmEcommerceWoo\Lib\EventStrategy;

abstract class AbstractEventStrategy {

    protected $wcTransformer;
    protected $wcOutput;
    protected $actions;

    public function getActions() {
        return $this->actions;
    }

    public function __construct($wcTransformer, $wcOutput) {
        $this->wcTransformer = $wcTransformer;
        $this->wcOutput = $wcOutput;

        $this->actions = $this->defineActions();
    }

    abstract protected function defineActions();

}