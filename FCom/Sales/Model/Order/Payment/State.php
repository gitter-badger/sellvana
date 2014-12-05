<?php defined('BUCKYBALL_ROOT_DIR') || die();

class FCom_Sales_Model_Order_Payment_State extends FCom_Core_Model_Abstract_State_Context
{
    /**
     * Order linked
     *
     * @var FCom_Sales_Model_Order_Payment
     */
    protected $_model;

    /**
     * Default classes for each type of payment state
     *
     * @var array
     */
    static protected $_defaultStateClasses = [
        'overall' => 'FCom_Sales_Model_Order_Payment_State_Overall',
        'processor' => 'FCom_Sales_Model_Order_Payment_State_Processor',
        'custom' => 'FCom_Sales_Model_Order_Payment_State_Custom',
    ];

    /**
     * @return FCom_Sales_Model_Order_Payment_State_Overall
     * @throws BException
     */
    public function overall()
    {
        return $this->_getStateObject('overall');
    }

    /**
     * @return FCom_Sales_Model_Order_Payment_State_Processor
     * @throws BException
     */
    public function processor()
    {
        return $this->_getStateObject('processor');
    }

    /**
     * @return FCom_Sales_Model_Order_Payment_State_Custom
     * @throws BException
     */
    public function custom()
    {
        return $this->_getStateObject('custom');
    }

}
