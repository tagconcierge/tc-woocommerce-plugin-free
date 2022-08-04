<?php

namespace GtmEcommerceWoo\Lib\GaEcommerceEntity;

class Event implements \JsonSerializable {

	public $name;
	public $items;
	public $extraProps;
	public $currency;
	public $transactionId;
	public $affiliation;
	public $value;
	public $tax;
	public $shipping;
	public $coupon;

	public function __construct( $name ) {
		$this->name = $name;
		$this->extraProps = [];
	}

	public function setItems( $items ) {
		$this->items = array_values($items);
		return $this;
	}

	public function addItem( $item ) {
		$this->items[] = $item;
		return $this;
	}

	public function setCurrency( $currency ) {
		$this->currency = $currency;
		return $this;
	}

	public function setTransactionId( $transactionId ) {
		$this->transactionId = $transactionId;
		return $this;
	}

	public function setAffiliation( $affiliation ) {
		$this->affiliation = $affiliation;
		return $this;
	}

	public function setValue( $value ) {
		$this->value = $value;
		return $this;
	}

	public function setTax( $tax ) {
		$this->tax = $tax;
		return $this;
	}

	public function setShipping( $shipping ) {
		$this->shipping = $shipping;
		return $this;
	}

	public function setCoupon( $coupon ) {
		$this->coupon = $coupon;
		return $this;
	}

	public function setExtraProperty( $propName, $propValue ) {
		$this->extraProps[$propName] = $propValue;
		return $this;
	}

	public function getValue() {
		if (!is_array($this->items) || count($this->items) === 0) {
			return 0;
		}
		return array_reduce($this->items, function( $carry, $item ) {
			$itemPrice = isset($item->price) ? $item->price : 0;
			$itemQuantity = isset($item->quantity) ? $item->quantity : 1;
			return $carry + ((float) $itemPrice * (float) $itemQuantity);
		}, 0);
	}

	public function jsonSerialize() {
		/**
		 * Allow to customize the ecommerce event properties
		 */
		apply_filters('gtm_ecommerce_woo_event', $this);

		if ('purchase' === $this->name) {
			$jsonEvent = [
				'event' => 'purchase',
				'ecommerce' => [
					// backwards compat
					'purchase' => [
						'transaction_id' => $this->transactionId,
						'affiliation' => $this->affiliation,
						'value' => $this->value,
						'tax' => $this->tax,
						'shipping' => $this->shipping,
						'currency' => $this->currency,
						'coupon' => @$this->coupon,
						'items' => $this->items
					],
					'transaction_id' => $this->transactionId,
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

		return array_filter($jsonEvent, function( $value ) {
			return !is_null($value) && '' !== $value;
		});
	}
}
