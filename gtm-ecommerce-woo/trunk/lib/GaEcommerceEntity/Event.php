<?php

namespace GtmEcommerceWoo\Lib\GaEcommerceEntity;

class Event implements \JsonSerializable {

	public $name;
	public $items;

	public function __construct( $name) {
		$this->name = $name;
		$this->extraProps = [];
	}

	public function setItems( $items) {
		$this->items = array_values($items);
		return $this;
	}

	public function addItem( $item) {
		$this->items[] = $item;
		return $this;
	}

	public function setCurrency( $Currency) {
		$this->currency = $Currency;
		return $this;
	}

	public function setTransationId( $transationId) {
		$this->transationId = $transationId;
		return $this;
	}

	public function setAffiliation( $affiliation) {
		$this->affiliation = $affiliation;
		return $this;
	}

	public function setValue( $Value) {
		$this->value = $Value;
		return $this;
	}

	public function setTax( $Tax) {
		$this->tax = $Tax;
		return $this;
	}

	public function setShipping( $shipping) {
		$this->shipping = $shipping;
		return $this;
	}

	public function setCoupon( $coupon) {
		$this->coupon = $coupon;
		return $this;
	}

	public function setExtraProperty( $propName, $propValue) {
		$this->extraProps[$propName] = $propValue;
		return $this;
	}

	public function getValue() {
		if (!is_array($this->items) || count($this->items) === 0) {
			return 0;
		}
		return array_reduce($this->items, function($carry, $item) {
			$itemPrice = isset($item->price) ? $item->price : 0;
			$itemQuantity = isset($item->quantity) ? $item->quantity : 1;
			return $carry + ($itemPrice * $itemQuantity);
		}, 0);
	}

	public function jsonSerialize() {
		apply_filters('gtm_ecommerce_woo_event', $this);

		if ('purchase' === $this->name) {
			$jsonEvent = [
				'event' => 'purchase',
				'ecommerce' => [
					// backwards compat
					'purchase' => [
						'transaction_id' => $this->transationId,
						'affiliation' => $this->affiliation,
						'value' => $this->value,
						'tax' => $this->tax,
						'shipping' => $this->shipping,
						'currency' => $this->currency,
						'coupon' => @$this->coupon,
						'items' => $this->items
					],
					'transaction_id' => $this->transationId,
					'affiliation' => $this->affiliation,
					'value' => $this->value,
					'tax' => $this->tax,
					'shipping' => $this->shipping,
					'currency' => $this->currency,
					'coupon' => @$this->coupon,
					'items' => $this->items
				]
			];
		} else {
			$jsonEvent = [
				'event' => $this->name,
				'ecommerce' => [
					'value' => $this->getValue(),
					'items' => $this->items,
				]
			];
		}

		foreach ($this->extraProps as $propName => $propValue) {
			$jsonEvent[$propName] = $propValue;
		}

		return array_filter($jsonEvent, function( $value) {
			return !is_null($value) && '' !== $value;
		});
	}
}
