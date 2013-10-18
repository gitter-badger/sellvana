<?php

class FCom_Customer_Admin_Controller_Customers extends FCom_Admin_Controller_Abstract_GridForm
{
    protected static $_origClass = __CLASS__;
    protected $_gridHref = 'customers';
    protected $_modelClass = 'FCom_Customer_Model_Customer';
    protected $_gridTitle = 'Customers';
    protected $_recordName = 'Customer';
    protected $_mainTableAlias = 'c';

    public function gridConfig()
    {
        $config = parent::gridConfig();
        $config['columns'] = array(
            'id' => array('index'=>'c.id'),
            'firstname' => array('label'=>'First Name', 'index'=>'c.firstname'),
            'lastname' => array('label'=>'Last Name', 'index'=>'c.lastname'),
            'email' => array('label'=>'Email', 'index'=>'c.email'),
            'street1' => array('label'=>'Address', 'index'=>'a.street1'),
            'city' => array('label'=>'City', 'index'=>'a.city'),
            'region' => array('label'=>'Region', 'index'=>'a.region'),
            'postcode' => array('label'=>'Postal Code', 'index'=>'a.postcode'),
            'country' => array('label'=>'Country', 'index'=>'a.country', 'options'=>FCom_Geo_Model_Country::i()->options()),
            'create_at' => array('label'=>'Created', 'index'=>'c.create_at'),
            'update_at' => array('label'=>'Updated', 'index'=>'c.update_at'),
        );
        $config['custom']['dblClickHref'] = BApp::href('customers/form/?id=');
        return $config;
    }

    public function gridOrmConfig($orm)
    {
        parent::gridOrmConfig($orm);

        $orm->left_outer_join('FCom_Customer_Model_Address', array('a.id','=','c.default_billing_id'), 'a')
            ->select(array('a.street1', 'a.city', 'a.region', 'a.postcode', 'a.country'))
        ;
    }

    public function formViewBefore($args)
    {
        parent::formViewBefore($args);
        $m = $args['model'];
        $args['view']->set(array(
            'sidebar_img' => BUtil::gravatar($m->email),
            'title' => $m->id ? 'Edit Customer: '.$m->firstname.' '.$m->lastname : 'Create New Customer',
        ));
    }

    public function formPostAfter($args)
    {
        parent::formPostAfter($args);
        if ($args['do']!=='DELETE') {
            $cust = $args['model'];
            $addrPost = BRequest::i()->post('address');
            if (($newData = BUtil::fromJson($addrPost['data_json']))) {
                $oldModels = FCom_Customer_Model_Address::i()->orm('a')->where('customer_id', $cust->id)->find_many_assoc();
                foreach ($newData as $id=>$data) {
                    if (empty($data['id'])) {
                        continue;
                    }
                    if (!empty($oldModels[$data['id']])) {
                        $addr = $oldModels[$data['id']];
                        $addr->set($data)->save();
                    } elseif ($data['id']<0) {
                        unset($data['id']);
                        $addr = FCom_Customer_Model_Address::i()->newBilling($data, $cust);
                    }
                }
            }
            if (($del = BUtil::fromJson($addrPost['del_json']))) {
                FCom_Customer_Model_Address::i()->delete_many(array('id'=>$del, 'customer_id'=>$cust->id));
            }
        }
    }
}
