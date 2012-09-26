<?php

class FCom_Customer_ApiServer_V1_Customer extends FCom_Admin_Controller_ApiServer_Abstract
{
    public function action_get()
    {
        $id = BRequest::i()->param('id');
        $len = BRequest::i()->get('len');
        if (!$len) {
            $len = 10;
        }
        $start = BRequest::i()->get('start');
        if (!$start) {
            $start = 0;
        }

        if ($id) {
            $customers[] = FCom_Customer_Model_Customer::load($id);
        } else {
            $customers = FCom_Customer_Model_Customer::orm()->limit($len, $start)->find_many();
        }
        if (empty($customers)) {
            $this->ok();
        }
        $result = FCom_Customer_Model_Customer::i()->prepareApiData($customers);
        $this->ok($result);
    }

    public function action_post()
    {
        $post = BUtil::fromJson(BRequest::i()->rawPost());

        if (empty($post['email'])) {
            $this->badRequest("Email is required");
        }
        if (empty($post['password'])) {
            $this->badRequest("Password is required");
        }
        if (empty($post['firstname'])) {
            $this->badRequest("Firstname is required");
        }
        if (empty($post['lastname'])) {
            $this->badRequest("Lastname is required");
        }


        $data = array();
        $data['email'] = $post['email'];
        $data['password'] = $post['password'];
        $data['firstname'] = $post['firstname'];
        $data['lastname'] = $post['lastname'];

        if (!empty($post['shipping_address'])) {
            $data['shipping_address_id'] = $post['shipping_address'];
        }
        if (!empty($post['billing_address_id'])) {
            $data['billing_address_id'] = $post['billing_address_id'];
        }

        $customer = FCom_Customer_Model_Customer::orm()->create($data)->save();

        if (!$customer) {
            $this->internalError("Can't create a customer");
        }

        $this->created(array('id' => $customer->id));
    }

    public function action_put()
    {
        $id = BRequest::i()->param('id');
        $post = BUtil::fromJson(BRequest::i()->rawPost());

        if (empty($id)) {
            $this->badRequest("Customer id is required");
        }

        $data = array();

        if (!empty($post['email'])) {
            $data['email'] = $post['email'];
        }
        if (!empty($post['password'])) {
            $data['password'] = $post['password'];
        }
        if (!empty($post['firstname'])) {
            $data['firstname'] = $post['firstname'];
        }
        if (!empty($post['lastname'])) {
            $data['lastname'] = $post['lastname'];
        }
        if (!empty($post['shipping_address'])) {
            $data['shipping_address_id'] = $post['shipping_address'];
        }
        if (!empty($post['billing_address_id'])) {
            $data['billing_address_id'] = $post['billing_address_id'];
        }


        $customer = FCom_Customer_Model_Customer::load($id);
        if (!$customer) {
            $this->notFound("Customer id #{$id} not found");
        }

        $customer->set($data)->save();
        $this->ok();
    }

    public function action_delete()
    {
        $id = BRequest::i()->param('id');

        if (empty($id)) {
            $this->notFound("Customer id is required");
        }

        $customer = FCom_Customer_Model_Customer::load($id);
        if (!$customer) {
            $this->notFound("Customer id #{$id} not found");
        }

        $customer->delete();
        $this->ok();
    }


}