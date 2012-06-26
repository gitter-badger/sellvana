<?php

class FCom_Checkout_Frontend_Controller_Checkout extends FCom_Frontend_Controller_Abstract
{
    public function action_checkout_login()
    {
        $layout = BLayout::i();
        $layout->view('breadcrumbs')->crumbs = array(array('label'=>'Home', 'href'=>  BApp::baseUrl()),
            array('label'=>'Login or guest checkout', 'active'=>true));
        $this->layout('/checkout/login');
        BResponse::i()->render();
    }

    public function action_checkout()
    {
        $layout = BLayout::i();
        $layout->view('breadcrumbs')->crumbs = array(array('label'=>'Home', 'href'=>  BApp::baseUrl()),
            array('label'=>'Checkout', 'active'=>true));

        $shipAddress = null;
        $billAddress = null;

        $user = false;
        if (BApp::m('FCom_Customer')) {
            $user = FCom_Customer_Model_Customer::sessionUser();
        }
        $guestCheckout = false;
        if (!$user) {
            $guestCheckout = true;
        }

        $cart = FCom_Checkout_Model_Cart::i()->sessionCart()->calcTotals();

        if ($cart->id()) {
            $shipAddress = FCom_Checkout_Model_Address::i()->getAddress($cart->id(), 'shipping');
            $billAddress = FCom_Checkout_Model_Address::i()->getAddress($cart->id(), 'billing');

            if ($user && !$shipAddress) {
                FCom_Checkout_Model_Address::i()->newShipping($cart->id(), $user->defaultShipping());
                $shipAddress = FCom_Checkout_Model_Address::i()->getAddress($cart->id(), 'shipping');
            }
            if ($user && !$billAddress) {
                FCom_Checkout_Model_Address::i()->newBilling($cart->id(), $user->defaultBilling());
                $billAddress = FCom_Checkout_Model_Address::i()->getAddress($cart->id(), 'billing');
            }
        }

        if (empty($shipAddress)) {
            $href = BApp::href('checkout/address?t=s');
            BResponse::i()->redirect($href);
        } elseif (empty($billAddress)) {
            $href = BApp::href('checkout/address?t=b');
            BResponse::i()->redirect($href);
        }

        if (empty($cart->payment_method) && $user) {
            $cart->payment_method = $user->getPaymentMethod();
        }

        if (empty($cart->payment_detials) && $user) {
            $cart->payment_detials = $user->getPaymentDetails();
        }
        //print_r($cart);exit;
        $cart->calculateTotals();


        $shippingMethods = FCom_Checkout_Model_Cart::i()->getShippingMethods();
        $paymentMethods = FCom_Checkout_Model_Cart::i()->getPaymentMethods();
        if (!empty($paymentMethods[$cart->payment_method])) {
            $layout->view('checkout/checkout')->paymentMethod = $cart->payment_method;
            $layout->view('checkout/checkout')->paymentClass = $paymentMethods[$cart->payment_method];
            $layout->view('checkout/checkout')->paymentDetails = BUtil::fromJson($cart->payment_detials);
        }

        $this->messages('checkout/checkout');
        $layout->view('checkout/checkout')->cart = $cart;
        $layout->view('checkout/checkout')->guest = $guestCheckout;
        $layout->view('checkout/checkout')->shippingAddress = FCom_Checkout_Model_Address::as_html($shipAddress);
        $layout->view('checkout/checkout')->billingAddress = FCom_Checkout_Model_Address::as_html($billAddress);
        $layout->view('checkout/checkout')->shippingMethods = $shippingMethods;

        $layout->view('checkout/checkout')->totals = $cart->getTotals();
        $this->layout('/checkout/checkout');
        BResponse::i()->render();
    }

