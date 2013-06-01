<?php

class FCom_CustomField_Admin extends BClass
{
    public static function bootstrap()
    {
        FCom_CustomField_Main::bootstrap();

        $ctrl = 'FCom_CustomField_Admin_Controller_FieldSets';
        BRouting::i()
            ->get('/customfields/fieldsets', $ctrl.'.index')
            ->any('/customfields/fieldsets/.action', $ctrl)

            ->get('/customfields/products/.action', 'FCom_CustomField_Admin_Controller_Products')
        ;

        BLayout::i()
            ->addAllViews('Admin/views')
            ->loadLayoutAfterTheme('Admin/layout.yml')
        ;

        BEvents::i()
//            ->on('FCom_Catalog_Model_Product::afterSave', 'FCom_CustomField_Admin.productAfterSave')
            ->on('FCom_Catalog_Admin_Controller_Products::gridColumns', 'FCom_CustomField_Admin.productGridColumns')
                //
            ->on('FCom_Catalog_Admin_Controller_Products::formViewBefore', 'FCom_CustomField_Admin_Controller_Products.formViewBefore');
        ;
    }

/*
    public function productAfterSave($args)
    {
        $p = $args['model'];
        $data = $p->as_array();
        $fields = FCom_CustomField_Model_Field::i()->fieldsInfo('product', true);
        if (array_intersect($fields, array_keys($data))) {
            $custom = FCom_CustomField_Model_ProductField::i()->load($p->id, 'product_id');
            if (!$custom) {
                $custom = FCom_CustomField_Model_ProductField::i()->create();
            }
            $custom->set('product_id', $p->id)->set($data)->save();
        }
        // not deleting to preserve meta info about fields
    }
*/
    public function productGridColumns($args)
    {
        $fields = FCom_CustomField_Model_Field::i()->orm('f')->find_many();
        foreach ($fields as $f) {
            $col = array('label'=>$f->field_name, 'index'=>'pcf.'.$f->field_name, 'hidden'=>true);
            if ($f->admin_input_type=='select') {
                $col['options'] = FCom_CustomField_Model_FieldOption::i()->orm()
                    ->where('field_id', $f->id)
                    ->find_many_assoc('id', 'label');
            }
            $args['columns'][$f->field_code] = $col;
        }
    }
}
