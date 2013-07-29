<?php

class FCom_Sales_Model_Order extends FCom_Core_Model_Abstract
{
    protected static $_table = 'fcom_sales_order';
    protected static $_origClass = __CLASS__;

    /**
    * Fallback singleton/instance factory
    *
    * @param bool $new if true returns a new instance, otherwise singleton
    * @param array $args
    * @return FCom_Sales_Model_Order
    */
    public static function i($new=false, array $args=array())
    {
        return BClassRegistry::i()->instance(get_called_class(), $args, !$new);
    }

    public function billing()
    {
        return FCom_Sales_Model_Cart_Address::i()->orm('a')
                ->where('cart_id', $this->cart_id)->where('atype', 'billing')->find_one();
    }

    public function addNew($data)
    {
        $status = FCom_Sales_Model_Order_Status::i()->statusNew();
        $data['status'] = $status->name;
        $data['status_id'] = $status->id;
        BEvents::i()->fire(__CLASS__.'.addNew', array('order'=>$data));
        return $this->create($data)->save();
    }

    public function update($data)
    {
        BEvents::i()->fire(__CLASS__.'.update', array('order'=>$data));
        return $this->set($data)->save();
    }

    public function paid()
    {
        $status = FCom_Sales_Model_Order_Status::i()->statusPaid();
        $data = array();
        $data['status'] = $status->name;
        $data['status_id'] = $status->id;
        $data['purchased_dt'] = date("Y-m-d H:i:s");
        $this->set($data)->save();
    }

    public function pending()
    {
        $status = FCom_Sales_Model_Order_Status::i()->statusPending();
        $data = array();
        $data['status'] = $status->name;
        $data['status_id'] = $status->id;
        $this->set($data)->save();
    }

    public function status()
    {
        return FCom_Sales_Model_Order_Status::i()->orm()->where('id', $this->status_id)->find_one();
    }


    /**
     * Return total UNIQUE number of items in the order
     * @param boolean $assoc
     * @return array
     */
    public function items($assoc=true)
    {
        $this->items = FCom_Sales_Model_Order_Item::i()->orm()->where('order_id', $this->id)->find_many_assoc();
        return $assoc ? $this->items : array_values($this->items);
    }

    public function isOrderExists($productId, $customerID)
    {
        return $this->orm('o')->join('FCom_Sales_Model_Order_Item', array('o.id','=','oi.order_id'), 'oi')
                ->where("customer_id", $customerID)->where("product_id", $productId)->find_one();

    }

    public function addresses()
    {
        return FCom_Sales_Model_Order_Address::i()->orm('a')->where('order_id', $this->id)->find_many();
    }

    public function prepareApiData($orders, $includeItems=false)
    {
        $result = array();
        foreach($orders as $i => $order) {
            $result[$i] = array(
                'id'                => $order->id,
                'customer_id'      => $order->customer_id,
                'status'               => $order->status,
                'item_qty'             => $order->item_qty,
                'subtotal'               => $order->subtotal,
                'balance'            => $order->balance,
                'tax'       => $order->tax,
                'shipping_method' => $order->shipping_method,
                'shipping_service'       => $order->shipping_service,
                'payment_method'       => $order->payment_method,
                'coupon_code'       => $order->coupon_code
            );
            if ($includeItems) {
                $items = $order->items();
                foreach($items as $item) {
                    $result[$i]['items'][] = array(
                        'product_id'    => $item->product_id,
                        'qty'    => $item->qty,
                        'total'    => $item->total,
                        //get product info as object and prepare data for api
                        'product_info'    => FCom_Catalog_Model_Product::i()->prepareApiData(BUtil::fromJson($item->product_info, true)),
                    );
                }
            }
        }
        return $result;
    }

