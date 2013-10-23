<?php

class FCom_Wishlist_Model_Wishlist extends FCom_Core_Model_Abstract
{
    protected static $_table = 'fcom_wishlist';
    protected static $_origClass = __CLASS__;

    protected $items = null;
    protected $wishlist = null;

    public function wishlist()
    {
        $customer = FCom_Customer_Model_Customer::i()->sessionUser();
        if (!$customer){
            return false;
        }
        if (!$this->wishlist) {
            $wishlist = static::i()->load(array("customer_id", $customer->id()));
            if (!$wishlist) {
                $this->orm()->create()->set("customer_id", $customer->id())->save();
                $wishlist = static::i()->load(array("customer_id", $customer->id));
            }

            $this->wishlist = $wishlist;
        }
        return $this->wishlist;
    }

    public function items($refresh=false)
    {
        if (!$this->items || $refresh) {
            $items = FCom_Wishlist_Model_WishlistItem::i()->orm()->where('wishlist_id', $this->id)->find_many_assoc();
            foreach($items as $ik => $item) {
                if (!$item->product()) {
                    $this->removeItem($item);
                    unset($items[$ik]);
                }
            }
            $this->items = $items;
        }
        return $this->items;
    }

    public function addItem($productId)
    {
        $item = FCom_Wishlist_Model_WishlistItem::i()->load(array('wishlist_id'=>$this->id, 'product_id'=>$productId));
        if (!$item) {
            $item = FCom_Wishlist_Model_WishlistItem::i()->orm()->create();
            $item->set('wishlist_id', $this->id())
                    ->set('product_id', $productId);
            $item->save();
        }

        return $this;
    }

    public function removeItem($item)
    {
        if (is_numeric($item)) {
            $this->items();
            $item = $this->childById('items', $item);
        }
        if ($item) {
            unset($this->items[$item->id()]);
            $item->delete();
        }
        return $this;
    }

    public function removeProduct($productId)
    {
        $this->removeItem($this->childById('items', $productId, 'product_id'));
        return $this;
    }
}
