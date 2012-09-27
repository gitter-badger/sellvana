<?php

class FCom_Catalog_ApiServer_V1_Category extends FCom_Admin_Controller_ApiServer_Abstract
{
    //protected $_authorizeActionsWhitelist = array('Put');

    public function action_index()
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
            $categories[] = FCom_Catalog_Model_Category::load($id);
        } else {
            $categories = FCom_Catalog_Model_Category::orm()->limit($len, $start)->find_many();
        }
        if (empty($categories)) {
            $this->ok();
        }
        $result = FCom_Catalog_Model_Category::i()->prepareApiData($categories);
        $this->ok($result);
    }

    public function action_index__post()
    {
        $post = BUtil::fromJson(BRequest::i()->rawPost());

        if (!empty($post['parent_id'])) {
            $category = FCom_Catalog_Model_Category::load($post['parent_id']);
        } else {
            $category = FCom_Catalog_Model_Category::orm()->where_null('parent_id')->find_one();
        }
        if (!$category) {
            $this->notFound("Parent category id #{$post['parent_id']} do not found");
        }

        $res = $category->createChild($post['name']);
        if (!$res) {
            $this->badRequest("Incorrect data provided");
        }
        $this->created(array('id' => $res->id));
    }

    public function action_index__put()
    {
        $id = BRequest::i()->param('id');
        $post = BUtil::fromJson(BRequest::i()->rawPost());

        if (empty($id)) {
            $this->notFound("Category id is required");
        }

        if (empty($post['action']) || !in_array($post['action'], array('move','rename'))) {
            $this->notFound("'action' parameter is missing. Allowed actions: move and rename");
        }

        $category = FCom_Catalog_Model_Category::load($id);
        if (!$category) {
            $this->notFound("Category id #{$id} not found");
        }

        if ('rename' == $post['action']) {
            if (empty($post['name'])) {
                $this->notFound("Rename category required a new 'name' parameter");
            }
            $category->rename($post['name']);
        } else if ('move' == $post['action']) {
            if (empty($post['parent_id'])) {
                $this->notFound("Parameter parent_id is required to move category");
            }
            try {
                $category->move($post['parent_id']);
            } catch (Exception $e) {
                $this->badRequest($e->getMessage());
            }
        }
        $category->cacheSaveDirty();
        $this->ok();
    }

    public function action_index__delete()
    {
        $id = BRequest::i()->param('id');

        if (empty($id)) {
            $this->notFound("Category id is required");
        }

        $category = FCom_Catalog_Model_Category::load($id);
        if (!$category) {
            $this->notFound("Category id #{$id} not found");
        }

        if ($id<2) {
            $this->badRequest("Can't remove root");
        }
        try {
            $category->delete();
        } catch (Exception $e) {
            $this->badRequest($e->getMessage());
        }
        $this->ok();
    }


}