    public function formatApiPost($post)
    {
        $data = array();
        if (!empty($post['customer_id'])) {
            $data['customer_id'] = $post['customer_id'];
        }
        if (!empty($post['status'])) {
            $data['status'] = $post['status'];
        }
        if (!empty($post['item_qty'])) {
            $data['item_qty'] = $post['item_qty'];
        }
        if (!empty($post['subtotal'])) {
            $data['subtotal'] = $post['subtotal'];
        }
        if (!empty($post['balance'])) {
            $data['balance'] = $post['balance'];
        }
        if (!empty($post['tax'])) {
            $data['tax'] = $post['tax'];
        }
        if (!empty($post['shipping_method'])) {
            $data['shipping_method'] = $post['shipping_method'];
        }
        if (!empty($post['shipping_service'])) {
            $data['shipping_service'] = $post['shipping_service'];
        }
        if (!empty($post['payment_method'])) {
            $data['payment_method'] = $post['payment_method'];
        }
        if (!empty($post['coupon_code'])) {
            $data['coupon_code'] = $post['coupon_code'];
        }
        return $data;
    }

    /**
     * @param FCom_Sales_Model_Cart $cart
     * @param array $options
     * @return FCom_Sales_Model_Order
     */
    public static function createFromCart($cart, $options = array())
    {
        $cart->calculateTotals();
        $shippingMethod       = $cart->getShippingMethod();
        $shippingServiceTitle = '';
        if (is_object($shippingMethod)) {
            $shippingServiceTitle = $shippingMethod->getService($cart->shipping_service);
        }
        $orderData                           = array();
        $orderData['cart_id']                = $cart->id();
        $orderData['customer_id']            = $cart->customer_id;
        $orderData['item_qty']               = $cart->item_qty;
        $orderData['subtotal']               = $cart->subtotal;
        $orderData['shipping_method']        = $cart->shipping_method;
        $orderData['shipping_service']       = $cart->shipping_service;
        $orderData['shipping_service_title'] = $shippingServiceTitle;
        $orderData['payment_method']         = $cart->payment_method;
        $orderData['payment_details']        = $cart->payment_details;
        $orderData['coupon_code']            = $cart->coupon_code;
        $orderData['tax']                    = $cart->tax;
//        $orderData['total_json']             = $cart->total_json;
        $orderData['balance']                = $cart->grand_total; // this has been calculated in cart
        $orderData['gt_base']                = $cart->grand_total; // full grand total
        $orderData['created_dt']             = BDb::now();

        //create sales order
        $salesOrder = FCom_Sales_Model_Order::i()->load($cart->id(), 'cart_id');
        if ($salesOrder) {
            $salesOrder->update($orderData);
        } else {
            $salesOrder = FCom_Sales_Model_Order::i()->addNew($orderData);
        }
        //copy order items
        foreach ($cart->items() as $item) {
            if (!static::itemAllowed($options, $item)) {
                continue;
            }
            /* @var $item FCom_Sales_Model_Cart_Item */
            $product = FCom_Catalog_Model_Product::i()->load($item->product_id);
            if (!$product) {
                continue;
            }
            $orderItem                 = array();
            $orderItem['order_id']     = $salesOrder->id();
            $orderItem['product_id']   = $item->product_id;
            $orderItem['qty']          = $item->qty;
            $orderItem['total']        = $item->rowTotal();
            $orderItem['product_info'] = BUtil::toJson($product->as_array());

            $testItem = FCom_Sales_Model_Order_Item::i()->isItemExist($salesOrder->id(), $item->product_id);
            if ($testItem) {
                $testItem->update($orderItem);
            } else {
                FCom_Sales_Model_Order_Item::i()->addNew($orderItem);
            }
        }

        //copy addresses
        $shippingAddress = $cart->getAddressByType('shipping');
        if ($shippingAddress) {
            FCom_Sales_Model_Order_Address::i()->newAddress($salesOrder->id(), $shippingAddress);
        }
        $billingAddress = $cart->getAddressByType('billing');
        if ($billingAddress) {
            FCom_Sales_Model_Order_Address::i()->newAddress($salesOrder->id(), $billingAddress);
        }

        //Made payment
        $paymentMethod = $cart->getPaymentMethod();
        if (is_object($paymentMethod)) {
            $paymentMethod->payOnCheckout();
        }
        return $salesOrder;
    }

    protected static function itemAllowed($options, $item)
    {
        if(isset($options['items'])){
            foreach ($options['items'] as $i) {
                if($i['id'] == $item->id){
                    return true; // item id matches
                }
            }
            return false; // item is not with passed filter
        }

        return true; // no items filter passed
    }

}