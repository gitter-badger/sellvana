<?php defined('BUCKYBALL_ROOT_DIR') || die();

class FCom_Email_Admin_Controller_Subscriptions extends FCom_Admin_Controller_Abstract_GridForm
{
    protected static $_origClass = __CLASS__;
    protected $_gridHref = 'subscriptions';
    protected $_modelClass = 'FCom_Email_Model_Pref';
    protected $_gridTitle = 'Subscriptions';
    protected $_recordName = 'Subscription';
    protected $_mainTableAlias = 'e';
    protected $_navPath = 'customer/subscriptions';
    protected $_permission = 'subscriptions';

    public function gridConfig()
    {
        $config            = parent::gridConfig();
        unset($config['form_url']);
        $config['columns'] = [
            ['type' => 'row_select'],
            ['name' => 'id', 'label' => 'ID', 'index' => 'e.id'],
            ['type' => 'input', 'name' => 'email', 'label' => 'Email', 'index' => 'e.email', 'addable' => true, 'editable' => true,
                  'validation' => ['required' => true, 'unique' => $this->BApp->href('subscriptions/unique')]],
            ['type' => 'input', 'name' => 'unsub_all', 'label' => 'Un-subscribe all', 'index' => 'e.unsub_all',
                  'addable' => true, 'editable' => true, 'mass-editable' => true, 'options' => ['1' => 'Yes', '0' => 'No'], 'editor' => 'select'],
            ['type' => 'input', 'name' => 'sub_newsletter', 'label' => 'Subscribe newsletter', 'index' => 'e.sub_newsletter', 'addable' => true,
                  'editable' => true, 'mass-editable' => true, 'options' => ['1' => 'Yes', '0' => 'No'], 'editor' => 'select'],
            ['name' => 'create_at', 'label' => 'Created', 'index' => 'e.create_at'],
            ['type' => 'btn_group',
                  'buttons' => [
                                        ['name' => 'edit'],
                                        ['name' => 'delete']
                                    ]
                ]
        ];
        $config['actions'] = [
//            'new' => array('caption' => 'New Email Subscription', 'modal' => true),
            'export' => true,
            'edit'   => true,
            'delete' => true
        ];
        $config['filters'] = [
            ['field' => 'email', 'type' => 'text'],
            ['field' => 'sub_newsletter', 'type' => 'multiselect'],
        ];
        $config['new_button'] = '#add_new_email_subscription';
        return $config;
    }

    public function gridViewBefore($args)
    {
        parent::gridViewBefore($args);
        $this->view('admin/grid')->set(['actions' => ['new' => '<button type="button" id="add_new_email_subscription" class="btn grid-new btn-primary _modal">' . BLocale::_('New Email Subscription') . '</button>']]);
    }

    public function action_unique__POST()
    {
        $post = $this->BRequest->post();
        $data = each($post);
        $rows = $this->BDb->many_as_array($this->FCom_Email_Model_Pref->orm()->where($data['key'], $data['value'])->find_many());
        $this->BResponse->json(['unique' => empty($rows), 'id' => (empty($rows) ? -1 : $rows[0]['id'])]);
    }
}
