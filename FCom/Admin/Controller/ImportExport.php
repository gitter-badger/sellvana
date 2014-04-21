<?php

class FCom_Admin_Controller_ImportExport extends FCom_Admin_Controller_Abstract_GridForm
{
    protected $_permission = 'system/importexport';
    protected static $_origClass = __CLASS__;
    protected $_gridHref = 'importexport';
    protected $_gridTitle = 'Import Export';
    protected $_recordName = 'Import Export';

    /**
     * get config for grid: ie models
     * @param $model FCom_Admin_Model_Role
     * @return array
     */
    public function getIeConfig( $model )
    {

        $config              = parent::gridConfig();
        $config[ 'id' ]      = 'role_all_ie_perm_grid_' . $model->id();
        $config['data_mode'] = 'local';
        $config[ 'columns' ] = array(
            array( 'name' => 'permission_name', 'label' => 'Permission Name', 'width' => 250 ),
            array( 'type' => 'input', 'name' => 'import', 'label' => 'Import',
                   'width' => 100, 'editable' => 'inline', 'editor' => 'checkbox',
            ),
            array( 'type' => 'input', 'name' => 'export', 'label' => 'Export',
                   'width' => 100, 'editable' => 'inline', 'editor' => 'checkbox',
            ),
        );
        $config[ 'actions' ] = array(
            'add' => array( 'caption' => 'Add selected models' )
        );
        $config[ 'filters' ] = array(
            array( 'field' => 'permission_name', 'type' => 'text' ),
        );
        $config[ 'events' ] = array( 'add' );
        $config['grid_before_create'] = 'iePermGridRegister';


        $data = FCom_Core_ImportExport::i()->collectExportableModels();
        $permissions = array_flip( explode( "\n", $model->get( 'permissions_data' ) ) );
        $default = array( 'permission_name' => '', 'import' => 0, 'export' => 0, 'parent' => null, 'children' => array() );
        $fcom = $default;
        $fcom[ 'id' ] = 'FCom';
        $fcom[ 'permission_name' ] = 'FCom';
        $gridData = array( 'FCom' => $fcom );
        foreach ( $data as $id => $d ) {
            $module = explode( '_', $id, 3 );
            array_splice( $module, 2 );
            $module = join( '_', $module );
            if ( !isset( $gridData[ $module ] ) ) {
                $mod                                 = $default;
                $mod[ 'id' ]                         = $module;
                $mod[ 'permission_name' ]            = $module;
                $mod[ 'parent' ]                     = 'FCom';
                $gridData[ $module ]                 = $mod;
                $gridData[ 'FCom' ][ 'children' ][ ] = $module;
            }
            $obj                                  = $default;
            $obj[ 'id' ]                          = $id;
            $obj[ 'permission_name' ]             = $id;
            $obj[ 'parent' ]                      = $module;
            $gridData[ $id ]                      = $obj;
            $gridData[ $module ][ 'children' ][ ] = $id;
        }

        foreach ( $gridData as $id => &$value ) {
            $parent = $value['parent'];
            if ( isset( $permissions[ $id . '/import' ] ) || isset( $permissions[ $parent . '/import' ]) ) {
                $value[ 'import' ] = 1;
            }
            if ( isset( $permissions[ $id . '/export' ] ) || isset( $permissions[ $parent . '/export' ]) ) {
                $value[ 'export' ] = 1;
            }
        }

        $config[ 'data' ] = $gridData;

        return array( 'config' => $config );
    }
}
