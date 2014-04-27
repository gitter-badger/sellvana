<?php

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
        unset( $config[ 'form_url' ] );
        $config[ 'columns' ] = array(
            array( 'type' => 'row_select' ),
            array( 'name' => 'id', 'label' => 'ID', 'index' => 'e.id' ),
            array( 'type' => 'input', 'name' => 'email', 'label' => 'Email', 'index' => 'e.email', 'addable' => true, 'editable' => true,
                  'validation' => array( 'required' => true, 'unique' => BApp::href( 'subscriptions/unique' ) ) ),
            array( 'type' => 'input', 'name' => 'unsub_all', 'label' => 'Un-subscribe all', 'index' => 'e.unsub_all',
                  'addable' => true, 'editable' => true, 'mass-editable' => true, 'options' => array( '1' => 'Yes', '0' => 'No' ), 'editor' => 'select' ),
            array( 'type' => 'input', 'name' => 'sub_newsletter', 'label' => 'Subscribe newsletter', 'index' => 'e.sub_newsletter', 'addable' => true,
                  'editable' => true, 'mass-editable' => true, 'options' => array( '1' => 'Yes', '0' => 'No' ), 'editor' => 'select' ),
            array( 'name' => 'create_at', 'label' => 'Created', 'index' => 'e.create_at' ),
            array( 'type' => 'btn_group',
                  'buttons' => array(
                                        array( 'name' => 'edit' ),
                                        array( 'name' => 'delete' )
                                    )
                )
        );
        $config[ 'actions' ] = array(
//            'new' => array('caption' => 'New Email Subscription', 'modal' => true),
            'export' => true,
            'edit'   => true,
            'delete' => true
        );
        $config[ 'filters' ] = array(
            array( 'field' => 'email', 'type' => 'text' ),
            array( 'field' => 'sub_newsletter', 'type' => 'multiselect' ),
        );
        $config[ 'new_button' ] = '#add_new_email_subscription';
        return $config;
    }

    public function gridViewBefore( $args )
    {
        parent::gridViewBefore( $args );
        $this->view( 'admin/grid' )->set( array( 'actions' => array( 'new' => '<button type="button" id="add_new_email_subscription" class="btn grid-new btn-primary _modal">' . BLocale::_( 'New Email Subscription' ) . '</button>' ) ) );
    }

    public function action_unique__POST()
    {
        $post = BRequest::i()->post();
        $data = each( $post );
        $rows = BDb::many_as_array( FCom_Email_Model_Pref::i()->orm()->where( $data[ 'key' ], $data[ 'value' ] )->find_many() );
        BResponse::i()->json( array( 'unique' => empty( $rows ), 'id' => ( empty( $rows ) ? -1 : $rows[ 0 ][ 'id' ] ) ) );
    }
}
