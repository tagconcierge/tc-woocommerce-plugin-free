<?php

namespace GtmEcommerceWoo\Lib\GaEcommerceEntity;

class Item implements \JsonSerializable {

	public $itemName;
	public $itemBrand;
	public $itemCoupon;
	public $itemVariant;
	public $itemListName;
	public $itemListId;
	public $index;
	public $quantity;

	public function __construct( $itemName ) {
		$this->itemName = $itemName;
		$this->itemCategories = [];
		$this->extraProps = [];
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

	public function jsonSerialize() {
		$jsonItem = [
			'item_name' => $this->itemName,
			'item_id' => $this->itemId,
			'price' => $this->price,
			'item_brand' => @$this->itemBrand,
			// 'item_category': 'Apparel',
			// 'item_category_2': 'Mens',
			// 'item_category_3': 'Shirts',
			// 'item_category_4': 'Tshirts',
			'item_coupon' => @$this->itemCoupon,
			'item_variant' => @$this->itemVariant,
			'item_list_name' => @$this->itemListName,
			'item_list_id' => @$this->itemListId,
			'index' => @$this->index,
			'quantity' => @$this->quantity,
		];

		foreach ($this->itemCategories as $index => $category) {
			$categoryParam = 'item_category';
			if ($index > 0) {
				$categoryParam .= '_' . ( $index + 1 );
			}
			$jsonItem[$categoryParam] = $category;
		}

		foreach ($this->extraProps as $propName => $propValue) {
			$jsonItem[$propName] = $propValue;
		}

		return array_filter($jsonItem, function( $value) {
			return !is_null($value) && '' !== $value;
		});
	}
}
