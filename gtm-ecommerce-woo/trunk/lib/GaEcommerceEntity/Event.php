<?php

namespace GtmEcommerceWoo\Lib\GaEcommerceEntity;

class Event implements \JsonSerializable {

    protected $name;
    protected $items;

    public function __construct($name) {
        $this->name = $name;
    }

    public function setItems($items) {
        $this->items = $items;
        return $this;
    }

    public function addItem($item) {
        $this->items[] = $item;
        return $this;
    }

    public function setCurrency($Currency) {
        $this->currency = $Currency;
        return $this;
    }

    public function setTransationId($TransationId) {
        $this->transationId = $TransationId;
        return $this;
    }

    public function setAffiliation($Affiliation) {
        $this->affiliation = $Affiliation;
        return $this;
    }

    public function setValue($Value) {
        $this->value = $Value;
        return $this;
    }

    public function setTax($Tax) {
        $this->tax = $Tax;
        return $this;
    }

    public function setShipping($shipping) {
        $this->shipping = $shipping;
        return $this;
    }

    public function setCoupon($coupon) {
        $this->coupon = $coupon;
        return $this;
    }


    public function jsonSerialize() {
        return [
            'event' => $this->name,
            'ecommerce' => [
                'items' => $this->items,
            ]
        ];
    }
}