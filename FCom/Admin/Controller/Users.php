<?php

class FCom_Admin_Controller_Users extends FCom_Admin_Controller_Abstract_GridForm
{
    protected static $_origClass = __CLASS__;
    protected $_permission = 'system/users';
    protected $_modelClass = 'FCom_Admin_Model_User';
    protected $_gridHref = 'users';
    protected $_gridTitle = 'Admin Users';
    protected $_recordName = 'User';
    protected $_mainTableAlias = 'au';

    public function gridConfig()
    {
        $config = parent::gridConfig();

        $config['columns'] = array(
            array('cell' => 'select-row', 'headerCell' => 'select-all', 'width' => 40),
            array('name' => 'id', 'label' => 'ID', 'index' => 'id', 'width' => 55, 'cell' => 'integer'),
            array('name' => 'username', 'label' => 'User Name', 'width' => 100, 'href' => BApp::href($this->_formHref.'?id=:id')),
            array('name' => 'email', 'label' => 'Email', 'width' => 150),
            array('name' => 'firstname', 'label' => 'First Name', 'width' => 150),
            array('name' => 'lastname', 'label' => 'Last Name', 'width' => 150),
            array('name' => 'is_superadmin', 'label' => 'SuperAdmin', 'width' => 100, 'cell' => 'integer', 'options' => FCom_Admin_Model_User::i()->fieldOptions('is_superadmin')),
            array('name' => 'status', 'label' => 'Status', 'width' => 100, 'cell' => 'integer', 'editor' => 'select', 'editable' => true, 'mass-editable' => true,
                  'options' => FCom_Admin_Model_User::i()->fieldOptions('status')),
            array('name' => '_actions', 'label' => 'Actions', 'sortable' => false, 'width' => 85,
                  'data'=> array('edit' => array('href' => BApp::href($this->_formHref.'?id='), 'col' => 'id'), 'delete' => true)),
        );
        $config['actions'] = array(
            'edit' => array('caption' => 'status'),
            'delete' => true,
        );
        $config['filters'] = array(
            array('field' => 'username', 'type' => 'text'),
            array('field' => 'email', 'type' => 'text'),
            array('field' => 'is_superadmin', 'type' => 'select'),
            array('field' => 'status', 'type' => 'select'),
        );

        return $config;
    }

    public function formViewBefore($args)
    {
        parent::formViewBefore($args);
        $m = $args['model'];
        $args['view']->set(array(
            'sidebar_img' => BUtil::gravatar($m->email),
            'title' => $m->id ? 'Edit User: '.$m->username : 'Create New User',
        ));
        $id = BRequest::i()->param('id', true);
        if($id){
            $args['view']->addTab("history", array('label' => BLocale::_("History"), 'pos' => 20));
        }
    }

    public function formPostBefore($args)
    {
        parent::formPostBefore($args);

        unset($args['data']['model']['password_hash']);
        if (!empty($args['data']['model']['password'])) {
            $args['model']->setPassword($args['data']['model']['password']);
        }
    }

    /**
     * get config for grid: users of role
     * @param $model FCom_Admin_Model_Role
     * @return array
     */
    public function getRoleUsersConfig($model)
    {
        $class = $this->_modelClass;
        $orm = $class::i()->orm()->table_alias('au')
            ->select(array('au.id', 'au.username', 'au.email', 'au.status'))
            ->join('FCom_Admin_Model_Role', array('au.role_id','=','ar.id'), 'ar')
            ->where('au.role_id', $model ? $model->id : 0);

        $config = parent::gridConfig();

        // TODO for empty local grid, it throws exception
        unset($config['orm']);
        $config['data'] = $orm->find_many();
        $config['id'] = 'role_users_grid_'.$model->id;
        $config['columns'] = array(
            array('cell'=>'select-row', 'headerCell'=>'select-all', 'width'=>40),
            array('name'=>'id', 'label'=>'ID', 'index'=>'au.id', 'width'=>80, 'hidden'=>true),
            array('name'=>'username', 'label'=>'Username', 'index'=>'au.username', 'width'=>200),
            array('name'=>'email', 'label'=>'Email', 'index'=>'au.email', 'width'=>200),
            array('name'=>'status', 'label'=>'Status', 'index'=>'au.status', 'width'=>200, 'editable' => true, 'mass-editable' => true,
                  'editor' => 'select', 'options' => FCom_Admin_Model_User::i()->fieldOptions('status'))
        );
        $config['actions'] = array(
            'add'=>array('caption'=>'Add user'),
        );
        $config['filters'] = array(
            array('field'=>'username', 'type'=>'text'),
            array('field'=>'email', 'type'=>'text'),
            array('field'=>'status', 'type'=>'select')
        );
        $config['data_mode'] = 'local';
        $config['events'] = array('init', 'add','mass-delete');

        return array('config'=>$config);
    }

    /**
     * get config for grid: all users
     * @param $model FCom_Admin_Model_Role
     * @param $filterAdmin bool
     * @return array
     */
    public function getAllUsersConfig($model, $filterAdmin = true)
    {
        $config            = parent::gridConfig();
        $config['id']      = 'role_all_users_grid_' . $model->id;
        $config['columns'] = array(
            array('cell' => 'select-row', 'headerCell' => 'select-all', 'width' => 40),
            array('name' => 'id', 'label' => 'ID', 'index' => 'au.id', 'width' => 55, 'hidden' => true),
            array('name' => 'username', 'label' => 'Name', 'index' => 'au.username', 'width' => 250),
            array('name' => 'email', 'label' => 'Local SKU', 'index' => 'au.email', 'width' => 100),
            array('name' => 'status', 'label' => 'Status', 'index' => 'au.status', 'width' => 100, 'editable' => true, 'mass-editable' => true,
                  'editor' => 'select', 'options' => FCom_Admin_Model_User::i()->fieldOptions('status'))
        );
        $config['actions'] = array(
            'add' => array('caption' => 'Add selected users')
        );
        $config['filters'] = array(
            array('field' => 'username', 'type' => 'text'),
            array('field' => 'email', 'type' => 'text'),
            array('field'=>'status', 'type'=>'select'),
            '_quick' => array('expr' => 'username like ? or email like ? or au.id=?', 'args' => array('?%', '%?%', '?'))
        );
        if ($filterAdmin) {
            $config['orm'] = FCom_Admin_Model_User::i()->orm()->where('is_superadmin', 0);
        }

        $config['events'] = array('add');

        return array('config' => $config);
    }
}
