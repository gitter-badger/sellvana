<?php defined('BUCKYBALL_ROOT_DIR') || die();

class FCom_Sales_Model_Order_Item_State extends FCom_Core_Model_Abstract_State_Context
{
    /**
     * Order linked
     *
     * @var FCom_Sales_Model_Order_Item
     */
    protected $_model;

    /**
     * Default classes for each type of order item state
     *
     * @var array
     */
    static protected $_defaultStateClasses = [
        'overall' => 'FCom_Sales_Model_Order_Item_State_Overall',
        'delivery' => 'FCom_Sales_Model_Order_Item_State_Delivery',
        'payment' => 'FCom_Sales_Model_Order_Item_State_Payment',
        'custom' => 'FCom_Sales_Model_Order_Item_State_Custom',
    ];

    public function overall()
    {
        return $this->_getStateObject('overall');
    }

    public function delivery()
    {
        return $this->_getStateObject('delivery');
    }

    public function payment()
    {
        return $this->_getStateObject('payment');
    }

    public function custom()
    {
        return $this->_getStateObject('custom');
    }

}