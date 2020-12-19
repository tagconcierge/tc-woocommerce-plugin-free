<?php

namespace GtmEcommerceWoo\Lib\GaEcommerceEntity;

class Event implements \JsonSerializable {

    protected $name;
    protected $items;

    public function __construct($name) {
        $this->name = $name;
    }

    public function setItems($items) {
        $this->items = array_values($items);
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

    public function setTransationId($transationId) {
        $this->transationId = $transationId;
        return $this;
    }

    public function setAffiliation($affiliation) {
        $this->affiliation = $affiliation;
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
        if ($this->name === "purchase") {
            $jsonEvent = [
                'event' => 'purchase',
                'ecommerce' => [
                    'purchase' => [
                        'transaction_id' => $this->transationId,
                        'affiliation' => $this->affiliation,
                        'value' => $this->value,
                        'tax' => $this->tax,
                        'shipping' => $this->shipping,
                        'currency' => $this->currency,
                        'coupon' => @$this->coupon,
                        'items' => $this->items
                    ]
                ]
            ];
        } else {
            $jsonEvent = [
                'event' => $this->name,
                'ecommerce' => [
                    'items' => $this->items,
                ]
            ];
        }

        return array_filter($jsonEvent, function($value) { return !is_null($value) && $value !== ''; });
    }
}