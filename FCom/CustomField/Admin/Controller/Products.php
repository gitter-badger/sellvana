<?php

class FCom_CustomField_Admin_Controller_Products extends FCom_Admin_Controller_Abstract
{
    public function fieldsetsGridConfig()
    {
        $config = array(
            'grid' => array(
                'id'      => 'product_fieldsets',
                'caption' => 'Field Sets',
                'url' => BApp::href( 'customfields/fieldsets/grid_data' ),
                'orm' => 'FCom_CustomField_Model_SetField',
                'columns' => array(
                    'id' => array( 'label' => 'ID', 'width' => 55, 'sorttype' => 'number', 'key' => true ),
                    'set_code' => array( 'label' => 'Set Code', 'width' => 100, 'editable' => true ),
                    'set_name' => array( 'label' => 'Set Name', 'width' => 200, 'editable' => true ),
                    'num_fields' => array( 'label' => 'Fields', 'width' => 30 ),
                ),
                'actions' => array(
                            'edit' => true,
                            'delete' => true
                ),
                'filters' => array(
                            array( 'field' => 'set_name', 'type' => 'text' ),
                            array( 'field' => 'set_code', 'type' => 'text' ),
                            '_quick' => array( 'expr' => 'product_name like ? or set_code like ', 'args' =>  array( '%?%', '%?%' ) )
                )
            )
        );

        return $config;
    }

    public function variantFieldGridConfig( $model )
    {
        $data = $model->getData( 'variants_fields' );

        $config = array(
            'config' => array(
                'id' => 'variable-field-grid',
                'caption' => 'Variable Field Grid',
                'data_mode' => 'local',
                'data' => ( $data === null ? array() : $data ),
                'columns' => array(
                    array( 'type' => 'row_select' ),
                    array( 'name' => 'id', 'label' => 'ID', 'width' => 30, 'hidden' => true ),
                    array( 'name' => 'name', 'label' => 'Field Name', 'width' => 300 ),
                    array( 'type' => 'btn_group',  'buttons' => array( array( 'name' => 'delete' ) ) )
                ),
                'actions' => array(
                                   'delete' => array( 'caption' => 'Remove' )
                                ),
                'grid_before_create' => 'variantFieldGridRegister'
            )
        );

        return $config;
    }

    public function variantGridConfig( $model )
    {
        $columns = array(
            array( 'type' => 'row_select' ),
            array( 'name' => 'id', 'label' => 'ID', 'width' => 30, 'hidden' => true, 'position' => 1 )
        );

        $vFields = $model->getData( 'variants_fields' );
        if ( $vFields !== null ) {
            $pos = 2;
            foreach ( $vFields as $f ) {
                $f[ 'type' ] = 'input';
                $f[ 'options' ] = FCom_CustomField_Model_FieldOption::i()->getListAssocById( $f[ 'id' ] );
                $f[ 'label' ] = $f[ 'name' ];
                $f[ 'editable' ] = 'inline';
                $f[ 'addable' ] = true;
                $f[ 'mass-editable' ] = true;
                $f[ 'width' ] = 150;
                $f[ 'position' ] = $pos++;
                $f[ 'validation' ] = array( 'required' => true );
                $f[ 'editor' ] = 'select';
                $f[ 'default' ] = '';
                $columns[] = $f;
            }
        }
        $columns[] = array( 'type' => 'input', 'name' => 'sku', 'label' => 'SKU', 'width' => 150, 'editable' => 'inline',
                            'addable' => true, 'validation' => array( 'required' => true ), 'default' => '' );
        $columns[] = array( 'type' => 'input', 'name' => 'price', 'label' => 'PRICE', 'width' => 150, 'editable' => 'inline',
                            'addable' => true, 'validation' => array( 'required' => true, 'number' => true ), 'default' => '' );
        $columns[] = array( 'type' => 'input', 'name' => 'qty', 'label' => 'QTY', 'width' => 150, 'editable' => 'inline',
                            'addable' => true, 'validation' => array( 'required' => true, 'number' => true ), 'default' => '' );
        $columns[] = array( 'type' => 'btn_group',  'buttons' => array( array( 'name' => 'delete' ) ) );

        $data = array();

        $variants = $model->getData( 'variants' );

        if ( $variants !== null ) {
            $index = 0;
            foreach ( $variants as $v ) {
                $v[ 'fields' ][ 'sku' ] = $v[ 'sku' ];
                $v[ 'fields' ][ 'qty' ] = $v[ 'qty' ];
                $v[ 'fields' ][ 'price' ] = $v[ 'price' ];
                $v[ 'fields' ][ 'id' ] = $index++;
                $data[] = $v[ 'fields' ];
            }
        }

        $config = array(
            'config' => array(
                'id' => 'variant-grid',
                'caption' => 'Variable Field Grid',
                'data_mode' => 'local',
                'data' => $data,
                'columns' => $columns,
                'filters' => array(
                            '_quick' => array( 'expr' => 'field_name like ? or id like ', 'args' => array( '%?%', '%?%' ) )
                ),
                'actions' => array(
                                    'new' => array( 'caption' => 'New Variant' ),
                                    'delete' => array( 'caption' => 'Remove' )
                                ),
                'grid_before_create' => 'variantGridRegister'
            )
        );

        return $config;

    }

