<?php

class FCom_ApiTest_ApiServer_V1_Test extends FCom_Admin_Controller_ApiServer_Abstract
{
    protected $_authorizeActions = array('put');

    public function action_list()
    {
        $data = array('One', 'Two', 'Three');
        BResponse::i()->json($data);
    }

    public function action_put()
    {
        $data = array('One', 'Two', 'Three');
        BResponse::i()->json($data);
    }
}