<?php

class FCom_Promo_Frontend extends BClass
{
    static public function bootstrap()
    {
        //add product to cart
        BPubSub::i()->on('FCom_Checkout_Model_Cart::calcTotals', 'FCom_Promo_Frontend::onPromoCartValidate');
        BPubSub::i()->on('FCom_Checkout_Model_Cart::addProduct', 'FCom_Promo_Frontend::onPromoCartAddProduct');

        BPubSub::i()
            ->on('BLayout::hook.promotions', 'FCom_Promo_Frontend_Controller.hook_promotions')
        ;

        BFrontController::i()
            ->route( 'GET /promo/media', 'FCom_Promo_Frontend_Controller.media')
        ;

        BLayout::i()->addAllViews('Frontend/views');
        BPubSub::i()->on('BLayout::theme.load.after', 'FCom_Promo_Frontend::layout');

    }

    static public function layout()
    {
        BLayout::i()->layout(array(
            '/promo/media'=>array(
                array('hook', 'main', 'views'=>array('promo/media'))
            ),
        ));
    }

    public static function onPromoCartValidate($args)
    {
        $cart = $args['model'];

        $items = $cart->items();
        if (!$items) {
            $allCartPromo = FCom_Promo_Model_Cart::orm()->where('cart_id', $cart->id)->find_many();
            foreach($allCartPromo as $cartPromo) {
                $cartPromo->delete();
            }
            return;
        }

        $productIds = array();
        foreach($items as $item) {
            if ($item->promo_id_get) {
                continue;
            }
            $item->promo_qty_used = 0;
            $item->promo_id_buy = '';
            $item->save();
            $productIds[$item->product_id] = $item;
        }
        if (!$productIds) {
            return;
        }

        $activePromo = array();
        $activePromoIds = array();
        $promoList = FCom_Promo_Model_Promo::i()->getActive();
        if (!$promoList) {
            return;
        }

        foreach($promoList as $promo) {
            $promoProductsInGroup = FCom_Promo_Model_Product::orm()
                            ->where('promo_id', $promo->id)
                            ->where_in('product_id', array_keys($productIds))
                            ->find_many();

            if (!$promoProductsInGroup) {
                continue;
            }

            //BUY qty
            if ('qty' == $promo->buy_type) {
                //FROM Single group
                if ('one' == $promo->buy_group) {
                    $groupProducts = array();
                    $groupQty = array();
                    foreach($promoProductsInGroup as $product) {
                        if (!isset($groupProducts[$product->group_id])) {
                            $groupProducts[$product->group_id] = array();
                             $groupQty[$product->group_id] = 0;
                        }
                        if (!empty($productIds[$product->product_id])) {
                            $groupProducts[$product->group_id][] = $productIds[$product->product_id];
                            $groupQty[$product->group_id] += ($productIds[$product->product_id]->qty - $productIds[$product->product_id]->promo_qty_used);
                        }
                        if ($promo->buy_amount <= $groupQty[$product->group_id] ) {
                            //save how many
                            $activePromo[] = $promo;
                            $activePromoIds[] = $promo->id;
                            $promoBuyAmount = $promo->buy_amount;
                            foreach($groupProducts[$product->group_id] as $groupItem) {
                                if (!empty($groupItem->promo_id_buy)) {
                                    $promoIds = explode(",", $groupItem->promo_id_buy);
                                    if(!in_array($promo->id, $promoIds)){
                                        $promoIds[] = $promo->id;
                                    }
                                    $groupItem->promo_id_buy = implode(",", $promoIds);
                                } else {
                                    $groupItem->promo_id_buy = $promo->id;
                                }
                                if ($promoBuyAmount > 0) {
                                    $qtyUsed = $groupItem->qty - $promoBuyAmount;
                                    if ($qtyUsed <= 0) {
                                        $groupItem->promo_qty_used += $groupItem->qty;
                                    } else {
                                        $groupItem->promo_qty_used += $promoBuyAmount;
                                    }
                                    $promoBuyAmount -= $groupItem->qty;
                                }
                                $groupItem->save();
                            }

                            //only one promo per cart available
                            //break 2;
                        }
                    }
                }
                //FROM All Group
                if ('all' == $promo->buy_group) {
                    $groupItems = array();
                    $productQty = 0;
                    foreach($promoProductsInGroup as $product) {
                        if (!empty($productIds[$product->product_id])) {
                            $groupItems[] = $productIds[$product->product_id];
                            $productQty += ($productIds[$product->product_id]->qty - $productIds[$product->product_id]->promo_qty_used);
                        }
                        if ($promo->buy_amount <= $productQty ) {
                            $activePromo[] = $promo;
                            $activePromoIds[] = $promo->id;
                            $promoBuyAmount = $promo->buy_amount;
                            foreach($groupItems as $groupItem) {
                                if (!empty($groupItem->promo_id_buy)) {
                                    $promoIds = explode(",", $groupItem->promo_id_buy);
                                    if(!in_array($promo->id, $promoIds)){
                                        $promoIds[] = $promo->id;
                                    }
                                    $groupItem->promo_id_buy = implode(",", $promoIds);
                                } else {
                                    $groupItem->promo_id_buy = $promo->id;
                                }
                                if ($promoBuyAmount > 0) {
                                    $qtyUsed = $groupItem->qty - $promoBuyAmount;
                                    if ($qtyUsed <= 0) {
                                        $groupItem->promo_qty_used = $groupItem->qty;
                                    } else {
                                        $groupItem->promo_qty_used = $promoBuyAmount;
                                    }
                                    $promoBuyAmount -= $groupItem->qty;
                                }
                                $groupItem->save();
                            }
                            //only one promo per cart available
                            //break 2;
                        }
                    }
                }
            }
            if ('$' == $promo->buy_type) {
                if ('one' == $promo->buy_group) {
                    $groupProducts = array();
                    foreach($promoProductsInGroup as $product) {
                        if (!isset($groupProducts[$product->group_id])) {
                            $groupProducts[$product->group_id] = 0;
                        }
                        if (!empty($productIds[$product->product_id])) {
                            $groupProducts[$product->group_id] += $productIds[$product->product_id]->price*$productIds[$product->product_id]->qty;
                        }
                    }
                    foreach ($groupProducts as $productPrice) {
                        if ($promo->buy_amount <= $productPrice ) {
                            $activePromo[] = $promo;
                            $activePromoIds[] = $promo->id;
                        }
                    }
                }
                if ('all' == $promo->buy_group) {
                    $productPrice = 0;
                    foreach($promoProductsInGroup as $product) {
                        if (!empty($productIds[$product->product_id])) {
                            $productPrice += $productIds[$product->product_id]->price*$productIds[$product->product_id]->qty;
                        }
                    }

                    if ($promo->buy_amount <= $productPrice ) {
                        $activePromo[] = $promo;
                        $activePromoIds[] = $promo->id;
                    }
                }
            }
        }

        //check cart promo items
        $allCartItemPromo = FCom_Checkout_Model_CartItem::orm()->where('cart_id', $cart->id)->where_not_equal('promo_id_get', 0)->find_many();
        foreach($allCartItemPromo as $promoItem) {
            if (!in_array($promoItem->promo_id_get, $activePromoIds)) {
                $promoItem->delete();
            }
        }

        //check cart promos
        $allCartPromo = FCom_Promo_Model_Cart::orm()->where('cart_id', $cart->id)->find_many();
        foreach($allCartPromo as $cartPromo) {
            if (!in_array($cartPromo->promo_id, $activePromoIds)  || time() > strtotime($cartPromo->updated_dt) + 3600) {
                $cartPromo->delete();
            }
        }
        if (!empty($activePromo)) {
            foreach($activePromo as $promo) {
                $promoCart = FCom_Promo_Model_Cart::orm()->where('cart_id', $cart->id)
                        ->where('promo_id', $promo->id)
                    ->find_one();
                if (!$promoCart) {
                    $promoCart = FCom_Promo_Model_Cart::create(array('cart_id'=>$cart->id, 'promo_id'=>$promo->id));
                }
                $promoCart->set('updated_dt', date("Y-m-d H:i:s"));
                $promoCart->save();
            }
        }
    }

