<?php defined('BUCKYBALL_ROOT_DIR') || die();

class Sellvana_Sales_Model_Cart_State_Payment extends FCom_Core_Model_Abstract_State_Concrete
{
    const FREE = 'free',
        UNPAID = 'unpaid',
        EXTERNAL = 'external',
        ACCEPTED = 'accepted',
        FAILED = 'failed';

    protected $_valueLabels = [
        self::FREE => 'Free',
        self::UNPAID => 'Unpaid',
        self::EXTERNAL => 'External',
        self::ACCEPTED => 'Accepted',
        self::FAILED => 'Failed',
    ];

    public function setFree()
    {
        return $this->changeState(self::FREE);
    }

    public function setUnpaid()
    {
        return $this->changeState(self::UNPAID);
    }

    public function setExternal()
    {
        return $this->changeState(self::EXTERNAL);
    }

    public function setAccepted()
    {
        return $this->changeState(self::ACCEPTED);
    }

    public function setFailed()
    {
        return $this->changeState(self::FAILED);
    }
}