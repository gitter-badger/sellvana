<?php defined('BUCKYBALL_ROOT_DIR') || die();

class FCom_Sales_Model_Order_State_Overall extends FCom_Core_Model_Abstract_State_Concrete
{
    protected $_valueLabels = [
        'new' => 'New',
        'placed' => 'Placed',
        'review' => 'Review',
        'fraud' => 'Fraud',
        'legit' => 'Passed Verification',
        'processing' => 'Processing',
        'complete' => 'Complete',
        'cancel_req' => 'Cancel Requested',
        'canceled' => 'Canceled',
        'archived' => 'Archived',
    ];

    protected $_setValueNotificationTemplates =[
        'placed' => [
            'email/sales/order-state-overall-placed',
            'email/sales/order-state-overall-placed-admin',
        ],
        'review' => 'email/sales/order-state-overall-review',
        'fraud' => 'email/sales/order-state-overall-fraud',
        'legit' => 'email/sales/order-state-overall-legit',
        'cancel_req' => 'email/sales/order-state-overall-cancel_req-admin',
        'canceled' => 'email/sales/order-state-overall-canceled',
    ];

    public function setNew()
    {
        return $this->changeState('new');
    }

    public function setPlaced()
    {
        return $this->changeState('placed');
    }

    public function setReview()
    {
        return $this->changeState('review');
    }

    public function setLegit()
    {
        return $this->changeState('legit');
    }

    public function setFraud()
    {
        return $this->changeState('fraud');
    }

    public function setProcessing()
    {
        return $this->changeState('processing');
    }

    public function setComplete()
    {
        return $this->changeState('complete');
    }

    public function setCancelRequested()
    {
        return $this->changeState('cancel_req');
    }

    public function setCanceled()
    {
        return $this->changeState('canceled');
    }

    public function setArchived()
    {
        return $this->changeState('archived');
    }
}