    public static function onPromoCartAddProduct($args)
    {
        $cart = $args['model'];
        $currentItem = $args['item'];
        if ($currentItem->promo_id_get) {
            return;
        }

        $items = $cart->items();
        if (!$items) {
            return;
        }

        $promoList = false;
        foreach($items as $item) {
            if (!$item->promo_id_buy) {
                continue;
            }
            if ($item->qty - $item->promo_qty_used == 0) {
                continue;
            }
            $promoIds = explode(",", $item->promo_id_buy);
            foreach($promoIds as $promoId) {
                $promoList[$promoId] = FCom_Promo_Model_Promo::load($promoId);
            }
        }

        if (!$promoList) {
            return;
        }

        foreach($promoList as $promo) {
            //GET QTY
            if ($promo->get_type == 'qty') {
                //FROM Any Group
                if ($promo->get_group == 'any_group') {
                    $promoItemQtyTotal = 0;
                    foreach($items as $item) {
                        if ($item->promo_id_get == $promo->id) {
                            $promoItemQtyTotal += $item->qty;
                        }
                    }

                    $item = FCom_Checkout_Model_CartItem::load(array('cart_id'=>$cart->id, 'product_id'=>$currentItem->product_id, 'promo_id_get' => $promo->id));

                    //IF GET QTY < Item Qty then add 1
                    if ($item && $promo->get_amount > $promoItemQtyTotal) {
                        $item->qty += 1;
                    } elseif (!$item) {
                        //if it is single item of product then mark it as promo
                        if ($currentItem->qty == 1) {
                            $item = $currentItem;
                            $item->promo_id_get = $promo->id;
                            $item->promo_id_buy = '';
                            $item->price = 0;
                        } else {
                            //if not then add new promo item and decrase qty of current item
                            $item = FCom_Checkout_Model_CartItem::create(array('cart_id'=>$cart->id, 'product_id'=>$currentItem->product_id,
                                'qty'=>1, 'price' => 0, 'promo_id_get' => $promo->id));

                            $currentItem->qty -= 1;
                            $currentItem->save();
                        }
                    } else {
                        continue;
                    }
                    $item->save();
                }
                //FROM Same Group
                if ($promo->get_group == 'same_group') {

                    $promoItemQtyTotal = 0;
                    foreach($items as $item) {
                        if ($item->promo_id_get == $promo->id) {
                            $promoItemQtyTotal += $item->qty;
                        }
                    }

                    $productId = $currentItem->product_id;

                    $groupProduct = FCom_Promo_Model_Product::orm()->where('promo_id', $promo->id())
                            ->where('product_id', $productId)->find_one();
                    if (!$groupProduct) {
                        continue;
                    }
                    $sameGroup = false;
                    foreach($items as $item) {
                        if ($item->promo_id_get) {
                            continue;
                        }
                        $groupProductItem = FCom_Promo_Model_Product::orm()->where('promo_id', $promo->id())
                            ->where('product_id', $item->product_id)
                                ->where('group_id', $groupProduct->group_id)->find_one();
                        if ($groupProductItem) {
                            $sameGroup = true;
                            break;
                        }
                    }
                    if ($sameGroup) {
                         $item = FCom_Checkout_Model_CartItem::load(array('cart_id'=>$cart->id, 'product_id'=>$currentItem->product_id, 'promo_id_get' => $promo->id));

                        //IF GET QTY < Item Qty then add 1
                        if ($item && $promo->get_amount > $promoItemQtyTotal) {
                            $item->qty += 1;
                        } elseif (!$item) {
                            //if it is single item of product then mark it as promo
                            if ($currentItem->qty == 1) {
                                $item = $currentItem;
                                $item->promo_id_get = $promo->id;
                                $item->promo_id_buy = '';
                                $item->price = 0;
                            } else {
                                //if not then add new promo item and decrase qty of current item
                                $item = FCom_Checkout_Model_CartItem::create(array('cart_id'=>$cart->id, 'product_id'=>$currentItem->product_id,
                                    'qty'=>1, 'price' => 0, 'promo_id_get' => $promo->id));

                                $currentItem->qty -= 1;
                                $currentItem->save();
                            }
                        } else {
                            continue;
                        }
                        $item->save();
                    }

                }
                if ($promo->get_group == 'same_prod') {
                    $promoItemQtyTotal = 0;
                    foreach($items as $item) {
                        if ($item->promo_id_get == $promo->id) {
                            $promoItemQtyTotal += $item->qty;
                        }
                    }

                    if ($currentItem->qty > 1 && $promo->get_amount > $promoItemQtyTotal) {
                         $item = FCom_Checkout_Model_CartItem::load(array('cart_id'=>$cart->id, 'product_id'=>$currentItem->product_id, 'promo_id_get' => $promo->id));

                        //IF GET QTY < Item Qty then add 1
                        if ($item) {
                            $item->qty += 1;
                        } elseif (!$item) {
                            //if it is single item of product then mark it as promo
                            if ($currentItem->qty == 1) {
                                $item = $currentItem;
                                $item->promo_id_get = $promo->id;
                                $item->promo_id_buy = '';
                                $item->price = 0;
                            } else {
                                //file_put_contents("/tmp/data",print_r($currentItem,1));exit;
                                //if not then add new promo item and decrase qty of current item
                                $item = FCom_Checkout_Model_CartItem::create(array('cart_id'=>$cart->id, 'product_id'=>$currentItem->product_id,
                                    'qty'=>1, 'price' => 0, 'promo_id_get' => $promo->id));

                                $currentItem->qty -= 1;
                                $currentItem->save();
                            }
                        } else {
                            continue;
                        }
                        $item->save();
                    }
                }
            }
        }
    }

}
