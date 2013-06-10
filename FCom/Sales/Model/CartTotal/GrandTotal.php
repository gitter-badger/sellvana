<?php

class FCom_Sales_Model_CartTotal_GrandTotal extends FCom_Sales_Model_CartTotal_Abstract
{
    protected $_code = 'grandtotal';
    protected $_label = 'Grand Total';
    protected $_sortOrder = 90;

    public function calculate()
    {
        $cart = $this->_cart;
        $total = $cart->subtotal;
        $total += $cart->shipping_price;
        $total += $cart->tax_amount;
        $total -= $cart->discount_amount;
        $this->_value = $cart->grand_total = $total;
        return $this;
    }

    public function isHidden()
    {
        return false;
    }
}