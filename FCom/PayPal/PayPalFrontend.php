<?php

class FCom_PayPal_Frontend extends BClass
{
    static public function bootstrap()
    {
        BRouting::i()->get('/paypal/.action', 'FCom_PayPal_Frontend_Controller');

        BLayout::i()->addAllViews('Frontend/views');

        FCom_Checkout_Model_Cart::i()->addPaymentMethod('paypal', 'FCom_PayPal_Frontend');
    }

    public function getName()
    {
        return 'PayPal Express Checkout';
    }

    public function processPayment()
    {
        $href = BApp::href('paypal/redirect');
        BResponse::i()->redirect($href);
    }
}
