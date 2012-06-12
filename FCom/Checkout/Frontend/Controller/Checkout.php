<?php

class FCom_Checkout_Frontend_Controller_Checkout extends FCom_Frontend_Controller_Abstract
{
    public function action_checkout()
    {
        $layout = BLayout::i();
        $layout->view('breadcrumbs')->crumbs = array('home', array('label'=>'Checkout', 'active'=>true));

        $shipAddress = null;
        $billAddress = null;

        $user = FCom_Customer_Model_Customer::sessionUser();
        $cart = FCom_Checkout_Model_Cart::i()->sessionCart()->calcTotals();

        if ($cart->id()) {
            $shipAddress = FCom_Checkout_Model_Address::i()->getAddress($cart->id(), 'shipping');
            $billAddress = FCom_Checkout_Model_Address::i()->getAddress($cart->id(), 'billing');

            if ($user && !$shipAddress) {
                //todo: create shipping & billing address entries from user address data
                $shipAddress = $user->defaultShipping();
            }
            if ($user && !$billAddress) {
                //todo: create shipping & billing address entries from user address data
                $billAddress = $user->defaultBilling();
            }
        }

        if (empty($shipAddress)) {
            $href = BApp::href('checkout/address?t=s');
            BResponse::i()->redirect($href);
        } elseif (empty($billAddress)) {
            $href = BApp::href('checkout/address?t=b');
            BResponse::i()->redirect($href);
        }

        $shippingMethods = FCom_Checkout_Api::i()->getShippingMethods();

        $layout->view('checkout/checkout')->cart = $cart;
        $layout->view('checkout/checkout')->shippingAddress = FCom_Checkout_Model_Address::as_html($shipAddress);
        $layout->view('checkout/checkout')->billingAddress = FCom_Checkout_Model_Address::as_html($billAddress);
        $layout->view('checkout/checkout')->shippingMethods = $shippingMethods;
        $this->layout('/checkout/checkout');
        BResponse::i()->render();
    }

    public function action_checkout_post()
    {
        $post = BRequest::i()->post();
        $cart = FCom_Checkout_Model_Cart::i()->sessionCart();

        if (!empty($post['shipping_method'])) {
            $cart->shipping_method = $post['shipping_method'];
            $cart->shipping_price = FCom_Checkout_Api::i()->getShippingMethod($post['shipping_method'])->getPrice();
        }

        if (!empty($post['payment'])) {
            $cart->payment_details = BUtil::toJson($post['payment']);
        }
        if (!empty($post['discount_code'])) {
            $cart->discount_code = $post['discount_code'];
        }
        $cart->save();
        $href = BApp::href('checkout');
        BResponse::i()->redirect($href);
    }

    public function action_payment()
    {
        $cart = FCom_Checkout_Model_Cart::i()->sessionCart();
        $layout = BLayout::i();
        $layout->view('breadcrumbs')->crumbs = array('home', array('label'=>'Payment methods', 'active'=>true));
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
        $layout->view('breadcrumbs')->crumbs = array('home', array('label'=>'Shipping', 'active'=>true));
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
}