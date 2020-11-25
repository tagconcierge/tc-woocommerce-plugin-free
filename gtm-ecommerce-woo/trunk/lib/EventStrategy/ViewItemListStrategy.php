<?php

namespace GtmEcommerceWoo\Lib\EventStrategy;

use GtmEcommerceWoo\Lib\GaEcommerceEntity\Event;

/**
 * when a user sees a list of items/offerings
 */
class ViewItemListStrategy extends AbstractEventStrategy {

    public function defineActions() {
        return [
            'woocommerce_before_shop_loop' => [$this, 'beforeShopLoop'],
            'woocommerce_shop_loop' => [$this, 'shopLoop'],
            'woocommerce_after_shop_loop' => [$this, 'afterShopLoop']
        ];
    }


    public function beforeShopLoop() {
        $this->items = [];
        $this->index = 0;
        $this->itemListName = "test";
        $this->itemListId = "000";
    }

    public function shopLoop() {
        global $product;
        // var_dump("shopLoop", $product);
        $item = $this->wcTransformer->getItem($product);
        $item->setIndex($this->index);
        $item->setItemListName($this->itemListName);
        $item->setItemListId($this->itemListId);

        $this->items[] = $item;
        $this->index++;
    }

    public function afterShopLoop() {
        $event = new Event('view_item_list')
            ->setItems($this->items);
        $this->wcOutput->dataLayerPush($event);
    }
    // hook to woocommerce_shop_loop to build the list
    // woocommerce_after_shop_loop to drop it in the page

}