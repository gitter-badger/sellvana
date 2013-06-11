<?php

class FCom_Sales_Main extends BClass
{
    protected $_registry = array();
    protected $_heap = array();

    static public function bootstrap()
    {
        foreach (array('Subtotal', 'Shipping', 'Discount', 'GrandTotal') as $total) {
            FCom_Sales_Model_Cart::i()->registerTotalRowHandler('FCom_Sales_Model_Cart_Total_'.$total);
        }
    }

    public function addPaymentMethod($name, $class=null)
    {
        if (is_null($class)) $class = $name;
        $this->_registry['payment_method'][$name] = $class;
        return $this;
    }

    public function addCheckoutMethod($name, $class=null)
    {
        if (is_null($class)) $class = $name;
        $this->_registry['checkout_method'][$name] = $class;
        return $this;
    }

    public function addShippingMethod($name, $class=null)
    {
        if (is_null($class)) $class = $name;
        $this->_registry['shipping_method'][$name] = $class;
        return $this;
    }

    public function addDiscountMethod($name, $class=null)
    {
        if (is_null($class)) $class = $name;
        $this->_registry['discount_method'][$name] = $class;
        return $this;
    }

    public function getShippingMethodClassName($name)
    {
        return !empty($this->_registry['shipping_method'][$name]) ? $this->_registry['shipping_method'][$name] : null;
    }

    protected function _getHeap($type, $name=null)
    {
        if (empty($this->_heap[$type])) {
            foreach ($this->_registry[$type] as $n=>$class) {
                $this->_heap[$type][$n] = $class::i();
            }
            uasort($this->_heap[$type], function($a, $b) { return $a->getSortOrder() - $b->getSortOrder(); });
        }
        return is_null($name) ? $this->_heap[$type] :
            (!empty($this->_heap[$type][$name]) ? $this->_heap[$type][$name] : null);
    }

    public function getPaymentMethods()
    {
        return $this->_getHeap('payment_method');
    }

    public function getCheckoutMethods()
    {
        return $this->_getHeap('checkout_method');
    }

    public function getShippingMethods()
    {
        return $this->_getHeap('shipping_method');
    }

    public function getDiscountMethods()
    {
        return $this->_getHeap('discount_method');
    }
}

