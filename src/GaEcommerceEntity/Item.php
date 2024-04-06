<?php

namespace GtmEcommerceWoo\Lib\GaEcommerceEntity;

class Item implements \JsonSerializable {

	public $itemId;
	public $itemName;
	public $itemBrand;
	public $itemCoupon;
	public $itemVariant;
	public $itemListName;
	public $itemListId;
	public $index;
	public $quantity;
	public $price;
	public $discount;
	public $extraProps = [];
	public $itemCategories = [];

	public function __construct( $itemName ) {
		$this->itemName = $itemName;
	}

	public function setItemName( $itemName ) {
		$this->itemName = $itemName;
	}

	public function setItemId( $itemId ) {
		$this->itemId = $itemId;
	}

	public function setPrice( $price ) {
		$this->price = (float) $price;
	}

	public function setDiscount( $discount ) {
		$this->discount = (float) $discount;
	}

	public function setItemBrand( $itemBrand ) {
		$this->itemBrand = $itemBrand;
	}

	public function setItemVariant( $itemVariant ) {
		$this->itemVariant = $itemVariant;
	}

	public function setItemCategories( $itemCategories ) {
		$this->itemCategories = $itemCategories;
	}

	public function addItemCategory( $itemCategory ) {
		$this->itemCategories[] = $itemCategory;
	}

	public function setItemCoupon( $itemCoupon ) {
		$this->itemCoupon = $itemCoupon;
	}

	public function setIndex( $index ) {
		$this->index = $index;

		return $this;
	}

	public function setItemListName( $itemListName ) {
		$this->itemListName = $itemListName;

		return $this;
	}

	public function setItemListId( $itemListId ) {
		$this->itemListId = $itemListId;

		return $this;
	}

	public function setQuantity( $quantity ) {
		$this->quantity = (int) $quantity;

		return $this;
	}

	public function setExtraProperty( $propName, $propValue ) {
		$this->extraProps[$propName] = $propValue;

		return $this;
	}

	public function jsonSerialize(): array {
		$jsonItem = [
			'item_name' => $this->itemName,
			'item_id' => $this->itemId,
			'price' => $this->price,
			'item_brand' => @$this->itemBrand,
			'item_coupon' => @$this->itemCoupon,
			'item_variant' => @$this->itemVariant,
			'item_list_name' => @$this->itemListName,
			'item_list_id' => @$this->itemListId,
			'index' => @$this->index,
			'quantity' => @$this->quantity,
		];

		if (null !== $this->discount && 0 < $this->discount) {
			$jsonItem['discount'] = $this->discount;
		}

		foreach ($this->itemCategories as $index => $category) {
			$categoryParam = 'item_category';
			if ($index > 0) {
				$categoryParam = sprintf('%s%d', $categoryParam, $index + 1);
			}
			$jsonItem[$categoryParam] = $category;
		}

		foreach ($this->extraProps as $propName => $propValue) {
			$jsonItem[$propName] = $propValue;
		}

		return array_filter($jsonItem, static function ( $value ) {
			return !is_null($value) && '' !== $value;
		});
	}
}
