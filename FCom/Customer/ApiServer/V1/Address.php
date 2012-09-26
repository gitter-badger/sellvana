<?php

class FCom_Customer_ApiServer_V1_Address extends FCom_Admin_Controller_ApiServer_Abstract
{
    public function action_get()
    {
        $id = BRequest::i()->param('id');
        $customerId = BRequest::i()->param('customer_id');
        $len = BRequest::i()->get('len');
        if (!$len) {
            $len = 10;
        }
        $start = BRequest::i()->get('start');
        if (!$start) {
            $start = 0;
        }

        if ($id) {
            $customerAddress[] = FCom_Customer_Model_Address::load($id);
        } else if($customerId) {
            $customerAddress = FCom_Customer_Model_Address::orm()->where('customer_id', $customerId)->limit($len, $start)->find_many();
        } else {
            $customerAddress = FCom_Customer_Model_Address::orm()->limit($len, $start)->find_many();
        }
        if (empty($customerAddress)) {
            $this->ok();
        }
        $result = FCom_Customer_Model_Address::i()->prepareApiData($customerAddress);
        $this->ok($result);
    }

    public function action_post()
    {
        $post = BUtil::fromJson(BRequest::i()->rawPost());

        if (empty($post['customer_id'])) {
            $this->badRequest("Customer id is required");
        }

        $data = FCom_Customer_Model_Address::i()->formatApiPost($post);
        $data['customer_id'] = $post['customer_id'];
        
        $address = FCom_Customer_Model_Address::orm()->create($data)->save();

        if (!$address) {
            $this->internalError("Can't create a customer address");
        }

        $this->created(array('id' => $address->id));
    }

    public function action_put()
    {
        $id = BRequest::i()->param('id');
        $post = BUtil::fromJson(BRequest::i()->rawPost());

        if (empty($id)) {
            $this->badRequest("Customer address id is required");
        }

        $data = FCom_Customer_Model_Address::i()->formatApiPost($post);

        $address = FCom_Customer_Model_Address::load($id);
        if (!$address) {
            $this->notFound("Customer address id #{$id} not found");
        }

        $address->set($data)->save();
        $this->ok();
    }

    public function action_delete()
    {
        $id = BRequest::i()->param('id');

        if (empty($id)) {
            $this->notFound("Customer address id is required");
        }

        $address = FCom_Customer_Model_Address::load($id);
        if (!$address) {
            $this->notFound("Customer address id #{$id} not found");
        }

        $address->delete();
        $this->ok();
    }


}