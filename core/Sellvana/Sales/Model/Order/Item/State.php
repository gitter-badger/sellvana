<?php defined('BUCKYBALL_ROOT_DIR') || die();

class Sellvana_Sales_Model_Order_Item_State extends FCom_Core_Model_Abstract_State_Context
{
    const OVERALL = 'overall',
        DELIVERY = 'delivery',
        PAYMENT = 'payment',
        RETURNS = 'return',
        REFUND = 'refund',
        CANCEL = 'cancel',
        CUSTOM = 'custom';

    protected $_stateLabels = [
        self::OVERALL => 'Overall',
        self::DELIVERY => 'Delivery',
        self::PAYMENT => 'Payment',
        self::RETURNS => 'Return',
        self::REFUND => 'Refund',
        self::CANCEL => 'Cancel',
        self::CUSTOM => 'Custom',
    ];

    /**
     * Order linked
     *
     * @var Sellvana_Sales_Model_Order_Item
     */
    protected $_model;

    /**
     * Default classes for each type of order item state
     *
     * @var array
     */
    static protected $_defaultStateClasses = [
        self::OVERALL => 'Sellvana_Sales_Model_Order_Item_State_Overall',
        self::DELIVERY => 'Sellvana_Sales_Model_Order_Item_State_Delivery',
        self::PAYMENT => 'Sellvana_Sales_Model_Order_Item_State_Payment',
        self::RETURNS => 'Sellvana_Sales_Model_Order_Item_State_Return',
        self::REFUND => 'Sellvana_Sales_Model_Order_Item_State_Refund',
        self::CANCEL => 'Sellvana_Sales_Model_Order_Item_State_Cancel',
        self::CUSTOM => 'Sellvana_Sales_Model_Order_Item_State_Custom',
    ];

    /**
     * @return Sellvana_Sales_Model_Order_Item_State_Overall
     * @throws BException
     */
    public function overall()
    {
        return $this->_getStateObject(self::OVERALL);
    }

    /**
     * @return Sellvana_Sales_Model_Order_Item_State_Delivery
     * @throws BException
     */
    public function delivery()
    {
        return $this->_getStateObject(self::DELIVERY);
    }

    /**
     * @return Sellvana_Sales_Model_Order_Item_State_Payment
     * @throws BException
     */
    public function payment()
    {
        return $this->_getStateObject(self::PAYMENT);
    }

    /**
     * @return Sellvana_Sales_Model_Order_Item_State_Return
     * @throws BException
     */
    public function returns()
    {
        return $this->_getStateObject(self::RETURNS);
    }


    /**
     * @return Sellvana_Sales_Model_Order_Item_State_Refund
     * @throws BException
     */
    public function refund()
    {
        return $this->_getStateObject(self::REFUND);
    }


    /**
     * @return Sellvana_Sales_Model_Order_Item_State_Cancel
     * @throws BException
     */
    public function cancel()
    {
        return $this->_getStateObject(self::CANCEL);
    }

    /**
     * @return Sellvana_Sales_Model_Order_Item_State_Custom
     * @throws BException
     */
    public function custom()
    {
        return $this->_getStateObject(self::CUSTOM);
    }

    public function setDefaultStates()
    {
        $this->overall()->setDefaultState();
        $this->delivery()->setDefaultState();
        $this->payment()->setDefaultState();
        $this->returns()->setDefaultState();
        $this->refund()->setDefaultState();
        $this->cancel()->setDefaultState();
        $this->custom()->setDefaultState();
        return $this;
    }
}