    /**
     * @param $model FCom_Catalog_Model_Product
     * @return array
     */
    public function frontendFieldGrid( $model )
    {
        $data = $model->getData( 'frontend_fields' );
        if ( !isset( $data ) )
            $data = array();
        $config = array(
            'config' => array(
                'id' => 'frontend-field-grid',
                'caption' => 'Frontend Field Grid',
                'data_mode' => 'local',
                'data' => $data,
                'columns' => array(
                    array( 'type' => 'row_select' ),
                    array( 'name' => 'id', 'label' => 'ID', 'width' => 30, 'hidden' => true ),
                    array( 'name' => 'name', 'label' => 'Field Name', 'width' => 200 ),
                    array( 'name' => 'label', 'label' => 'Field Label', 'width' => 200 ),
                    array( 'name' => 'input_type', 'label' => 'Input Type', 'width' => 200 ),
                    array( 'name' => 'options', 'label' => 'Options', 'width' => 200 ),
                    array( 'type' => 'input', 'name' => 'price', 'label' => 'Price', 'width' => 200, 'editable' => 'inline',
                            'validation' => array( 'number' => true ) ),
                    array( 'type' => 'btn_group', 'buttons' => array( array( 'name' => 'delete' ) ) )
                ),
                'actions' => array(
                                    'add' => array( 'caption' => 'Add Fields' ),
                                    'delete' => array( 'caption' => 'Remove' )
                                ),
                'grid_before_create' => 'frontendFieldGridRegister'
            )
        );

        return $config;
    }

    public function formViewBefore()
    {
        $id = BRequest::i()->params( 'id', true );
        $p = FCom_Catalog_Model_Product::i()->load( $id );

        if ( !$p ) {
            return;//$p = FCom_Catalog_Model_Product::i()->create();
        }

        $fields_options = array();
        $fields = FCom_CustomField_Model_ProductField::i()->productFields( $p );
        foreach ( $fields as $field ) {
            $fields_options[ $field->id ] = FCom_CustomField_Model_FieldOption::i()->orm()
                    ->where( "field_id", $field->id )->find_many();
        }
        $view = $this->view( 'customfields/products/fields-partial' );
        $view->set( 'model', $p )->set( 'fields', $fields )->set( 'fields_options', $fields_options );
    }

    public function action_field_remove()
    {
        $id = BRequest::i()->params( 'id', true );
        $p = FCom_Catalog_Model_Product::i()->load( $id );
        if ( !$p ) {
            return;
        }
        $hide_field = BRequest::i()->params( 'hide_field', true );
        if ( !$hide_field ) {
            return;
        }
        FCom_CustomField_Model_ProductField::i()->removeField( $p, $hide_field );
        BResponse::i()->json( '' );
    }

    public function action_fields_partial()
    {
        $id = BRequest::i()->params( 'id', true );
        $p = FCom_Catalog_Model_Product::i()->load( $id );
        if ( !$p ) {
            $p = FCom_Catalog_Model_Product::i()->create();
        }

        $fields_options = array();
        $fields = FCom_CustomField_Model_ProductField::i()->productFields( $p, BRequest::i()->request() );
        foreach ( $fields as $field ) {
            $fields_options[ $field->id ] = FCom_CustomField_Model_FieldOption::i()->orm()
                    ->where( "field_id", $field->id )->find_many();
        }

        $view = $this->view( 'customfields/products/fields-partial' );
        $view->set( 'model', $p )->set( 'fields', $fields )->set( 'fields_options', $fields_options );
        BLayout::i()->setRootView( 'customfields/products/fields-partial' );
        BResponse::i()->render();
    }