    public function action_checkout_post()
    {
        $post = BRequest::i()->post();

        $cart = FCom_Checkout_Model_Cart::i()->sessionCart();

        if (!empty($post['shipping'])) {
            $shipping = explode(":", $post['shipping']);
            $cart->shipping_method = $shipping[0];
            $cart->shipping_service = $shipping[1];
            //$cart->shipping_price = FCom_Checkout_Model_Cart::i()->getShippingMethod($post['shipping_method'])->getPrice();
        }

        if (!empty($post['payment'])) {
            $cart->payment_details = BUtil::toJson($post['payment']);
            if (FCom_Customer_Model_Customer::isLoggedIn()) {
                $user = FCom_Customer_Model_Customer::sessionUser();
                $user->setPaymentDetails($post['payment']);
            }
        }
        if (!empty($post['discount_code'])) {
            $cart->discount_code = $post['discount_code'];
        }
        if (!empty($post['create_account'])) {
            $r = $post['account'];
            try {
                $customer = FCom_Customer_Model_Customer::i()->register($r);
                $cart->user_id = $customer->id();
                $cart->save();
            } catch (Exception $e) {
                //die($e->getMessage());
            }
            //$cart->discount_code = $post['discount_code'];
        }
        $cart->save();

        if (!empty($post['place_order'])) {
            //todo: create order
            //redirect to payment page
            $orderData = array();
            $orderData['cart_id'] = $cart->id();
            $orderData['user_id'] = $cart->user_id;
            $orderData['item_qty']  = $cart->item_qty;
            $orderData['subtotal']  = $cart->subtotal;
            $orderData['shipping_method'] = $cart->shipping_method;
            $orderData['shipping_service'] = $cart->shipping_service;
            $orderData['payment_method'] = $cart->payment_method;
            $orderData['payment_details'] = $cart->payment_details;
            $orderData['discount_code'] = $cart->discount_code;
            $orderData['tax'] = $cart->tax;
            $orderData['total_json'] = $cart->total_json;
            $orderData['balance'] = $cart->calc_balance;

            $salesOrder = FCom_Sales_Model_Order::i()->load($cart->id(), 'cart_id');
            if (!$salesOrder) {
                $salesOrder = FCom_Sales_Model_Order::i()->add($orderData);
            }

            foreach ($cart->items() as $item) {
                $testItem = FCom_Sales_Model_OrderItem::i()->isItemExist($salesOrder->id(), $item->product_id);
                if ($testItem) {
                    continue;
                }
                $product = FCom_Catalog_Model_Product::i()->load($item->product_id);
                if (!$product) {
                    continue;
                }
                $orderItem = array();
                $orderItem['order_id'] = $salesOrder->id();
                $orderItem['product_id'] = $item->product_id;
                $orderItem['qty'] = $item->qty;
                $orderItem['total'] = $item->price;
                $orderItem['product_info'] = BUtil::toJson($product->as_array());
                FCom_Sales_Model_OrderItem::i()->add($orderItem);
            }

            //Made payment
            $paymentMethods = FCom_Checkout_Model_Cart::i()->getPaymentMethods();
            if (is_object($paymentMethods[$cart->payment_method])) {
                $paymentMethods[$cart->payment_method]->processPayment();
            }

        }


        $href = BApp::href('checkout');
        BResponse::i()->redirect($href);
    }

    public function action_payment()
    {
        $layout = BLayout::i();
        $cart = FCom_Checkout_Model_Cart::i()->sessionCart();
        $paymentMethods = FCom_Checkout_Model_Cart::i()->getPaymentMethods();
        $layout->view('breadcrumbs')->crumbs = array(
            array('label'=>'Home', 'href'=>  BApp::baseUrl()),
            array('label'=>'Checkout', 'href'=>  BApp::href("checkout")),
            array('label'=>'Payment methods', 'active'=>true));
        $layout->view('checkout/payment')->payment_methods = $paymentMethods;
        $layout->view('checkout/payment')->cart = $cart;
        $this->layout('/checkout/payment');
        BResponse::i()->render();
    }

    public function action_payment_post()
    {
        $post = BRequest::i()->post();
        $cart = FCom_Checkout_Model_Cart::i()->sessionCart();

        if (!empty($post['payment_method'])) {
            $cart->payment_method = $post['payment_method'];
        }
        $cart->save();
        $href = BApp::href('checkout');
        BResponse::i()->redirect($href);
    }

    public function action_shipping()
    {
        $layout = BLayout::i();
        $layout->view('breadcrumbs')->crumbs = array(
            array('label'=>'Home', 'href'=>  BApp::baseUrl()),
            array('label'=>'Checkout', 'href'=>  BApp::href("checkout")),
            array('label'=>'Shipping address', 'active'=>true));
        $layout->view('checkout/shipping')->address = array();
        $layout->view('checkout/shipping')->methods = array();
        $this->layout('/checkout/shipping');
        BResponse::i()->render();
    }

    public function action_shipping_post()
    {
        $href = BApp::href('checkout/payment');
        BResponse::i()->redirect($href);
    }

    public function action_success()
    {
        $sData =& BSession::i()->dataToUpdate();
        if (empty($sData['last_order']['id'])) {
            BResponse::i()->redirect(BApp::href('checkout'));
        }
        $salesOrder = FCom_Sales_Model_Order::i()->load($sData['last_order']['id']);
        $salesOrder->paid();
        $this->view('breadcrumbs')->crumbs = array(
            array('label'=>'Home', 'href'=>  BApp::baseUrl()),
            array('label'=>'Confirmation', 'active'=>true));
        $this->view('checkout/success')->order = $salesOrder;
        $this->layout('/checkout/success');
        BResponse::i()->render();
    }
}