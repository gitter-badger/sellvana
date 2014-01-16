<?php

class FCom_Blog_Admin_Controller_Category extends FCom_Admin_Admin_Controller_Abstract_GridForm
{
    protected static $_origClass = __CLASS__;
    protected $_gridHref = 'blog/category';
    protected $_modelClass = 'FCom_Blog_Model_Category';
    protected $_gridTitle = 'Blog Categories';
    protected $_recordName = 'Blog Category';
    protected $_permission = 'blog';
    protected $_mainTableAlias = 'c';

    public function gridConfig()
    {
        $config = parent::gridConfig();
        $config['columns'] = array(
            array('cell' => 'select-row', 'headerCell' => 'select-all', 'width' => 40),
            array('name' => 'id', 'label' => 'ID'),
            array('name' => 'name', 'label'=>'Name'),
            array('name' => 'description', 'label'=>'Description'),
            array('name' => 'url_key', 'label'=>'Url Key'),
            array('name' => '_actions', 'label' => 'Actions', 'sortable' => false,
                'data' => array('edit' => array('href' => BApp::href('blog/category/form/?id='), 'col'=>'id'),'delete' => true)),
        );
        $config['filters'] = array(
            array('field' => 'name', 'type' => 'text'),
            array('field' => 'url_key', 'type' => 'text'),
        );
        return $config;
    }

    public function formViewBefore($args)
    {
        parent::formViewBefore($args);
        $m = $args['model'];
        $args['view']->set(array(
                'title' => $m->id ? 'Edit Blog Category: '.$m->title : 'Create New Blog Category',
            ));
    }

    public function formPostAfter($args)
    {
        parent::formPostAfter($args);
        $cp = FCom_Blog_Model_CategoryPost::i();
        $model = $args['model'];
        $data = BRequest::i()->post();
        if (!empty($data['grid']['post_category']['del'])) {
            $cp->delete_many(array(
                    'category_id' => $model->id,
                    'post_id'=>explode(',', $data['grid']['post_category']['del']),
                ));
        }
        if (!empty($data['grid']['post_category']['add'])) {
            $oldPost = $cp->orm()->where('category_id', $model->id)->where('post_id', $model->id)
                ->find_many_assoc('post_id');
            foreach (explode(',', $data['grid']['post_category']['add']) as $postId) {
                if ($postId && empty($oldPost[$postId])) {
                    $m = $cp->create(array(
                            'category_id'=>$model->id,
                            'post_id'=>$postId,
                        ))->save();
                }
            }
        }
    }

    public function processFormTabs($view, $model = null, $mode = 'edit', $allowed = null)
    {
        if ($model && $model->id) {
            $view->addTab('post', array('label' => $this->_('Blog Posts'), 'pos' => 20));
        }
        return parent::processFormTabs($view, $model, $mode, $allowed);
    }
}
