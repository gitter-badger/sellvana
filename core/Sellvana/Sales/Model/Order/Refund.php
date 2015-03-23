<?php defined('BUCKYBALL_ROOT_DIR') || die();

/**
 * Class Sellvana_Sales_Model_Order_Refund
 *
 * @property FCom_Admin_Model_User $FCom_Admin_Model_User
 * @property Sellvana_Sales_Model_Order_History $Sellvana_Sales_Model_Order_History
 * @property Sellvana_Sales_Model_Order_Refund_State $Sellvana_Sales_Model_Order_Refund_State
 */

class Sellvana_Sales_Model_Order_Refund extends FCom_Core_Model_Abstract
{
    use Sellvana_Sales_Model_Trait_OrderChild;

    protected static $_table = 'fcom_sales_order_refund';
    protected static $_origClass = __CLASS__;

    protected $_state;

    /**
     * @return Sellvana_Sales_Model_Order_Refund_State
     */
    public function state()
    {
        if (!$this->_state) {
            $this->_state = $this->Sellvana_Sales_Model_Order_Refund_State->factory($this);
        }
        return $this->_state;
    }

    public function __destruct()
    {
        unset($this->_order, $this->_state);
    }
}