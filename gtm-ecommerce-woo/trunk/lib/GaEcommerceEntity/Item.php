<?php

namespace GtmEcommerceWoo\Lib\GaEcommerceEntity;

class Item implements \JsonSerializable {

    protected $name;

    public function __construct($name) {
        $this->name = $name;
    }

    public function setItemName($itemName) {
        $this->itemName = $itemName;
    }

    public function setItemId($itemId) {
        $this->itemId = $itemId;
    }

    public function setPrice($price) {
        $this->price = $price;
    }

    public function setItemBrand($itemBrand) {
        $this->itemBrand = $itemBrand;
    }

    public function setItemCategory($itemCategory) {
        $this->itemCategory = $itemCategory;
    }

    public function setIndex($index) {
        $this->index = $index;
        return $this;
    }

    public function setItemListName($itemListName) {
        $this->itemListName = $itemListName;
        return $this;
    }

    public function setItemListId($itemListId) {
        $this->itemListId = $itemListId;
        return $this;
    }

    public function setQuantity($quantity) {
        $this->quantity = $quantity;
        return $this;
    }

    public function jsonSerialize() {
        return [
            'item_name' => $this->itemName,
            'item_id' => $this->itemId,
            'price' => $this->price,
            'index' => @$this->index,
        ];
    }
}