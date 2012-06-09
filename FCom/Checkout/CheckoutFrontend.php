<?php

class FCom_Checkout_Frontend extends BClass
{
    static public function bootstrap()
    {

        BFrontController::i()
            ->route( 'GET /cart', 'FCom_Checkout_Frontend_Controller.cart')
            ->route('POST /cart', 'FCom_Checkout_Frontend_Controller.cart_post')

            //checkout
            ->route( 'GET /checkout', 'FCom_Checkout_Frontend_Controller_Checkout.checkout')
            ->route( 'POST /checkout', 'FCom_Checkout_Frontend_Controller_Checkout.checkout_post')

            //payment
            ->route( 'GET /checkout/payment', 'FCom_Checkout_Frontend_Controller_Checkout.payment')
            ->route( 'POST /checkout/payment', 'FCom_Checkout_Frontend_Controller_Checkout.payment_post')

            //payment
            ->route( 'GET /checkout/shipping', 'FCom_Checkout_Frontend_Controller_Checkout.shipping')
            ->route( 'POST /checkout/shipping', 'FCom_Checkout_Frontend_Controller_Checkout.shipping_post')

            //shipping address
            ->route( 'GET /checkout/address/shipping', 'FCom_Checkout_Frontend_Controller_Address.shipping')
            ->route('POST /checkout/address/shipping', 'FCom_Checkout_Frontend_Controller_Address.shipping_post')
            //billing address
            ->route( 'GET /checkout/address/billing', 'FCom_Checkout_Frontend_Controller_Address.billing')
            ->route('POST /checkout/address/billing', 'FCom_Checkout_Frontend_Controller_Address.billing_post')
        ;

        //merge cart sessions after user login
        BPubSub::i()->on('FCom_Customer_Model_Customer::login.after', 'FCom_Checkout_Model_Cart::userLogin');
        BPubSub::i()->on('FCom_Customer_Model_Customer::logout.before', 'FCom_Checkout_Model_Cart::userLogout');

        BLayout::i()->addAllViews('Frontend/views')
                ->afterTheme('FCom_Checkout_Frontend::layout');

        self::initCart();

    }
    static public function initCart()
    {
        $cart = FCom_Checkout_Model_Cart::sessionCart();
        $itemNum = 0;
        $itemPrice = 0;
        if($cart){
            $itemPrice = round($cart->subtotal,2);
            $itemNum = ceil($cart->item_num);
        }
        BLayout::i()->view('cart/header')->cartItemPrice = $itemPrice;
        BLayout::i()->view('cart/header')->cartItemNum = $itemNum;
    }

    static public function layout()
    {
        BLayout::i()->layout(array(
            'base'=>array(
                array('view', 'head', 'do'=>array(
                    array('js', '{FCom_Checkout}/Frontend/js/fcom.checkout.js'),
                )
            )),
            '/checkout/cart'=>array(
                array('layout', 'base'),
                array('hook', 'main', 'views'=>array('checkout/cart'))
            ),
            '/checkout/checkout'=>array(
                array('layout', 'base'),
                array('hook', 'main', 'views'=>array('checkout/checkout'))
            ),
            '/checkout/payment'=>array(
                array('layout', 'base'),
                array('hook', 'main', 'views'=>array('checkout/payment'))
            ),
            '/checkout/shipping'=>array(
                array('layout', 'base'),
                array('hook', 'main', 'views'=>array('checkout/shipping'))
            ),
            '/checkout/address/shipping'=>array(
                array('layout', 'base'),
                array('hook', 'main', 'views'=>array('checkout/address/shipping'))
            ),
            '/checkout/address/billing'=>array(
                array('layout', 'base'),
                array('hook', 'main', 'views'=>array('checkout/address/billing'))
            ),
        ));
    }

    public function getShippingMethods()
    {
        return array(new stdClass());
    }

}

