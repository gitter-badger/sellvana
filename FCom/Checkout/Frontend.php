<?php defined('BUCKYBALL_ROOT_DIR') || die();

/**
 * Class FCom_Checkout_Frontend
 *
 * @property FCom_Sales_Main $FCom_Sales_Main
 * @property FCom_Sales_Model_Cart $FCom_Sales_Model_Cart
 */

class FCom_Checkout_Frontend extends BClass
{
    /**
     * Init cart after all modules are registered
     */
    public function initCartTotals()
    {
        $cart = $this->FCom_Sales_Model_Cart->sessionCart(true);
        if (false == $cart->items()) {
            return;
        }
        $this->FCom_Sales_Model_Cart->addTotalRow('subtotal', ['callback' => 'FCom_Sales_Model_Cart.subtotalCallback',
            'label' => 'Subtotal', 'after' => '']);
        if ($cart->shipping_method) {
            $shippingClass = $this->FCom_Sales_Main->getShippingMethodClassName($cart->shipping_method);
            $this->FCom_Sales_Model_Cart->addTotalRow('shipping', ['callback' => $shippingClass . '.getRateCallback',
                'label' => 'Shipping', 'after' => 'subtotal']);
        }
        if ($cart->coupon_code) {
            $this->FCom_Sales_Model_Cart->addTotalRow('discount', ['callback' => 'FCom_Sales_Model_Cart.discountCallback',
                'label' => 'Discount', 'after' => 'shipping']);
        }
    }
}