    public function getInitialData( $model )
    {
        $customFields = $model->getData( 'custom_fields' );
        return !isset( $customFields ) ? -1 : $customFields;
    }
    public function fieldsetAry()
    {
        $sets = BDb::many_as_array( FCom_CustomField_Model_Set::i()->orm( 's' )->select( 's.*' )->find_many() );

        return json_encode( $sets );
    }

    public function fieldAry()
    {
        $fields = BDb::many_as_array( FCom_CustomField_Model_SetField::i()->orm( 's' )->select( 's.*' )->find_many() );

        return json_encode( $fields );
    }

    public function action_get_fieldset()
    {
        $r = BRequest::i();
        $id = $r->get( 'id' );
        $set = FCom_CustomField_Model_Set::i()->load( $id );
        $fields = BDb::many_as_array(
                    FCom_CustomField_Model_SetField::i()->orm( 'sf' )
                    ->join( 'FCom_CustomField_Model_Field', array( 'f.id', '=', 'sf.field_id' ), 'f' )
                    ->select( array( 'f.id', 'f.field_name', 'f.admin_input_type' ) )
                    ->where( 'sf.set_id', $id )->find_many()
                );
        foreach ( $fields as &$field ) {
            if ( $field[ 'admin_input_type' ] === 'select' ||  $field[ 'admin_input_type' ] === 'multiselect' ) {
                $field[ 'options' ] = FCom_CustomField_Model_FieldOption::i()->getListAssocById( $field[ 'id' ] );
            }
        }

        BResponse::i()->json( array( 'id' => $set->id, 'set_name' => $set->set_name, 'fields' => ( $fields ) ) );
    }

    public function action_get_field()
    {
        $r = BRequest::i();
        $id = $r->get( 'id' );
        $field = FCom_CustomField_Model_Field::i()->load( $id );
        $options = FCom_CustomField_Model_FieldOption::i()->getListAssocById( $field->id );
        BResponse::i()->json( array( 'id' => $field->id, 'field_name' => $field->field_name, 'admin_input_type' => $field->admin_input_type, 'multilang' => $field->multilanguage, 'options' => $options, 'required' => $field->required ) );
    }

    public function action_save__POST()
    {
         $data = BRequest::i()->post();
         $prodId = $data[ 'id' ];
         $json = $data[ 'json' ];

         $res = BDb::many_as_array( FCom_CustomField_Model_ProductField::i()->orm()->where( 'product_id', $prodId )->find_many() );

         if ( empty( $res ) ) {
            $new = FCom_CustomField_Model_ProductField::i()->create();
            $new->product_id = $prodId;
            $new->_data_serialized = $json;
            $new->save();
            $status = 'Successfully saved.';
         } else {

            $row = FCom_CustomField_Model_ProductField::i()->load( $res[ 0 ][ 'id' ] );
            $row->_data_serialized = $json;
            $row->save();
            $status = 'Successfully updated.';
         }

         BResponse::i()->json( array( 'status' => $status ) );
    }

    public function action_get_fields__POST()
    {
        $res = array();
        $data = BRequest::i()->post();
        $ids = explode( ',', $data[ 'ids' ] );
        $optionsModel = FCom_CustomField_Model_FieldOption::i();
        $fieldModel = FCom_CustomField_Model_Field::i();
        foreach ( $ids as $id ) {
            $field = $fieldModel->load( $id );
            $options = join( ',', array_keys( $optionsModel->getListAssocById( $id ) ) );
            $res[] = array( 'id' => $id, 'name' => $field->field_name, 'label' => $field->frontend_label, 'input_type' => $field->admin_input_type, 'options' => $options );
        }

        BResponse::i()->json( $res );
    }

    public function getFieldTypes()
    {
        $f = FCom_CustomField_Model_Field::i();
        return $f->fieldOptions( 'table_field_type' );
    }

    public function getAdminInputTypes()
    {
        $f = FCom_CustomField_Model_Field::i();
        return $f->fieldOptions( 'admin_input_type' );
    }
}
