<?php

class FCom_Cms_Admin_Controller_Blocks extends FCom_Admin_Controller_Abstract_GridForm
{
    protected static $_origClass = __CLASS__;
    protected $_gridHref = 'cms/blocks';
    protected $_modelClass = 'FCom_Cms_Model_Block';
    protected $_gridTitle = 'CMS Block';
    protected $_recordName = 'CMS Block';

    public function gridConfig()
    {
        $config = parent::gridConfig();
        $config[ 'columns' ] = [
            [ 'type' => 'row_select' ],
            [ 'name' => 'handle', 'label' => 'Handle' ],
            [ 'name' => 'description', 'label' => 'Description', 'editable' => true ],
            [ 'type' => 'input', 'name' => 'renderer', 'label' => 'Renderer',
                  'options' => BLayout::i()->getAllRenderers( true ), 'editable' => true, 'mass-editable' => true, 'editor' => 'select' ],
            [ 'name' => 'version', 'label' => 'Version' ],
            [ 'type' => 'input', 'name' => 'page_enabled', 'label' => 'Page Enable',
                  'options' => [ '1' => 'Yes', '0' => 'No' ], 'editable' => true, 'mass-editable' => true, 'editor' => 'select' ],
            [ 'name' => 'page_url', 'label' => 'Page Url' ],
            [ 'name' => 'page_title', 'label' => 'Page Title' ],
            [ 'name' => 'meta_title', 'label' => 'Meta Title', 'hidden' => true ],
            [ 'name' => 'meta_description', 'label' => 'Meta Description', 'hidden' => true ],
            [ 'name' => 'meta_keywords', 'label' => 'Meta Keywords', 'hidden' => true ],
            [ 'name' => 'modified_time', 'label' => 'Modified Time', 'hidden' => true ],
            [ 'type' => 'btn_group', 'buttons' => [
                [ 'name' => 'edit' ],
                [ 'name' => 'delete' ],
            ] ],
        ];
        $config[ 'actions' ] = [
            'edit' => true,
            'delete' => true
        ];
        $config[ 'filters' ] = [
            [ 'field' => 'handle', 'type' => 'text' ],
            [ 'field' => 'page_enabled', 'type' => 'multiselect' ],
        ];
        return $config;
    }

    public function formViewBefore( $args )
    {
        parent::formViewBefore( $args );
        $m = $args[ 'model' ];
        $args[ 'view' ]->set( [
            'title' => $m->id ? 'Edit CMS Block: ' . $m->handle : 'Create New CMS Block',
        ] );
    }

    public function historyGridConfig( $m )
    {
        return [
            'grid' => [
                'id' => 'cms_blocks_form_history',
                'url' => BApp::href( 'cms/blocks/history/' . $m->id . '/grid_data' ),
                'editurl' => BApp::href( 'cms/blocks/history/' . $m->id . '/grid_data' ),
                'columns' => [
                    'id' => [ 'label' => 'ID', 'hidden' => true ],
                    'ts' => [ 'label' => 'TimeStamp', 'formatter' => 'date' ],
                    'version' => [ 'label' => 'Version' ],
                    'user_id' => [ 'type' => 'input', 'label' => 'User', 'editor' => 'select', 'options' => FCom_Admin_Model_User::i()->options() ],
                    'username' => [ 'Label' => 'User Name', 'hidden' => true ],
                    'comments' => [ 'labl' => 'Comments' ],
                ],
            ],
            'custom' => [ 'personalize' => true ],
            'filterToolbar' => [ 'stringResult' => true, 'searchOnEnter' => true, 'defaultSearch' => 'cn' ],
        ];
    }

    public function action_history_grid_data()
    {
        $id = BRequest::i()->params( 'id', true );
        if ( !$id ) {
            $data = [];
        } else {
            $orm = FCom_Cms_Model_BlockHistory::i()->orm( 'bh' )->select( 'bh.*' )
                ->where( 'block_id', $id );
            $data = FCom_Admin_View_Grid::i()->processORM( $orm, __METHOD__ );
        }
        BResponse::i()->json( $data );
    }

    public function action_history_grid_data__POST()
    {
        $this->_processGridDataPost( 'FCom_Cms_Model_BlockHistory' );
    }
}
