<?php defined('BUCKYBALL_ROOT_DIR') || die();

class FCom_Sales_Model_Order_Payment extends FCom_Core_Model_Abstract
{
    protected static $_table = 'fcom_sales_order_payment';
    protected static $_origClass = __CLASS__;

    public function onBeforeSave()
    {
        $currentDate = date("Y-m-d H:i:s");
        if (!$this->id()) {
            $this->set('create_at', $currentDate);
        }
        $this->set('update_at', $currentDate);
        return parent::onBeforeSave();
    }


    public function addNew($data)
    {
        $this->BEvents->fire(__CLASS__ . '.addNew', ['paymentData' => $data]);
        return $this->create($data);
    }

    /**
     * @param bool  $new
     * @param array $args
     * @return FCom_Sales_Model_Order_Payment
     */
    static public function i($new = false, array $args = [])
    {
        return BClassRegistry::instance(__CLASS__, $args, !$new);
    }
}
