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

	public function setItems( array $items ): Event {
		$this->items = array_values($items);
		return $this;
	}

	public function addItem( Item $item ): Event {
		$this->items[] = $item;
		return $this;
	}

	public function setCurrency( string $currency ): Event {
		$this->currency = $currency;
		return $this;
	}

	public function setTransactionId( $transactionId ): Event {
		$this->transactionId = $transactionId;
		return $this;
	}

	public function setAffiliation( string $affiliation ): Event {
		$this->affiliation = $affiliation;
		return $this;
	}

	public function setValue( float $value ): Event {
		$this->value = $value;
		return $this;
	}

	public function setTax( float $tax ): Event {
		$this->tax = $tax;
		return $this;
	}

	public function setShipping( float $shipping ): Event {
		$this->shipping = $shipping;
		return $this;
	}

	public function setCoupon( string $coupon ): Event {
		$this->coupon = $coupon;
		return $this;
	}

	public function setExtraProperty( string $propName, $propValue ): Event {
		$this->extraProps[$propName] = $propValue;
		return $this;
	}

	public function getValue(): float {
		if (null !== $this->value) {
			return $this->value;
		}

		if (!is_array($this->items) || count($this->items) === 0) {
			return 0;
		}

		$value = array_reduce($this->items, static function( $carry, $item ) {
			$itemPrice = $item->price ?? 0;
			$itemQuantity = $item->quantity ?? 1;
			return $carry + ( (float) $itemPrice * (float) $itemQuantity );
		}, 0);

		return round($value, 2);
	}

	public function jsonSerialize(): array {
		/**
		 * Applies middleware extending events with additional data.
		 *
		 * @since 1.10.7
		 */
		apply_filters('gtm_ecommerce_woo_event_middleware', $this);

		/**
		 * Allows to customize the ecommerce event properties.
		 *
		 * @since 1.10.0
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
					'currency' => $this->currency,
					'coupon' => $this->coupon,
					'items' => $this->items,
				]
			];

			if ('view_item_list' !== $this->name) {
				$jsonEvent['ecommerce']['value'] = $this->getValue();
			}
		}

		if (null === $this->coupon || true === empty($this->coupon)) {
			unset($jsonEvent['ecommerce']['coupon'], $jsonEvent['ecommerce']['purchase']['coupon']);
		}

		if (null === $this->currency || true === empty($this->currency)) {
			unset($jsonEvent['ecommerce']['currency']);
		}

		if (2 === count($jsonEvent['ecommerce']) && 0.0 === (float) $jsonEvent['ecommerce']['value'] && null === $jsonEvent['ecommerce']['items']) {
			unset($jsonEvent['ecommerce']);
		}

		foreach ($this->extraProps as $propName => $propValue) {
			$jsonEvent[$propName] = $propValue;
		}

		$result = array_filter($jsonEvent, static function( $value ) {
			return !is_null($value) && '' !== $value;
		});

		/**
		 * Allows to customize the ecommerce event properties after data processing.
		 *
		 * @since 1.10.12
		 */
		apply_filters('gtm_ecommerce_woo_event_after_processing', $result, $this);

		return $result;
	}
}
