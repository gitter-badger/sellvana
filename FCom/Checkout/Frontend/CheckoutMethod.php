<?php defined('BUCKYBALL_ROOT_DIR') || die();

/**
 * Class FCom_Checkout_Frontend_CheckoutMethod
 *
 * @property FCom_Customer_Model_Customer $FCom_Customer_Model_Customer
 */

class FCom_Checkout_Frontend_CheckoutMethod extends FCom_Sales_Method_Checkout_Abstract
{
    public function getCartCheckoutButton()
    {
        return [
            'href'  => $this->BApp->href($this->FCom_Customer_Model_Customer->isLoggedIn() ? 'checkout' : 'checkout/login'),
            'label' => 'Proceed to Checkout',
        ];
    }
}
