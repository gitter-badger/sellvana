<?php

class FCom_Customer_Admin_Controller_Addresses extends FCom_Admin_Admin_Controller_Abstract_GridForm
{
    protected static $_origClass = __CLASS__;
    protected $_gridHref = 'addresses';
    protected $_modelClass = 'FCom_Customer_Model_Address';
    protected $_gridTitle = 'Addresses';
    protected $_recordName = 'Address';
    protected $_mainTableAlias = 'a';

    /**
     * config get all addresses of customer
     * @param $customer FCom_Customer_Model_Customer
     * @return array
     */
    public function getCustomerAddressesGridConfig($customer)
    {
        $config = parent::gridConfig();
        $config['id'] = 'customer_addresses_grid_' . $customer->id;
        $config['columns'] = array(
            array('cell' => 'select-row', 'headerCell' => 'select-all', 'width' => 40),
            array('name' => 'id', 'label' => 'ID', 'index' => 'a.id', 'width' => 80, 'hidden' => true),
            array('name' => 'email', 'label' => 'Email', 'index' => 'a.email', 'width' => 100, 'addable' => true, 'editable' => true),
            array('name' => 'firstname', 'label' => 'Firstname', 'index' => 'a.firstname', 'width' => 200, 'addable' => true, 'editable' => true),
            array('name' => 'lastname', 'label' => 'Lastname', 'index' => 'a.lastname', 'width' => 200, 'addable' => true, 'editable' => true),
            array('name' => 'company', 'label' => 'Company', 'index' => 'a.company', 'addable' => true, 'editable' => true),
            array('name' => 'street1', 'label' => 'Street1', 'index' => 'a.street1', 'width' => 200, 'addable' => true, 'editable' => true),
            array('name' => 'street2', 'label' => 'Street2', 'index' => 'a.street2', 'width' => 200, 'hidden' => true, 'addable' => true, 'editable' => true),
            array('name' => 'street3', 'label' => 'Street3', 'index' => 'a.street3', 'width' => 200, 'hidden' => true, 'addable' => true, 'editable' => true),
            array('name' => 'city', 'label' => 'City', 'index' => 'a.city', 'addable' => true, 'editable' => true),
            array('name' => 'region', 'label' => 'Region', 'index' => 'a.region', 'addable' => true, 'editable' => true),
            array('name' => 'postcode', 'label' => 'Postcode', 'index' => 'a.postcode', 'addable' => true, 'editable' => true),
            array('name' => 'phone', 'label' => 'Phone', 'index' => 'a.phone', 'addable' => true, 'editable' => true, 'hidden' => true),
            array('name' => 'fax', 'label' => 'Fax', 'index' => 'a.fax', 'addable' => true, 'editable' => true, 'hidden' => true),
            array('name' => 'country', 'label' => 'Country', 'index' => 'a.country', 'editor' => 'select', 'addable' => true,
                  'options' => FCom_Geo_Model_Country::i()->options(), 'editable' => true),
            array('name' => '_actions', 'label' => 'Actions', 'sortable' => false, 'width' => 115,
                  'data' => array('edit' => true, 'delete' => true)),
        );
        $config['actions'] = array(
            'new'    => array('caption' => 'Add New Address', 'modal' => true),
            'edit'   => true,
            'delete' => true
        );
        $config['filters'] = array(
            array('field' => 'country', 'type' => 'select'),
            array('field' => 'company', 'type' => 'text'),
            array('field' => 'postcode', 'type' => 'text'),
            array('field' => 'street1', 'type' => 'text'),
            array('field' => 'email', 'type' => 'text'),
            '_quick' => array('expr' => 'street1 like ? or company like ? or city like ? or country like ?', 'args' => array('%?%', '%?%', '%?%', '%?%'))
        );

        $config['orm'] = FCom_Customer_Model_Address::i()->orm($this->_mainTableAlias)->select($this->_mainTableAlias.'.*')->where('customer_id', $customer->id);

        return array('config' => $config);
    }
} 