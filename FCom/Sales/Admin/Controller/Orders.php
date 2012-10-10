<?php

class FCom_Sales_Admin_Controller_Orders extends FCom_Admin_Controller_Abstract_GridForm
{
    protected static $_origClass = __CLASS__;
    protected $_gridHref = 'orders';
    protected $_modelClass = 'FCom_Sales_Model_Order';
    protected $_gridTitle = 'Orders';
    protected $_recordName = 'Order';
    protected $_mainTableAlias = 'o';

    public function gridConfig()
    {
        $config = parent::gridConfig();
        $config['grid']['columns'] = array_replace_recursive($config['grid']['columns'], array(
            'id' => array('index'=>'o.id', 'label' => 'Order id', 'width' =>70),
            'purchased_dt' => array('index'=>'o.purchased_dt', 'label' => 'Purchased on'),
            'billing_name' => array('label'=>'Bill to Name', 'index'=>'ab.billing_name'),
            'billing_address' => array('label'=>'Bill to Address', 'index'=>'ab.billing_address'),
            'shipping_name' => array('label'=>'Ship to Name', 'index'=>'as.shipping_name'),
            'shipping_address' => array('label'=>'Ship to Address', 'index'=>'as.shipping_address'),
            'gt_base' => array('label'=>'GT (base)', 'index'=>'o.gt_base'),
            'balance' => array('label'=>'GT (paid)', 'index'=>'o.balance'),
            'discount' => array('label'=>'Discount', 'index'=>'o.discount_code'),
            'status' => array('label'=>'Status', 'index'=>'o.status'),
        ));
        $config['custom']['dblClickHref'] = BApp::href('orders/form/?id=');

        return $config;
    }

    public function gridOrmConfig($orm)
    {
        parent::gridOrmConfig($orm);

        $orm->left_outer_join('FCom_Sales_Model_Address', 'o.id = ab.order_id and ab.atype="billing"', 'ab') //array('o.id','=','a.order_id')
            ->select_expr('CONCAT_WS(" ", ab.firstname,ab.lastname)','billing_name')
            ->select_expr('CONCAT_WS(" \n", ab.street1,ab.city,ab.country,ab.phone)','billing_address')
        ;
        $orm->left_outer_join('FCom_Sales_Model_Address', 'o.id = as.order_id and as.atype="shipping"', 'as') //array('o.id','=','a.order_id')
            ->select_expr('CONCAT_WS(" ", as.firstname,as.lastname)','shipping_name')
            ->select_expr('CONCAT_WS(" \n", as.street1,as.city,as.country,as.phone)','shipping_address')
        ;
    }

    public function gridViewBefore($args)
    {
        parent::gridViewBefore($args);
        $args['view']->set(array(
            'actions' => array(
                'new' => '',
            ),
        ));
    }

    public function action_form()
    {
        $orderId = BRequest::i()->params('id', true);
        $act = BRequest::i()->params('act', true);

        $order = FCom_Sales_Model_Order::load($orderId);
        $shipping = FCom_Sales_Model_Address::i()->findByOrder($orderId,'shipping');
        $billing = FCom_Sales_Model_Address::i()->findByOrder($orderId,'billing');
        if ($shipping) {
            $order->shipping_name = $shipping->firstname.' '.$shipping->lastname;
            $order->shipping_address = FCom_Sales_Model_Address::i()->as_html($shipping);
        }
        if ($billing) {
            $order->billing_name = $billing->firstname.' '.$billing->lastname;
            $order->billing_address = FCom_Sales_Model_Address::i()->as_html($billing);
        }

        if ($order->user_id) {
            $customer = FCom_Customer_Model_Customer::load($order->user_id);
            $customer->guest = false;
        } else {
            $customer = new stdClass();
            $customer->guest = true;
        }
        $order->items = $order->items();
        $order->customer = $customer;

        $model = $order;
        $model->act = $act;

        $view = $this->view($this->_formViewName)->set('model', $model);

        $this->formViewBefore(array('view'=>$view, 'model'=>$model));

        $this->layout($this->_formLayoutName);
        $this->processFormTabs($view, $model, 'edit');
    }

    public function formViewBefore($args)
    {
        $m = $args['model'];
        $act = $m->act;
        if ('edit' == $act) {
            $actions =array(
                'back' => '<button type="button" class="st3 sz2 btn" onclick="location.href=\''.BApp::href($this->_gridHref).'\'"><span>Back to list</span></button>',
                'delete' => '<button type="submit" class="st2 sz2 btn" name="do" value="DELETE" onclick="return confirm(\'Are you sure?\') && adminForm.delete(this)"><span>Delete</span></button>',
                'save' => '<button type="submit" class="st1 sz2 btn" onclick="return adminForm.saveAll(this)"><span>Save</span></button>',
            );
        } else {
            $actions =array(
                'back' => '<button type="button" class="st3 sz2 btn" onclick="location.href=\''.BApp::href($this->_gridHref).'\'"><span>Back to list</span></button>',
                'edit' => '<button type="button" class="st1 sz2 btn" onclick="location.href=\''.BApp::href('orders/form').'?id='.$m->id.'&act=edit'.'\'"><span>Edit</span></button>',
            );
        }
        $args['view']->set(array(
            'form_id' => BLocale::transliterate($this->_formLayoutName),
            'form_url' => BApp::href($this->_formHref).'?id='.$m->id,
            'actions' => $actions,
        ));
        BPubSub::i()->fire(static::$_origClass.'::formViewBefore', $args);
    }
}